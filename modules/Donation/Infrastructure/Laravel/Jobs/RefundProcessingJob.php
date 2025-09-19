<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Donation\Application\Command\RefundDonationCommand;
use Modules\Donation\Application\Command\RefundDonationCommandHandler;
use Modules\Donation\Application\Event\DonationRefundedEvent;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Donation\Domain\ValueObject\RefundRequest;
use Modules\Donation\Infrastructure\Service\PaymentGatewayFactory;
use Modules\Shared\Infrastructure\Audit\AuditService;

final class RefundProcessingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        private readonly int $donationId,
        private readonly float $refundAmount,
        private readonly string $refundReason,
        /** @var array<string, mixed> */
        private readonly array $webhookPayload = [],
        private readonly bool $isWebhookInitiated = false,
        private readonly ?int $initiatedByUserId = null,
    ) {
        $this->onQueue('payments');
    }

    public function handle(
        DonationRepositoryInterface $donationRepository,
        CampaignRepositoryInterface $campaignRepository,
        RefundDonationCommandHandler $refundHandler,
        PaymentGatewayFactory $gatewayFactory,
        AuditService $auditService,
    ): void {
        Log::info('Processing donation refund', [
            'donation_id' => $this->donationId,
            'refund_amount' => $this->refundAmount,
            'reason' => $this->refundReason,
            'webhook_initiated' => $this->isWebhookInitiated,
            'job_id' => $this->job?->getJobId(),
        ]);

        DB::transaction(function () use (
            $donationRepository,
            $campaignRepository,
            $refundHandler,
            $gatewayFactory,
            $auditService
        ): void {
            // 1. Get and validate donation
            $donation = $donationRepository->findById($this->donationId);

            if (! $donation instanceof Donation) {
                Log::error('Donation not found for refund processing', [
                    'donation_id' => $this->donationId,
                ]);
                $this->fail('Donation not found');

                return;
            }

            if (! $donation->canBeRefunded()) {
                Log::error('Donation cannot be refunded', [
                    'donation_id' => $this->donationId,
                    'donation_status' => $donation->status,
                    'processed_at' => $donation->processed_at,
                ]);
                $this->fail('Donation cannot be refunded');

                return;
            }

            // 2. Process refund with payment gateway (if not webhook initiated)
            if (! $this->isWebhookInitiated) {
                $this->processGatewayRefund($donation, $gatewayFactory);
            }

            // 3. Update donation status
            $command = new RefundDonationCommand(
                $this->donationId,
                $this->refundReason,
                $this->initiatedByUserId ?? 1, // Default to system user if not provided
            );

            $refundHandler->handle($command);

            // 4. Update campaign totals
            $campaign = $campaignRepository->findById($donation->campaign_id);

            if ($campaign instanceof Campaign) {
                // Update campaign amounts manually since methods don't exist
                $campaign->current_amount = max(0, $campaign->current_amount - $this->refundAmount);

                // Update the campaign record
                $campaignRepository->updateById($campaign->id, [
                    'current_amount' => $campaign->current_amount,
                ]);

                Log::info('Campaign totals updated for refund', [
                    'campaign_id' => $campaign->id,
                    'refunded_amount' => $this->refundAmount,
                    'new_total' => $campaign->current_amount,
                ]);
            }

            // 5. Handle corporate matching refund
            $metadata = $donation->metadata ?? [];

            if (isset($metadata['corporate_match_amount']) && (float) $metadata['corporate_match_amount'] > 0) {
                $this->processMatchingRefund($donation);
            }

            // 6. Create audit log
            $auditService->log(
                'donation_refunded',
                'donation',
                $this->donationId,
                ['status' => $donation->status->value],
                ['status' => 'refunded', 'refund_amount' => $this->refundAmount],
                [
                    'refund_amount' => $this->refundAmount,
                    'reason' => $this->refundReason,
                    'original_amount' => $donation->amount,
                    'is_partial_refund' => $this->refundAmount < $donation->amount,
                    'initiated_by' => $this->initiatedByUserId,
                    'webhook_initiated' => $this->isWebhookInitiated,
                ],
            );

            // 7. Dispatch events
            Event::dispatch(new DonationRefundedEvent(
                $donation->id,
                $donation->campaign_id,
                $donation->user_id,
                $this->refundAmount,
                $donation->currency,
                $this->refundReason,
                $this->initiatedByUserId ?? 1,
            ));

            // 8. Send notifications
            $this->dispatchNotifications($donation);

            Log::info('Refund processing completed successfully', [
                'donation_id' => $this->donationId,
                'refund_amount' => $this->refundAmount,
                'original_amount' => $donation->amount,
            ]);
        });
    }

    public function failed(Exception $exception): void
    {
        Log::error('Refund processing job failed permanently', [
            'donation_id' => $this->donationId,
            'refund_amount' => $this->refundAmount,
            'reason' => $this->refundReason,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Mark refund as failed
        try {
            $donation = app(DonationRepositoryInterface::class)->findById($this->donationId);

            if ($donation) {
                $metadata = $donation->metadata ?? [];
                $metadata['refund_failed_at'] = now()->toISOString();
                $metadata['refund_failure_reason'] = $exception->getMessage();
                $donation->metadata = $metadata;
                app(DonationRepositoryInterface::class)->updateById($donation->id, ['metadata' => $metadata]);
            }
        } catch (Exception $e) {
            Log::error('Failed to update donation after refund job failure', [
                'donation_id' => $this->donationId,
                'error' => $e->getMessage(),
            ]);
        }

        // Notify administrators about failed refund
        try {
            SendAdminNotificationJob::dispatch(
                'Refund Processing Failed',
                [
                    'donation_id' => $this->donationId,
                    'refund_amount' => $this->refundAmount,
                    'reason' => $this->refundReason,
                    'error' => $exception->getMessage(),
                    'attempts' => $this->attempts(),
                ],
            )->onQueue('notifications');
        } catch (Exception $e) {
            Log::error('Failed to send admin notification about refund failure', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function processGatewayRefund(Donation $donation, PaymentGatewayFactory $gatewayFactory): void
    {
        try {
            if ($donation->payment_gateway === null) {
                throw new Exception('Payment gateway not specified for donation');
            }

            $gateway = $gatewayFactory->create($donation->payment_gateway);

            $refundRequest = new RefundRequest(
                $donation->transaction_id ?? '',
                $this->refundAmount,
                $donation->currency,
                $this->refundReason,
                $this->webhookPayload,
            );

            $result = $gateway->refundPayment($refundRequest);

            if (! $result->isSuccessful()) {
                throw new Exception('Gateway refund failed: ' . $result->getErrorMessage());
            }

            Log::info('Payment gateway refund processed successfully', [
                'donation_id' => $this->donationId,
                'gateway' => $donation->payment_gateway,
                'transaction_id' => $result->getTransactionId(),
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to process gateway refund', [
                'donation_id' => $this->donationId,
                'gateway' => $donation->payment_gateway,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function processMatchingRefund(Donation $donation): void
    {
        // TODO: Implement proper method to find matching donations or adjust the logic
        // For now, just log that corporate matching refund would be processed
        Log::info('Corporate matching refund would be processed here', [
            'donation_id' => $donation->id,
            'refund_amount' => $this->refundAmount,
        ]);

        // Note: This method intentionally has no implementation as the repository
        // method findByParentDonation doesn't exist. This should be implemented
        // when the proper repository method is available.
    }

    private function dispatchNotifications(Donation $donation): void
    {
        try {
            // Send refund notification to donor
            SendRefundNotificationJob::dispatch(
                $donation->id,
                $this->refundAmount,
                $this->refundReason,
            )->onQueue('notifications');

            // Notify campaign organizer if configured
            // Note: Campaign doesn't have metadata property, using default notification behavior
            if (config('donation.notifications.notify_organizer_on_refunds', true)) {
                SendCampaignUpdateNotificationJob::dispatch(
                    $donation->campaign->id ?? 0,
                    'donation_refunded',
                    [
                        'donation' => $donation,
                        'refund_amount' => $this->refundAmount,
                        'refund_reason' => $this->refundReason,
                    ],
                )->onQueue('notifications');
            }

            // Notify administrators for significant refunds
            if ($this->refundAmount >= config('donation.significant_refund_threshold', 1000)) {
                SendAdminNotificationJob::dispatch(
                    'Significant Donation Refund',
                    [
                        'donation_id' => $this->donationId,
                        'amount' => $this->refundAmount,
                        'reason' => $this->refundReason,
                        'campaign' => $donation->campaign->title ?? 'Unknown Campaign',
                    ],
                )->onQueue('notifications');
            }
        } catch (Exception $exception) {
            Log::error('Failed to dispatch refund notifications', [
                'donation_id' => $donation->id,
                'error' => $exception->getMessage(),
            ]);
            // Don't fail job for notification errors
        }
    }
}
