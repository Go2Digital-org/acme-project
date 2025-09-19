<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Jobs;

use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;

final class SendRefundNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 60;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly int $donationId,
        private readonly float $refundAmount,
        private readonly string $refundReason,
        /** @var array<string, mixed> */
        private readonly array $refundDetails = [],
    ) {
        $this->onQueue('notifications');
    }

    public function handle(DonationRepositoryInterface $donationRepository): void
    {
        Log::info('Sending refund notification', [
            'donation_id' => $this->donationId,
            'refund_amount' => $this->refundAmount,
            'job_id' => $this->job?->getJobId(),
        ]);

        $donation = $donationRepository->findById($this->donationId);

        if (! $donation instanceof Donation) {
            Log::error('Donation not found for refund notification', [
                'donation_id' => $this->donationId,
            ]);

            return;
        }

        if (! $this->isRefundNotificationRequired($donation)) {
            Log::info('Refund notification not required', [
                'donation_id' => $this->donationId,
                'status' => $donation->status->value,
            ]);

            return;
        }

        if (! $donation->user) {
            Log::warning('Cannot send refund notification for donation without employee', [
                'donation_id' => $this->donationId,
                'anonymous' => $donation->anonymous,
            ]);

            return;
        }

        if ($this->hasRefundNotificationAlreadyBeenSent($donation)) {
            Log::info('Refund notification already sent', [
                'donation_id' => $this->donationId,
            ]);

            return;
        }

        try {
            $this->sendRefundConfirmationEmail($donation);
            $this->updateDonorAccountRecords($donation, $donationRepository);
            $this->markRefundNotificationAsSent($donation, $donationRepository);

            Log::info('Refund notification sent successfully', [
                'donation_id' => $this->donationId,
                'refund_amount' => $this->refundAmount,
                'employee_email' => $donation->user?->email,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send refund notification', [
                'donation_id' => $this->donationId,
                'refund_amount' => $this->refundAmount,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Refund notification job failed permanently', [
            'donation_id' => $this->donationId,
            'refund_amount' => $this->refundAmount,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Mark in donation metadata that refund notification failed
        try {
            $donation = app(DonationRepositoryInterface::class)->findById($this->donationId);

            if ($donation) {
                $metadata = $donation->metadata ?? [];
                $metadata['refund_notification_failed_at'] = now()->toISOString();
                $metadata['refund_notification_error'] = $exception->getMessage();
                $donation->metadata = $metadata;
                app(DonationRepositoryInterface::class)->save($donation);
            }
        } catch (Exception $e) {
            Log::error('Failed to update donation metadata after refund notification job failure', [
                'donation_id' => $this->donationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isRefundNotificationRequired(Donation $donation): bool
    {
        // Only send notifications for actual refunds
        if ($donation->status->value !== 'refunded') {
            return false;
        }

        // Don't send for cancelled donations that were never processed
        return $donation->processed_at instanceof Carbon;
    }

    private function hasRefundNotificationAlreadyBeenSent(Donation $donation): bool
    {
        $metadata = $donation->metadata ?? [];

        return isset($metadata['refund_notification_sent_at']);
    }

    private function sendRefundConfirmationEmail(Donation $donation): void
    {
        $employee = $donation->user;

        if ($employee === null) {
            throw new Exception('Cannot send refund confirmation email: donation has no associated employee');
        }

        $locale = $employee->locale ?? app()->getLocale();

        $this->prepareRefundData($donation);

        try {
            // TODO: Implement actual email sending
            // Mail::to($employee->email)
            //     ->locale($locale)
            //     ->send(new RefundConfirmationMail($donation, $this->refundAmount, $this->refundReason, $refundData));

            Log::info('Refund confirmation email sent', [
                'donation_id' => $donation->id,
                'employee_email' => $employee->email,
                'locale' => $locale,
                'refund_amount' => $this->refundAmount,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send refund confirmation email', [
                'donation_id' => $donation->id,
                'employee_email' => $employee->email,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareRefundData(Donation $donation): array
    {
        $processingTime = $this->calculateRefundProcessingTime();

        return [
            'original_donation' => [
                'amount' => $donation->amount,
                'currency' => $donation->currency,
                'donated_at' => $donation->donated_at,
                'transaction_id' => $donation->transaction_id,
            ],
            'refund_details' => [
                'amount' => $this->refundAmount,
                'reason' => $this->refundReason,
                'initiated_at' => now(),
                'expected_completion' => now()->addWeekdays($processingTime['days']),
                'processing_time_text' => $processingTime['text'],
            ],
            'campaign_info' => [
                'name' => $donation->campaign->title ?? 'Unknown Campaign',
                'organization' => $donation->campaign?->organization?->getName() ?? 'N/A',
            ],
            'payment_method' => $this->getRefundPaymentMethodInfo($donation),
            'contact_info' => $this->getCustomerSupportInfo(),
            'next_steps' => $this->getRefundNextSteps($donation),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateRefundProcessingTime(): array
    {
        // Processing time varies by payment method
        $paymentMethod = $this->refundDetails['payment_gateway'] ?? 'unknown';

        $processingTimes = [
            'stripe' => ['days' => 5, 'text' => '3-5 business days'],
            'paypal' => ['days' => 3, 'text' => '1-3 business days'],
            'mollie' => ['days' => 7, 'text' => '5-7 business days'],
            'default' => ['days' => 7, 'text' => '5-10 business days'],
        ];

        return $processingTimes[$paymentMethod] ?? $processingTimes['default'];
    }

    /**
     * @return array<string, mixed>
     */
    private function getRefundPaymentMethodInfo(Donation $donation): array
    {
        $paymentMethod = $donation->payment_method->value ?? 'unknown';

        $methodInfo = [
            'credit_card' => [
                'display_name' => 'Credit Card',
                'refund_destination' => 'Original credit card',
                'icon' => 'credit-card',
            ],
            'paypal' => [
                'display_name' => 'PayPal',
                'refund_destination' => 'PayPal account',
                'icon' => 'paypal',
            ],
            'bank_transfer' => [
                'display_name' => 'Bank Transfer',
                'refund_destination' => 'Original bank account',
                'icon' => 'bank',
            ],
        ];

        return $methodInfo[$paymentMethod] ?? [
            'display_name' => ucfirst(str_replace('_', ' ', $paymentMethod)),
            'refund_destination' => 'Original payment method',
            'icon' => 'payment',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getCustomerSupportInfo(): array
    {
        return [
            'email' => config('mail.support_email', 'support@acme-corp.com'),
            'phone' => config('support.phone', '+1-800-123-4567'),
            'hours' => 'Monday-Friday, 9 AM - 5 PM EST',
            'reference_number' => 'REF-' . $this->donationId . '-' . now()->format('Ymd'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getRefundNextSteps(Donation $donation): array
    {
        return [
            'watch_account' => 'Monitor your payment method for the refund',
            'keep_confirmation' => 'Save this email as confirmation',
            'contact_support' => 'Contact support if refund is not received within expected timeframe',
            'tax_implications' => $this->getTaxImplications($donation),
        ];
    }

    private function getTaxImplications(Donation $donation): ?string
    {
        // Check if tax receipt was generated
        $metadata = $donation->metadata ?? [];

        if (isset($metadata['tax_receipt_generated_at'])) {
            return 'You may need to adjust your tax records as this donation refund could affect your tax deduction.';
        }

        return null;
    }

    private function updateDonorAccountRecords(
        Donation $donation,
        DonationRepositoryInterface $donationRepository,
    ): void {
        try {
            // Update donation metadata with refund notification info
            $metadata = $donation->metadata ?? [];
            $metadata['refund_notification_data'] = [
                'refund_amount' => $this->refundAmount,
                'refund_reason' => $this->refundReason,
                'notification_sent_at' => now()->toISOString(),
                'refund_details' => $this->refundDetails,
            ];

            $donation->metadata = $metadata;
            $donationRepository->save($donation);

            Log::info('Donor account records updated for refund', [
                'donation_id' => $donation->id,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to update donor account records', [
                'donation_id' => $donation->id,
                'error' => $exception->getMessage(),
            ]);
            // Don't fail the entire job for record update errors
        }
    }

    private function markRefundNotificationAsSent(
        Donation $donation,
        DonationRepositoryInterface $donationRepository,
    ): void {
        try {
            $metadata = $donation->metadata ?? [];
            $metadata['refund_notification_sent_at'] = now()->toISOString();
            $donation->metadata = $metadata;
            $donationRepository->save($donation);

            Log::info('Refund notification marked as sent', [
                'donation_id' => $donation->id,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to mark refund notification as sent', [
                'donation_id' => $donation->id,
                'error' => $exception->getMessage(),
            ]);
            // Don't fail the entire job for metadata update errors
        }
    }
}
