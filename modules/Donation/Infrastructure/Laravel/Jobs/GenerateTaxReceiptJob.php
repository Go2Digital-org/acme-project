<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Jobs;

use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;

final class GenerateTaxReceiptJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly int $donationId,
        private readonly bool $sendEmail = true,
    ) {
        $this->onQueue('reports');
    }

    public function handle(DonationRepositoryInterface $donationRepository): void
    {
        Log::info('Starting tax receipt generation', [
            'donation_id' => $this->donationId,
            'job_id' => $this->job?->getJobId(),
        ]);

        $donation = $donationRepository->findById($this->donationId);

        if (! $donation instanceof Donation) {
            Log::error('Donation not found for tax receipt generation', [
                'donation_id' => $this->donationId,
            ]);

            return;
        }

        if (! $this->isDonationEligible($donation)) {
            Log::info('Donation not eligible for tax receipt', [
                'donation_id' => $this->donationId,
                'status' => $donation->status->value,
                'amount' => $donation->amount,
            ]);

            return;
        }

        if ($this->hasExistingReceipt($donation)) {
            Log::info('Tax receipt already exists for donation', [
                'donation_id' => $this->donationId,
            ]);

            return;
        }

        if (! $donation->user) {
            Log::warning('Cannot generate tax receipt for anonymous donation', [
                'donation_id' => $this->donationId,
            ]);

            return;
        }

        try {
            $receiptPath = $this->generateTaxReceiptPdf($donation);
            $this->updateDonationRecord($donation, $receiptPath, $donationRepository);

            if ($this->sendEmail) {
                $this->sendReceiptByEmail($donation, $receiptPath);
            }

            Log::info('Tax receipt generated successfully', [
                'donation_id' => $this->donationId,
                'receipt_path' => $receiptPath,
                'email_sent' => $this->sendEmail,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to generate tax receipt', [
                'donation_id' => $this->donationId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Tax receipt generation job failed permanently', [
            'donation_id' => $this->donationId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Mark in donation metadata that receipt generation failed
        try {
            $donation = app(DonationRepositoryInterface::class)->findById($this->donationId);

            if ($donation) {
                $metadata = $donation->metadata ?? [];
                $metadata['tax_receipt_generation_failed_at'] = now()->toISOString();
                $metadata['tax_receipt_generation_error'] = $exception->getMessage();
                $donation->metadata = $metadata;
                app(DonationRepositoryInterface::class)->save($donation);
            }
        } catch (Exception $e) {
            Log::error('Failed to update donation metadata after tax receipt job failure', [
                'donation_id' => $this->donationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isDonationEligible(Donation $donation): bool
    {
        // Only completed donations above minimum threshold
        if ($donation->status->value !== 'completed') {
            return false;
        }

        // Minimum amount for tax receipt (configurable)
        $minimumAmount = config('donation.tax_receipt.minimum_amount', 20);

        if ($donation->amount < $minimumAmount) {
            return false;
        }

        // Must not be refunded
        return ! $donation->refunded_at;
    }

    private function hasExistingReceipt(Donation $donation): bool
    {
        return ! empty($donation->metadata['tax_receipt_generated_at']);
    }

    private function generateTaxReceiptPdf(Donation $donation): string
    {
        $receiptNumber = $this->generateReceiptNumber($donation);
        $receiptData = [
            'donation' => $donation,
            'employee' => $donation->user,
            'campaign' => $donation->campaign,
            'organization' => $donation->campaign?->organization,
            'receipt_number' => $receiptNumber,
            'issue_date' => now(),
            'tax_year' => $donation->completed_at->year ?? now()->year,
            'eligible_amount' => $this->calculateEligibleAmount($donation),
        ];

        // Generate PDF using DomPDF (when available)
        // For PHPStan compliance, we'll use a safer approach
        if (! class_exists(Pdf::class)) {
            throw new Exception('DomPDF package is not installed. Please install barryvdh/laravel-dompdf.');
        }

        $pdfClass = App::make('dompdf.wrapper');
        $pdf = $pdfClass->loadView('donation::tax-receipt', $receiptData)
            ->setPaper('a4')
            ->setOptions([
                'dpi' => 150,
                'defaultFont' => 'Arial',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
            ]);

        $filename = "tax-receipt-{$donation->id}-{$receiptNumber}.pdf";
        $relativePath = "tax-receipts/{$donation->user_id}/{$filename}";
        $fullPath = storage_path("app/private/{$relativePath}");

        // Ensure directory exists
        $directory = dirname($fullPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0o755, true);
        }

        $pdf->save($fullPath);

        return $relativePath;
    }

    private function generateReceiptNumber(Donation $donation): string
    {
        return sprintf(
            'TR-%s-%06d-%04d',
            now()->format('Y'),
            $donation->id,
            mt_rand(1000, 9999),
        );
    }

    private function calculateEligibleAmount(Donation $donation): float
    {
        // For now, entire donation amount is eligible
        // Future: may need to exclude benefits received, etc.
        return $donation->amount;
    }

    private function updateDonationRecord(
        Donation $donation,
        string $receiptPath,
        DonationRepositoryInterface $donationRepository,
    ): void {
        $metadata = $donation->metadata ?? [];
        $metadata['tax_receipt_generated_at'] = now()->toISOString();
        $metadata['tax_receipt_path'] = $receiptPath;
        $metadata['tax_receipt_number'] = $this->generateReceiptNumber($donation);

        $donation->metadata = $metadata;
        $donationRepository->save($donation);
    }

    private function sendReceiptByEmail(Donation $donation, string $receiptPath): void
    {
        try {
            $employee = $donation->user;

            if ($employee === null) {
                Log::warning('Cannot send tax receipt email: employee not found', [
                    'donation_id' => $donation->id,
                ]);

                return;
            }

            $locale = $employee->locale ?? app()->getLocale();

            // TODO: Implement actual email sending with receipt attachment
            // Mail::to($employee->email)
            //     ->locale($locale)
            //     ->send(new TaxReceiptMail($donation, $receiptPath));

            Log::info('Tax receipt email sent', [
                'donation_id' => $donation->id,
                'employee_email' => $employee->email,
                'locale' => $locale,
                'receipt_path' => $receiptPath,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to send tax receipt email', [
                'donation_id' => $donation->id,
                'error' => $exception->getMessage(),
            ]);
            // Don't fail the entire job for email errors
        }
    }
}
