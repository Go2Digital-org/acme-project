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
use Modules\Donation\Application\Command\CompleteDonationCommand;
use Modules\Donation\Application\Command\CompleteDonationCommandHandler;
use Modules\Donation\Application\Event\DonationCompletedEvent;
use Modules\Donation\Application\Service\DonationNotificationService;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Shared\Domain\ValueObject\DonationStatus;
use Modules\Shared\Infrastructure\Audit\AuditService;

// Job imports for notification dispatch
class_exists(GenerateTaxReceiptJob::class) ?: null;
class_exists(SendPaymentConfirmationJob::class) ?: null;
class_exists(\Modules\Campaign\Infrastructure\Laravel\Jobs\SendCampaignUpdateNotificationJob::class) ?: null;
class_exists(\Modules\Campaign\Infrastructure\Laravel\Jobs\SendMilestoneNotificationJob::class) ?: null;

/**
 * @phpstan-type PaymentDetailsArray array<string, mixed>
 */
final class ProcessDonationJob implements ShouldQueue
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
        private readonly string $transactionId,
        /** @var array<string, mixed> Reserved for future payment validation */
        private readonly array $paymentDetails = [],
        private readonly bool $shouldApplyCorporateMatch = true,
        private readonly bool $shouldGenerateReceipt = true,
    ) {
        $this->onQueue('payments');
    }

    public function handle(
        DonationRepositoryInterface $donationRepository,
        CampaignRepositoryInterface $campaignRepository,
        CompleteDonationCommandHandler $completeDonationHandler,
        DonationNotificationService $notificationService,
        AuditService $auditService,
    ): void {
        Log::info('Processing donation workflow', [
            'donation_id' => $this->donationId,
            'transaction_id' => $this->transactionId,
            'job_id' => $this->job?->getJobId(),
            'has_payment_data' => $this->paymentDetails !== [],
        ]);

        DB::transaction(function () use (
            $donationRepository,
            $campaignRepository,
            $completeDonationHandler,
            $auditService
        ): void {
            // 1. Get donation and validate
            $donation = $donationRepository->findById($this->donationId);

            if (! $donation instanceof Donation) {
                Log::error('Donation not found for processing', [
                    'donation_id' => $this->donationId,
                ]);
                $this->fail('Donation not found');

                return;
            }

            // 2. Complete donation
            $command = new CompleteDonationCommand(
                $this->donationId,
                $this->transactionId,
            );

            $completeDonationHandler->handle($command);

            // 3. Update campaign totals
            $campaign = $campaignRepository->findById($donation->campaign_id);

            if ($campaign instanceof Campaign) {
                // Update current amount
                $newCurrentAmount = ($campaign->current_amount ?? 0) + $donation->amount;
                $campaignRepository->updateById($campaign->id, [
                    'current_amount' => $newCurrentAmount,
                ]);

                Log::info('Campaign totals updated', [
                    'campaign_id' => $campaign->id,
                    'new_total' => $newCurrentAmount,
                ]);
            }

            // 4. Apply corporate matching if enabled
            if ($this->shouldApplyCorporateMatch && $campaign instanceof Campaign && ($campaign->has_corporate_matching ?? false)) {
                $this->applyCorporateMatching($donation, $donationRepository);
            }

            // 5. Create audit log
            $auditService->log(
                'donation_processed',
                'donation',
                $this->donationId,
                [],
                [
                    'transaction_id' => $this->transactionId,
                    'amount' => $donation->amount,
                    'currency' => $donation->currency,
                    'campaign_id' => $donation->campaign_id,
                    'user_id' => $donation->user_id,
                    'processing_completed' => true,
                ],
            );

            // 6. Dispatch events
            Event::dispatch(new DonationCompletedEvent(
                $donation->id,
                $donation->campaign_id,
                $donation->user_id,
                $donation->amount,
                $donation->currency,
            ));

            // 7. Send notifications
            $this->dispatchNotifications($donation);

            // 8. Generate tax receipt if required
            if ($this->shouldGenerateReceipt && $donation->amount >= 25.00) {
                GenerateTaxReceiptJob::dispatch($donation->id)
                    ->delay(now()->addMinutes(5))
                    ->onQueue('reports');
            }

            Log::info('Donation workflow completed successfully', [
                'donation_id' => $this->donationId,
                'transaction_id' => $this->transactionId,
                'amount' => $donation->amount,
                'corporate_match_applied' => $this->shouldApplyCorporateMatch && $campaign instanceof Campaign && ($campaign->has_corporate_matching ?? false),
            ]);
        });
    }

    public function failed(Exception $exception): void
    {
        Log::error('Donation processing job failed permanently', [
            'donation_id' => $this->donationId,
            'transaction_id' => $this->transactionId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Mark donation as failed
        try {
            $donationRepository = app(DonationRepositoryInterface::class);
            $donation = $donationRepository->findById($this->donationId);

            if ($donation) {
                $donationRepository->updateById($this->donationId, [
                    'status' => DonationStatus::FAILED,
                    'failure_reason' => 'Processing job failed: ' . $exception->getMessage(),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to update donation status after job failure', [
                'donation_id' => $this->donationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function applyCorporateMatching(
        Donation $donation,
        DonationRepositoryInterface $donationRepository,
    ): void {
        try {
            $matchingAmount = $this->calculateCorporateMatch($donation);

            if ($matchingAmount > 0) {
                // Update original donation with matching amount
                $donationRepository->updateById($donation->id, [
                    'metadata' => array_merge($donation->metadata ?? [], [
                        'corporate_match_amount' => $matchingAmount,
                    ]),
                ]);

                // Create separate matching donation record
                $matchingDonationData = [
                    'campaign_id' => $donation->campaign_id,
                    'user_id' => null, // Corporate match has no employee
                    'amount' => $matchingAmount,
                    'currency' => $donation->currency,
                    'payment_method' => $donation->payment_method,
                    'transaction_id' => $donation->transaction_id . '_match',
                    'status' => $donation->status,
                    'anonymous' => true,
                    'donated_at' => $donation->donated_at,
                    'metadata' => [
                        'is_corporate_match' => true,
                        'parent_donation_id' => $donation->id,
                    ],
                ];
                $matchingDonation = $donationRepository->create($matchingDonationData);

                Log::info('Corporate matching applied', [
                    'original_donation_id' => $donation->id,
                    'matching_donation_id' => $matchingDonation->id,
                    'matching_amount' => $matchingAmount,
                ]);
            }
        } catch (Exception $exception) {
            Log::error('Failed to apply corporate matching', [
                'donation_id' => $donation->id,
                'error' => $exception->getMessage(),
            ]);
            // Don't fail the entire job for matching errors
        }
    }

    private function calculateCorporateMatch(Donation $donation): float
    {
        $campaign = $donation->campaign;

        if (! $campaign || ! ($campaign->has_corporate_matching ?? false)) {
            return 0.0;
        }

        $matchRate = $campaign->corporate_matching_rate ?? 1.0;
        $maxMatch = $campaign->max_corporate_matching ?? PHP_FLOAT_MAX;

        $matchingAmount = $donation->amount * $matchRate;

        // Check if campaign hasn't exceeded total matching budget
        $totalMatched = $campaign->donations()
            ->whereJsonContains('metadata->is_corporate_match', true)
            ->sum('amount');

        if (($totalMatched + $matchingAmount) > $maxMatch) {
            return max(0, $maxMatch - $totalMatched);
        }

        return $matchingAmount;
    }

    private function dispatchNotifications(
        Donation $donation,
    ): void {
        try {
            // Send confirmation email to donor
            SendPaymentConfirmationJob::dispatch($donation->id)
                ->onQueue('notifications');

            // Notify campaign organizer
            $campaign = $donation->campaign;
            if ($campaign) {
                SendCampaignUpdateNotificationJob::dispatch(
                    $campaign->id,
                    'new_donation',
                    ['donation_id' => $donation->id],
                )->onQueue('notifications');
            }

            // Send milestone notifications if reached
            $this->checkAndSendMilestoneNotifications($donation);
        } catch (Exception $exception) {
            Log::error('Failed to dispatch donation notifications', [
                'donation_id' => $donation->id,
                'error' => $exception->getMessage(),
            ]);
            // Don't fail job for notification errors
        }
    }

    private function checkAndSendMilestoneNotifications(Donation $donation): void
    {
        $campaign = $donation->campaign;

        if (! $campaign) {
            return;
        }

        /** @var array<int, array<string, int>> */
        $milestones = [
            ['percentage' => 25],
            ['percentage' => 50],
            ['percentage' => 75],
            ['percentage' => 100],
        ];

        foreach ($milestones as $milestone) {
            $targetAmount = ($campaign->goal_amount ?? 0) * ($milestone['percentage'] / 100);

            if (($campaign->current_amount ?? 0) >= $targetAmount) {
                SendMilestoneNotificationJob::dispatch(
                    $campaign->id,
                    $milestone['percentage'],
                )->onQueue('notifications');

                Log::info('Milestone notification dispatched', [
                    'campaign_id' => $campaign->id,
                    'percentage' => $milestone['percentage'],
                    'target_amount' => $targetAmount,
                    'current_amount' => $campaign->current_amount ?? 0,
                ]);
            }
        }
    }
}
