<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;

final class SendPaymentConfirmationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [15, 30, 60];

    public function __construct(
        private readonly int $donationId,
        private readonly ?string $locale = null,
    ) {
        $this->onQueue('notifications');
    }

    public static function fromDonation(Donation $donation): self
    {
        return new self(
            donationId: $donation->id,
            locale: $donation->user->locale ?? app()->getLocale(),
        );
    }

    public function handle(DonationRepositoryInterface $donationRepository): void
    {
        Log::info('Sending payment confirmation email', [
            'donation_id' => $this->donationId,
            'job_id' => $this->job?->getJobId(),
        ]);

        try {
            $donation = $donationRepository->findById($this->donationId);

            if (! $donation instanceof Donation) {
                Log::error('Donation not found for confirmation email', [
                    'donation_id' => $this->donationId,
                ]);
                $this->fail('Donation not found');

                return;
            }

            // Skip sending confirmation if donation is anonymous and employee opted out
            if ($donation->is_anonymous && ($donation->user?->wants_donation_confirmations === false)) {
                Log::info('Skipping confirmation email for anonymous donation with opt-out', [
                    'donation_id' => $this->donationId,
                ]);

                return;
            }

            $this->sendConfirmationEmail($donation);

            Log::info('Payment confirmation email sent successfully', [
                'donation_id' => $this->donationId,
                'recipient' => $donation->user?->email,
                'amount' => $donation->amount,
                'currency' => $donation->currency,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send payment confirmation email', [
                'donation_id' => $this->donationId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Payment confirmation email job failed permanently', [
            'donation_id' => $this->donationId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Optionally store failed email attempt for retry later
        try {
            $donation = app(DonationRepositoryInterface::class)->findById($this->donationId);

            if ($donation instanceof Donation) {
                $donation->confirmation_email_failed_at = now();
                $donation->confirmation_email_failure_reason = $exception->getMessage();
                app(DonationRepositoryInterface::class)->save($donation);
            }
        } catch (Exception $e) {
            Log::error('Failed to update donation after confirmation email failure', [
                'donation_id' => $this->donationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendConfirmationEmail(Donation $donation): void
    {
        $employee = $donation->user;
        $campaign = $donation->campaign;

        if (! $campaign) {
            Log::warning('Cannot send payment confirmation - campaign not found', [
                'donation_id' => $this->donationId,
            ]);

            return;
        }

        $organization = $campaign->organization;

        // Set locale for email
        $originalLocale = app()->getLocale();

        if ($this->locale) {
            app()->setLocale($this->locale);
        }

        try {
            // Prepare email data
            $emailData = [
                'donation' => $donation,
                'employee' => $employee,
                'campaign' => $campaign,
                'organization' => $organization,
                'confirmation_number' => $this->generateConfirmationNumber($donation),
                'donation_date' => $donation->processed_at?->format('F j, Y \a\t g:i A') ?? 'Not processed',
                'tax_deductible' => $donation->isEligibleForTaxReceipt(),
                'corporate_match_amount' => $donation->corporate_match_amount,
                'total_impact' => $donation->amount + ($donation->corporate_match_amount ?? 0),
            ];

            // Create and send email
            $mailable = $this->createConfirmationMailable($emailData);

            if ($employee?->email !== null) {
                Mail::to($employee->email)
                    ->queue($mailable);
            }

            // Send copy to campaign organizer if requested
            if (($campaign->send_confirmation_copies_to_organizer ?? false) && $campaign->employee !== null && $campaign->employee->getEmail() !== null) {
                Mail::to($campaign->employee->getEmail())
                    ->queue($this->createOrganizerCopyMailable($emailData));
            }
        } finally {
            // Restore original locale
            app()->setLocale($originalLocale);
        }
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    /**
     * @param  array<array-key, mixed>  $data
     */
    private function createConfirmationMailable(array $data): Mailable
    {
        return new class($data) extends Mailable
        {
            /**
             * @param  array<array-key, mixed>  $data
             */
            public function __construct(private array $data) {}

            public function build(): self
            {
                $donation = $this->data['donation'];
                $campaign = $this->data['campaign'];

                /** @var view-string $viewName */
                $viewName = 'emails.donation-confirmation';

                return $this->subject(__('donation.confirmation_email_subject', [
                    'campaign' => $campaign->title,
                    'amount' => number_format($donation->amount, 2),
                    'currency' => strtoupper((string) $donation->currency),
                ]))
                    ->view($viewName)
                    ->with($this->data);
            }
        };
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    /**
     * @param  array<array-key, mixed>  $data
     */
    private function createOrganizerCopyMailable(array $data): Mailable
    {
        return new class($data) extends Mailable
        {
            /**
             * @param  array<array-key, mixed>  $data
             */
            public function __construct(private array $data) {}

            public function build(): self
            {
                $donation = $this->data['donation'];
                $campaign = $this->data['campaign'];
                $employee = $this->data['employee'];

                /** @var view-string $viewName */
                $viewName = 'emails.donation-organizer-notification';

                return $this->subject(__('donation.organizer_notification_subject', [
                    'campaign' => $campaign->title,
                    'donor' => $donation->is_anonymous ? 'Anonymous' : $employee->name,
                    'amount' => number_format($donation->amount, 2),
                    'currency' => strtoupper((string) $donation->currency),
                ]))
                    ->view($viewName)
                    ->with($this->data);
            }
        };
    }

    private function generateConfirmationNumber(Donation $donation): string
    {
        return sprintf(
            'DN-%s-%06d',
            $donation->processed_at?->format('Ymd') ?? now()->format('Ymd'),
            $donation->id,
        );
    }
}
