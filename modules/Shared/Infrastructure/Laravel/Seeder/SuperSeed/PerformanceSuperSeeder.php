<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Seeder\SuperSeed;

use DateTime;
use Faker\Factory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * High-Performance Base Seeder for Trillion-Scale Data Generation
 *
 * Features:
 * - Memory efficient batch processing
 * - Raw SQL inserts for maximum speed
 * - Intelligent batch sizing based on record complexity
 * - Memory monitoring and cleanup
 * - Progress tracking with real-time metrics
 */
abstract class PerformanceSuperSeeder
{
    protected BatchProcessor $batchProcessor;

    // Performance tuning parameters
    protected int $minBatchSize = 1000;

    protected int $maxBatchSize = 100000;

    protected int $targetMemoryUsage = 100 * 1024 * 1024; // 100MB per batch

    protected int $recordsProcessed = 0;

    protected int $targetRecords = 0;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(protected PerformanceMonitor $monitor, protected array $options = [])
    {
        $this->batchProcessor = new BatchProcessor($this->getTableName());

        // Override defaults from options
        $this->minBatchSize = $this->options['min_batch_size'] ?? $this->minBatchSize;
        $this->maxBatchSize = $this->options['max_batch_size'] ?? $this->maxBatchSize;
        $this->targetMemoryUsage = $this->options['target_memory'] ?? $this->targetMemoryUsage;
    }

    /**
     * Calculate optimal number of batches based on record count and complexity
     */
    public function calculateOptimalBatches(int $recordCount): int
    {
        $this->targetRecords = $recordCount;

        $optimalBatchSize = $this->calculateOptimalBatchSize();

        return (int) ceil($recordCount / $optimalBatchSize);
    }

    /**
     * Execute next batch with intelligent sizing and performance monitoring
     */
    public function executeNextBatch(): int
    {
        if ($this->isComplete()) {
            return 0;
        }

        $remainingRecords = $this->targetRecords - $this->recordsProcessed;
        $batchSize = min($remainingRecords, $this->calculateDynamicBatchSize());

        $batchData = $this->generateBatchData($batchSize);

        // Use raw insert for maximum performance
        $insertedCount = $this->batchProcessor->insertBatch($batchData);

        $this->recordsProcessed += $insertedCount;

        return $insertedCount;
    }

    /**
     * Check if seeding is complete
     */
    public function isComplete(): bool
    {
        return $this->recordsProcessed >= $this->targetRecords;
    }

    /**
     * Calculate optimal batch size based on current memory usage and record complexity
     */
    protected function calculateOptimalBatchSize(): int
    {
        $recordSize = $this->estimateRecordSize();
        $maxRecordsPerBatch = (int) ($this->targetMemoryUsage / $recordSize);

        return max(
            $this->minBatchSize,
            min($this->maxBatchSize, $maxRecordsPerBatch)
        );
    }

    /**
     * Calculate dynamic batch size based on current performance metrics
     */
    protected function calculateDynamicBatchSize(): int
    {
        $baseSize = $this->calculateOptimalBatchSize();

        // Adjust based on current memory usage
        $currentMemory = memory_get_usage(true);
        $memoryPressure = $currentMemory / (512 * 1024 * 1024); // Relative to 512MB limit

        if ($memoryPressure > 0.8) {
            // High memory pressure - reduce batch size
            $baseSize = (int) ($baseSize * 0.5);
        }

        if ($memoryPressure < 0.3) {
            // Low memory pressure - increase batch size
            $baseSize = (int) ($baseSize * 1.5);
        }

        // Adjust based on recent performance
        $metrics = $this->monitor->getCurrentMetrics();
        if (isset($metrics['avg_batch_time']) && $metrics['avg_batch_time'] > 2.0) {
            // Slow batches - reduce size
            $baseSize = (int) ($baseSize * 0.8);
        }

        return max(
            $this->minBatchSize,
            min($this->maxBatchSize, $baseSize)
        );
    }

    /**
     * Generate batch data ready for raw insert
     * Must be implemented by concrete seeders
     *
     * @return list<array<string, mixed>>
     */
    abstract protected function generateBatchData(int $batchSize): array;

    /**
     * Get the target table name for raw inserts
     * Must be implemented by concrete seeders
     */
    abstract protected function getTableName(): string;

    /**
     * Estimate memory size of a single record in bytes
     * Should be overridden by concrete seeders for accuracy
     */
    protected function estimateRecordSize(): int
    {
        // Default estimate - override in concrete classes
        return 1024; // 1KB per record
    }

    /**
     * Get progress information
     */
    /**
     * @return array<string, mixed>
     */
    public function getProgress(): array
    {
        return [
            'processed' => $this->recordsProcessed,
            'target' => $this->targetRecords,
            'percentage' => $this->targetRecords > 0 ?
                round(($this->recordsProcessed / $this->targetRecords) * 100, 2) : 0,
            'remaining' => $this->targetRecords - $this->recordsProcessed,
        ];
    }

    /**
     * Pre-batch hook for custom preparation
     */
    protected function prepareBatch(): void
    {
        // Override in concrete classes if needed
    }

    /**
     * Post-batch hook for custom cleanup
     */
    protected function finalizeBatch(): void
    {
        // Override in concrete classes if needed
    }

    /**
     * Generate realistic fake data for seeding
     * Optimized for memory efficiency
     */
    /**
     * @param  array<string, mixed>  $params
     */
    protected function generateFakeData(string $type, array $params = []): mixed
    {
        static $faker = null;

        if ($faker === null) {
            $faker = Factory::create();
        }

        return match ($type) {
            'name' => $faker->name(),
            'email' => $faker->unique()->safeEmail(),
            'phone' => $faker->phoneNumber(),
            'address' => $faker->address(),
            'company' => $faker->company(),
            'text' => $faker->text($params['length'] ?? 160),
            'date' => $faker->dateTimeBetween($params['start'] ?? '-2 years', $params['end'] ?? 'now'),
            'number' => $faker->numberBetween($params['min'] ?? 1, $params['max'] ?? 1000),
            'decimal' => $faker->randomFloat($params['decimals'] ?? 2, $params['min'] ?? 1, $params['max'] ?? 1000),
            'boolean' => $faker->boolean($params['chance'] ?? 50),
            'uuid' => $faker->uuid(),
            'url' => $faker->url(),
            'password' => $faker->password(),
            default => throw new InvalidArgumentException("Unknown fake data type: {$type}")
        };
    }

    /**
     * Create timestamp array for database records
     *
     * @return array{created_at: string, updated_at: string}
     */
    protected function createTimestamps(?DateTime $date = null): array
    {
        $timestamp = $date?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s');

        return [
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    /**
     * Generate sequential IDs to avoid database auto-increment overhead
     */
    protected function generateSequentialId(): int
    {
        static $idCounter = 0;
        static $maxExistingId = null;

        if ($maxExistingId === null) {
            $maxExistingId = DB::table($this->getTableName())->max('id') ?? 0;
            $idCounter = $maxExistingId;
        }

        return ++$idCounter;
    }
}
