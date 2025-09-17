<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Laravel\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Donation\Domain\ValueObject\PaymentMethod;
use Modules\Export\Domain\Repository\ExportJobRepositoryInterface;
use Modules\Export\Domain\ValueObject\ExportFormat;
use Modules\Export\Domain\ValueObject\ExportId;
use Modules\Export\Infrastructure\Export\ChunkedCsvExporter;
use Modules\Export\Infrastructure\Export\ChunkedExcelExporter;
use Modules\Export\Infrastructure\Export\ExportStorageService;
use Modules\Shared\Domain\Export\DonationExportRepositoryInterface;
use Modules\Shared\Domain\ValueObject\DonationStatus;
use Throwable;

/**
 * Queue job for processing donation exports in chunks to handle large datasets efficiently.
 * Features:
 * - Memory-efficient chunked processing (1000 records per chunk)
 * - Progress tracking with 5% increments
 * - Streaming output to prevent memory issues
 * - Failure recovery and cleanup
 * - Support for CSV and Excel formats
 */
class ProcessDonationExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const CHUNK_SIZE = 1000;

    private const PROGRESS_UPDATE_THRESHOLD = 5; // Update progress every 5%

    private const MAX_EXPORT_RECORDS = 100000; // Maximum 100k records per export

    public int $tries = 3;

    public int $timeout = 3600; // 1 hour timeout

    public int $maxExceptions = 3;

    private int $totalRecords = 0;

    private int $processedRecords = 0;

    private int $lastReportedPercentage = 0;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly string $exportId,
        private readonly array $filters,
        private readonly string $format,
        private readonly int $userId,
        private readonly int $organizationId
    ) {
        $this->onQueue('exports');
    }

    public function handle(
        ExportJobRepositoryInterface $exportRepository,
        DonationExportRepositoryInterface $donationRepository,
        ExportStorageService $storageService
    ): void {
        $exportIdVO = ExportId::fromString($this->exportId);
        $formatVO = ExportFormat::from($this->format);

        try {
            Log::info('Starting donation export processing', [
                'export_id' => $this->exportId,
                'format' => $this->format,
                'filters' => $this->filters,
                'user_id' => $this->userId,
                'organization_id' => $this->organizationId,
            ]);

            // Get total count for progress tracking
            $this->totalRecords = $this->getTotalRecords($donationRepository);

            // Check if export exceeds maximum allowed records
            if ($this->totalRecords > self::MAX_EXPORT_RECORDS) {
                $errorMessage = sprintf(
                    'Export exceeds maximum allowed records. Found %d records, maximum allowed is %d. Please apply more filters to reduce the dataset.',
                    $this->totalRecords,
                    self::MAX_EXPORT_RECORDS
                );
                $this->handleFailure($exportRepository, $exportIdVO, $storageService, new Exception($errorMessage));

                return;
            }

            // Mark as processing and update total records count
            $exportRepository->markAsProcessing($exportIdVO, $this->totalRecords);

            if ($this->totalRecords === 0) {
                $this->handleEmptyExport($exportRepository, $exportIdVO, $storageService);

                return;
            }

            // Create temporary file path
            $tempFilePath = $storageService->createTempFilePath($formatVO);

            // Process export based on format
            $filePath = match ($formatVO) {
                ExportFormat::CSV => $this->processCsvExport($donationRepository, $tempFilePath, $exportRepository, $exportIdVO),
                ExportFormat::EXCEL => $this->processExcelExport($donationRepository, $tempFilePath, $exportRepository, $exportIdVO),
                default => throw new Exception("Unsupported export format: {$this->format}")
            };

            // Move file to final location and get file size
            $finalPath = $storageService->storeFinalFile($tempFilePath, $exportIdVO, $formatVO);
            $fileSize = $storageService->getFileSize($finalPath);

            // Mark as completed
            $exportRepository->markAsCompleted($exportIdVO, $finalPath, $fileSize);

            Log::info('Donation export completed successfully', [
                'export_id' => $this->exportId,
                'file_path' => $finalPath,
                'file_size' => $fileSize,
                'records_processed' => $this->processedRecords,
            ]);

        } catch (Exception $exception) {
            $this->handleFailure($exportRepository, $exportIdVO, $storageService, $exception);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Donation export job failed permanently', [
            'export_id' => $this->exportId,
            'exception' => $exception?->getMessage(),
            'filters' => $this->filters,
        ]);

        // Try to mark as failed in database if possible
        try {
            $exportRepository = app(ExportJobRepositoryInterface::class);
            $storageService = app(ExportStorageService::class);

            $exportIdVO = ExportId::fromString($this->exportId);
            $this->handleFailure($exportRepository, $exportIdVO, $storageService, $exception);
        } catch (Throwable $cleanupException) {
            Log::error('Failed to cleanup after export job failure', [
                'export_id' => $this->exportId,
                'cleanup_exception' => $cleanupException->getMessage(),
            ]);
        }
    }

    private function getTotalRecords(DonationExportRepositoryInterface $donationRepository): int
    {
        // Ensure user_id filter is always applied for security
        $filters = array_merge($this->filters, ['user_id' => $this->userId]);

        return $donationRepository->getDonationsWithRelations($filters)->count();
    }

    private function processCsvExport(
        DonationExportRepositoryInterface $donationRepository,
        string $filePath,
        ExportJobRepositoryInterface $exportRepository,
        ExportId $exportId
    ): string {
        $exporter = new ChunkedCsvExporter($filePath);

        $this->processInChunks($donationRepository, $exporter, $exportRepository, $exportId);

        $exporter->close();

        return $filePath;
    }

    private function processExcelExport(
        DonationExportRepositoryInterface $donationRepository,
        string $filePath,
        ExportJobRepositoryInterface $exportRepository,
        ExportId $exportId
    ): string {
        $exporter = new ChunkedExcelExporter($filePath);

        $this->processInChunks($donationRepository, $exporter, $exportRepository, $exportId);

        $exporter->close();

        return $filePath;
    }

    private function processInChunks(
        DonationExportRepositoryInterface $donationRepository,
        ChunkedCsvExporter|ChunkedExcelExporter $exporter,
        ExportJobRepositoryInterface $exportRepository,
        ExportId $exportId
    ): void {
        // Ensure user_id filter is always applied for security
        $filters = array_merge($this->filters, ['user_id' => $this->userId]);
        $query = $donationRepository->getDonationsWithRelations($filters);

        // Write headers
        $exporter->writeHeaders();

        // Process data in chunks
        $query->chunk(self::CHUNK_SIZE, function ($donations) use ($exporter, $exportRepository, $exportId): void {
            // Write chunk data
            $chunkData = [];
            foreach ($donations as $donation) {
                $chunkData[] = $this->transformDonationToArray($donation);
            }

            $exporter->writeRows($chunkData);

            // Update progress
            $this->processedRecords += count($donations);
            $this->updateProgress($exportRepository, $exportId);

            // Prevent memory leaks
            unset($donations, $chunkData);

            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        });
    }

    /**
     * Transform donation model to array for export.
     *
     * @param  Model  $donation  The donation model with dynamic properties
     * @return array<string, mixed>
     */
    private function transformDonationToArray(Model $donation): array
    {
        // Get English title only for the campaign
        $campaignTitle = 'N/A';
        $campaign = $donation->getAttribute('campaign');
        if ($campaign && is_object($campaign)) {
            if (method_exists($campaign, 'getTranslation')) {
                $campaignTitle = $campaign->getTranslation('title', 'en');
            } elseif (method_exists($campaign, 'getAttribute')) {
                $campaignTitle = $campaign->getAttribute('title') ?? 'N/A';
            }
        }

        return [
            'id' => $donation->getAttribute('id'),
            'campaign_id' => $donation->getAttribute('campaign_id'),
            'campaign_title' => $campaignTitle,
            'user_id' => $donation->getAttribute('user_id'),
            'user_name' => $donation->getAttribute('user') ? $donation->getAttribute('user')->name : ($donation->getAttribute('is_anonymous') ? 'Anonymous' : 'N/A'),
            'user_email' => $donation->getAttribute('user') ? $donation->getAttribute('user')->email : 'N/A',
            'amount' => $donation->getAttribute('amount'),
            'currency' => $donation->getAttribute('currency'),
            'payment_method' => $donation->getAttribute('payment_method') instanceof PaymentMethod ? $donation->getAttribute('payment_method')->value : ($donation->getAttribute('payment_method') ?? 'N/A'),
            'payment_gateway' => $donation->getAttribute('payment_gateway') ?? 'N/A',
            'transaction_id' => $donation->getAttribute('transaction_id') ?? 'N/A',
            'status' => $donation->getAttribute('status') instanceof DonationStatus ? $donation->getAttribute('status')->value : ($donation->getAttribute('status') ?? 'N/A'),
            'anonymous' => $donation->getAttribute('anonymous') ? 'Yes' : 'No',
            'recurring' => $donation->getAttribute('recurring') ? 'Yes' : 'No',
            'recurring_frequency' => $donation->getAttribute('recurring_frequency') ?? 'N/A',
            'donated_at' => $donation->getAttribute('donated_at')?->format('Y-m-d H:i:s') ?? 'N/A',
            'processed_at' => $donation->getAttribute('processed_at')?->format('Y-m-d H:i:s') ?? 'N/A',
            'completed_at' => $donation->getAttribute('completed_at')?->format('Y-m-d H:i:s') ?? 'N/A',
            'corporate_match_amount' => $donation->getAttribute('corporate_match_amount') ?? 0,
            'notes' => $donation->getAttribute('notes') ?? 'N/A',
            'created_at' => $donation->getAttribute('created_at')?->format('Y-m-d H:i:s') ?? 'N/A',
            'updated_at' => $donation->getAttribute('updated_at')?->format('Y-m-d H:i:s') ?? 'N/A',
        ];
    }

    private function updateProgress(
        ExportJobRepositoryInterface $exportRepository,
        ExportId $exportId
    ): void {
        if ($this->totalRecords === 0) {
            return;
        }

        $currentPercentage = (int) round(($this->processedRecords / $this->totalRecords) * 100);

        // Only update if we've reached the next threshold
        if ($currentPercentage >= $this->lastReportedPercentage + self::PROGRESS_UPDATE_THRESHOLD
            || $currentPercentage >= 100) {

            $message = "Processing donations... ({$this->processedRecords}/{$this->totalRecords} records)";

            $exportRepository->updateProgress(
                $exportId,
                $currentPercentage,
                $message,
                $this->processedRecords
            );

            $this->lastReportedPercentage = $currentPercentage;

            Log::debug('Export progress updated', [
                'export_id' => $exportId->toString(),
                'percentage' => $currentPercentage,
                'processed' => $this->processedRecords,
                'total' => $this->totalRecords,
            ]);
        }
    }

    private function handleEmptyExport(
        ExportJobRepositoryInterface $exportRepository,
        ExportId $exportId,
        ExportStorageService $storageService
    ): void {
        // Create empty file for consistency
        $formatVO = ExportFormat::from($this->format);
        $tempFilePath = $storageService->createTempFilePath($formatVO);

        // Create empty file based on format
        if ($formatVO === ExportFormat::CSV) {
            $exporter = new ChunkedCsvExporter($tempFilePath);
            $exporter->writeHeaders();
            $exporter->close();
        } else {
            $exporter = new ChunkedExcelExporter($tempFilePath);
            $exporter->writeHeaders();
            $exporter->close();
        }

        $finalPath = $storageService->storeFinalFile($tempFilePath, $exportId, $formatVO);
        $fileSize = $storageService->getFileSize($finalPath);

        $exportRepository->markAsCompleted($exportId, $finalPath, $fileSize);

        Log::info('Empty donation export completed', [
            'export_id' => $exportId->toString(),
            'file_path' => $finalPath,
        ]);
    }

    private function handleFailure(
        ExportJobRepositoryInterface $exportRepository,
        ExportId $exportId,
        ExportStorageService $storageService,
        ?Throwable $exception
    ): void {
        $errorMessage = $exception instanceof Exception ? $exception->getMessage() : 'Unknown error occurred';

        // Mark export as failed
        $exportRepository->markAsFailed($exportId, $errorMessage);

        // Clean up any temporary files
        $storageService->cleanupTempFiles($exportId);

        Log::error('Donation export failed', [
            'export_id' => $exportId->toString(),
            'error' => $errorMessage,
            'processed_records' => $this->processedRecords,
            'total_records' => $this->totalRecords,
        ]);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900]; // 1 minute, 5 minutes, 15 minutes
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'export',
            'donation-export',
            "user:{$this->userId}",
            "organization:{$this->organizationId}",
            "format:{$this->format}",
        ];
    }
}
