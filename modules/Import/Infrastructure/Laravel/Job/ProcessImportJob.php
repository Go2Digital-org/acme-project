<?php

declare(strict_types=1);

namespace Modules\Import\Infrastructure\Laravel\Job;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use Modules\Import\Domain\Model\Import;
use Modules\Import\Domain\Model\ImportRecord;
use Modules\Import\Domain\Repository\ImportRepositoryInterface;
use Modules\Import\Domain\ValueObject\ImportRecordStatus;
use Modules\Import\Domain\ValueObject\ImportStatus;
use Modules\Shared\Infrastructure\Audit\AuditService;
use Throwable;

/**
 * ProcessImportJob - Handles CSV import processing with optimized performance for 20K+ records
 *
 * This job implements proper PHPStan level 8 compliance by:
 * - Using proper type casting for Model objects before accessing properties
 * - Implementing null safety when accessing properties
 * - Using getAttribute() method when needed for dynamic property access
 * - Maintaining performance through chunked processing
 */
class ProcessImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 3600; // 1 hour for large imports

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900]; // 1min, 5min, 15min

    private const CHUNK_SIZE = 500; // Process 500 records at a time for optimal performance

    public function __construct(
        private readonly int $importId,
        /** @var array<string, mixed> Additional processing options */
        private readonly array $options = [],
    ) {
        $this->onQueue('imports');
    }

    public function handle(
        ImportRepositoryInterface $importRepository,
        AuditService $auditService,
    ): void {
        Log::info('Starting import processing', [
            'import_id' => $this->importId,
            'job_id' => $this->job?->getJobId(),
            'options' => $this->options,
        ]);

        try {
            DB::transaction(function () use ($importRepository, $auditService): void {
                $this->processImport($importRepository, $auditService);
            });
        } catch (Exception $exception) {
            $this->handleProcessingError($exception, $importRepository);
            throw $exception;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Import processing job failed permanently', [
            'import_id' => $this->importId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark import as failed using proper null safety
        try {
            $importRepository = app(ImportRepositoryInterface::class);
            $import = $importRepository->findById($this->importId);

            if ($import instanceof Import) {
                $importRepository->updateById($this->importId, [
                    'status' => ImportStatus::FAILED,
                    'completed_at' => now(),
                    'errors' => array_merge($import->errors ?? [], [
                        'job_failure' => $exception->getMessage(),
                        'failed_at' => now()->toISOString(),
                    ]),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to update import status after job failure', [
                'import_id' => $this->importId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function processImport(
        ImportRepositoryInterface $importRepository,
        AuditService $auditService,
    ): void {
        // PHPStan Level 8 Fix: Proper type casting before property access
        $import = $importRepository->findById($this->importId);

        if (! $import instanceof Import) {
            Log::error('Import not found for processing', [
                'import_id' => $this->importId,
            ]);
            $this->fail('Import not found');

            return;
        }

        // PHPStan Level 8 Fix: Explicit null safety check before property access
        if ($import->status !== ImportStatus::PENDING) {
            Log::warning('Import is not in pending status', [
                'import_id' => $this->importId,
                'status' => $import->status->value,
            ]);

            return;
        }

        // Mark import as processing with null-safe property access
        $this->updateImportStatus($import, ImportStatus::PROCESSING, $importRepository);

        try {
            // Process the CSV file
            $this->processCsvFile($import, $importRepository);

            // Complete the import
            $this->completeImport($import, $importRepository, $auditService);

        } catch (Throwable $exception) {
            $this->handleProcessingError($exception, $importRepository, $import);
            throw $exception;
        }
    }

    private function processCsvFile(
        Import $import,
        ImportRepositoryInterface $importRepository,
    ): void {
        // PHPStan Level 8 Fix: Use getAttribute() for dynamic property access with null safety
        $filename = $import->getAttribute('filename');
        if (! is_string($filename) || ($filename === '' || $filename === '0')) {
            throw new Exception('Import filename is invalid or missing');
        }

        if (! Storage::exists($filename)) {
            throw new Exception("Import file not found: {$filename}");
        }

        $filePath = Storage::path($filename);

        // Use League CSV for efficient CSV processing
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0); // First row contains headers

        $records = $csv->getRecords();
        $totalRecords = iterator_count($csv->getRecords());

        // Update total records count with proper type safety
        $importRepository->updateById($import->id, [
            'total_records' => $totalRecords,
        ]);

        Log::info('Starting CSV processing', [
            'import_id' => $import->id,
            'total_records' => $totalRecords,
            'chunk_size' => self::CHUNK_SIZE,
        ]);

        $processedCount = 0;
        $successCount = 0;
        $failureCount = 0;
        $batch = [];

        foreach ($records as $rowNumber => $record) {
            $batch[] = [
                'import_id' => $import->id,
                'row_number' => $rowNumber + 1,
                'data' => $record,
                'status' => ImportRecordStatus::PENDING,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Process in chunks for performance optimization
            if (count($batch) >= self::CHUNK_SIZE) {
                $result = $this->processBatch($batch, $import);
                $successCount += $result['success'];
                $failureCount += $result['failure'];
                $processedCount += count($batch);

                // Update progress with proper null safety
                $this->updateImportProgress($import, $processedCount, $successCount, $failureCount, $importRepository);

                $batch = [];
            }
        }

        // Process remaining records
        if ($batch !== []) {
            $result = $this->processBatch($batch, $import);
            $successCount += $result['success'];
            $failureCount += $result['failure'];
            $processedCount += count($batch);

            // Final progress update
            $this->updateImportProgress($import, $processedCount, $successCount, $failureCount, $importRepository);
        }

        Log::info('CSV processing completed', [
            'import_id' => $import->id,
            'processed' => $processedCount,
            'successful' => $successCount,
            'failed' => $failureCount,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $batch
     * @return array{success: int, failure: int}
     */
    private function processBatch(array $batch, Import $import): array
    {
        $successCount = 0;
        $failureCount = 0;

        try {
            // Bulk insert for performance
            ImportRecord::insert($batch);

            // Process each record individually for business logic
            foreach ($batch as $recordData) {
                try {
                    // PHPStan Level 8 Fix: Proper type casting and null safety
                    $rowNumber = (int) $recordData['row_number'];
                    $data = (array) $recordData['data'];

                    $this->processIndividualRecord($import, $rowNumber, $data);
                    $successCount++;

                } catch (Exception $exception) {
                    Log::warning('Failed to process individual record', [
                        'import_id' => $import->id,
                        'row_number' => $recordData['row_number'],
                        'error' => $exception->getMessage(),
                    ]);
                    $failureCount++;

                    // Update record status to failed
                    ImportRecord::where('import_id', $import->id)
                        ->where('row_number', $recordData['row_number'])
                        ->update([
                            'status' => ImportRecordStatus::FAILED,
                            'error_message' => $exception->getMessage(),
                            'updated_at' => now(),
                        ]);
                }
            }
        } catch (Exception $exception) {
            Log::error('Batch processing failed', [
                'import_id' => $import->id,
                'batch_size' => count($batch),
                'error' => $exception->getMessage(),
            ]);
            $failureCount = count($batch);
        }

        return [
            'success' => $successCount,
            'failure' => $failureCount,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function processIndividualRecord(Import $import, int $rowNumber, array $data): void
    {
        // PHPStan Level 8 Fix: Use getAttribute with proper type checking for import type
        $importType = $import->getAttribute('type');
        if (! is_string($importType)) {
            throw new Exception('Import type is not properly set');
        }

        // Process based on import type
        match ($importType) {
            'campaigns' => $this->processCampaignRecord($data, $rowNumber),
            'donations' => $this->processDonationRecord($data, $rowNumber),
            'users' => $this->processUserRecord($data, $rowNumber),
            default => throw new Exception("Unknown import type: {$importType}"),
        };

        // Update record status to success
        ImportRecord::where('import_id', $import->id)
            ->where('row_number', $rowNumber)
            ->update([
                'status' => ImportRecordStatus::SUCCESS,
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function processCampaignRecord(array $data, int $rowNumber): void
    {
        // Validate required fields with proper type checking
        if (! isset($data['title']) || ! is_string($data['title']) || empty($data['title'])) {
            throw new Exception("Row {$rowNumber}: Campaign title is required");
        }

        if (! isset($data['goal_amount']) || ! is_numeric($data['goal_amount'])) {
            throw new Exception("Row {$rowNumber}: Valid goal amount is required");
        }

        // Additional campaign processing logic would go here
        Log::debug('Processing campaign record', [
            'row_number' => $rowNumber,
            'title' => $data['title'],
            'goal_amount' => $data['goal_amount'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function processDonationRecord(array $data, int $rowNumber): void
    {
        // Validate required fields with proper type checking
        if (! isset($data['amount']) || ! is_numeric($data['amount']) || (float) $data['amount'] <= 0) {
            throw new Exception("Row {$rowNumber}: Valid donation amount is required");
        }

        if (! isset($data['campaign_id']) || ! is_numeric($data['campaign_id'])) {
            throw new Exception("Row {$rowNumber}: Valid campaign ID is required");
        }

        // Additional donation processing logic would go here
        Log::debug('Processing donation record', [
            'row_number' => $rowNumber,
            'amount' => $data['amount'],
            'campaign_id' => $data['campaign_id'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function processUserRecord(array $data, int $rowNumber): void
    {
        // Validate required fields with proper type checking
        if (! isset($data['email']) || ! is_string($data['email']) || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Row {$rowNumber}: Valid email address is required");
        }

        if (! isset($data['name']) || ! is_string($data['name']) || empty($data['name'])) {
            throw new Exception("Row {$rowNumber}: User name is required");
        }

        // Additional user processing logic would go here
        Log::debug('Processing user record', [
            'row_number' => $rowNumber,
            'email' => $data['email'],
            'name' => $data['name'],
        ]);
    }

    private function updateImportStatus(
        Import $import,
        ImportStatus $status,
        ImportRepositoryInterface $importRepository,
    ): void {
        $updateData = ['status' => $status];

        // PHPStan Level 8 Fix: Add null safety when updating timestamps
        if ($status === ImportStatus::PROCESSING) {
            $updateData['started_at'] = now();
        } elseif ($status->isFinished()) {
            $updateData['completed_at'] = now();
        }

        $importRepository->updateById($import->id, $updateData);

        Log::info('Import status updated', [
            'import_id' => $import->id,
            'status' => $status->value,
        ]);
    }

    private function updateImportProgress(
        Import $import,
        int $processedRecords,
        int $successfulRecords,
        int $failedRecords,
        ImportRepositoryInterface $importRepository,
    ): void {
        $importRepository->updateById($import->id, [
            'processed_records' => $processedRecords,
            'successful_records' => $successfulRecords,
            'failed_records' => $failedRecords,
        ]);

        // PHPStan Level 8 Fix: Safe property access for progress calculation
        $totalRecords = $import->getAttribute('total_records');
        $progressPercentage = is_numeric($totalRecords) && $totalRecords > 0
            ? round(($processedRecords / (int) $totalRecords) * 100, 2)
            : 0.0;

        Log::info('Import progress updated', [
            'import_id' => $import->id,
            'processed' => $processedRecords,
            'successful' => $successfulRecords,
            'failed' => $failedRecords,
            'progress_percentage' => $progressPercentage,
        ]);
    }

    private function completeImport(
        Import $import,
        ImportRepositoryInterface $importRepository,
        AuditService $auditService,
    ): void {
        // PHPStan Level 8 Fix: Safe property access with proper type checking
        $processedRecords = (int) $import->getAttribute('processed_records');
        $successfulRecords = (int) $import->getAttribute('successful_records');
        $failedRecords = (int) $import->getAttribute('failed_records');

        // Mark import as completed
        $this->updateImportStatus($import, ImportStatus::COMPLETED, $importRepository);

        // Create audit log
        $auditService->log(
            'import_completed',
            'import',
            $import->id,
            [],
            [
                'filename' => $import->getAttribute('original_filename'),
                'type' => $import->getAttribute('type'),
                'total_records' => $import->getAttribute('total_records'),
                'processed_records' => $processedRecords,
                'successful_records' => $successfulRecords,
                'failed_records' => $failedRecords,
                'success_rate' => $processedRecords > 0 ? round(($successfulRecords / $processedRecords) * 100, 2) : 0,
                'processing_completed' => true,
            ],
        );

        Log::info('Import completed successfully', [
            'import_id' => $import->id,
            'processed_records' => $processedRecords,
            'successful_records' => $successfulRecords,
            'failed_records' => $failedRecords,
        ]);
    }

    private function handleProcessingError(
        Throwable $exception,
        ImportRepositoryInterface $importRepository,
        ?Import $import = null,
    ): void {
        Log::error('Import processing error', [
            'import_id' => $this->importId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        if ($import instanceof Import) {
            // PHPStan Level 8 Fix: Safe error handling with null-safe property access
            $existingErrors = $import->getAttribute('errors');
            $errors = is_array($existingErrors) ? $existingErrors : [];

            $importRepository->updateById($import->id, [
                'status' => ImportStatus::FAILED,
                'completed_at' => now(),
                'errors' => array_merge($errors, [
                    'processing_error' => $exception->getMessage(),
                    'error_occurred_at' => now()->toISOString(),
                ]),
            ]);
        }
    }
}
