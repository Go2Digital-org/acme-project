<?php

declare(strict_types=1);

namespace Modules\Admin\Application\ReadModel;

use DateTimeImmutable;

final readonly class SystemStatusReadModel
{
    /**
     * @param  array<string, mixed>  $healthChecks
     * @param  array<string, mixed>  $performanceMetrics
     */
    public function __construct(
        public string $status, // 'healthy', 'warning', 'critical'
        /** @var array<string, mixed> */
        public array $healthChecks,
        /** @var array<string, mixed> */
        public array $performanceMetrics,
        public QueueStatusReadModel $queueStatus,
        public CacheStatusReadModel $cacheStatus,
        public StorageStatusReadModel $storageStatus,
        public DateTimeImmutable $lastChecked
    ) {}

    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    public function hasWarnings(): bool
    {
        return $this->status === 'warning';
    }

    public function isCritical(): bool
    {
        return $this->status === 'critical';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'health_checks' => $this->healthChecks,
            'performance_metrics' => $this->performanceMetrics,
            'queue_status' => $this->queueStatus->toArray(),
            'cache_status' => $this->cacheStatus->toArray(),
            'storage_status' => $this->storageStatus->toArray(),
            'last_checked' => $this->lastChecked->format('Y-m-d H:i:s'),
        ];
    }
}

final readonly class QueueStatusReadModel
{
    /**
     * @param  array<int, array<string, int|string>>  $queueWorkers
     */
    public function __construct(
        public int $pendingJobs,
        public int $failedJobs,
        /** @var array<int, array<string, int|string>> */
        public array $queueWorkers,
        public string $status
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pending_jobs' => $this->pendingJobs,
            'failed_jobs' => $this->failedJobs,
            'queue_workers' => $this->queueWorkers,
            'status' => $this->status,
        ];
    }
}

final readonly class CacheStatusReadModel
{
    /**
     * @param  array<string, mixed>  $stats
     */
    public function __construct(
        public string $driver,
        public bool $isConnected,
        /** @var array<string, mixed> */
        public array $stats,
        public string $status
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'driver' => $this->driver,
            'is_connected' => $this->isConnected,
            'stats' => $this->stats,
            'status' => $this->status,
        ];
    }
}

final readonly class StorageStatusReadModel
{
    /**
     * @param  array<string, mixed>  $disks
     */
    public function __construct(
        /** @var array<string, mixed> */
        public array $disks,
        public int $totalSpace,
        public int $usedSpace,
        public int $freeSpace,
        public float $usagePercentage
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'disks' => $this->disks,
            'total_space' => $this->totalSpace,
            'used_space' => $this->usedSpace,
            'free_space' => $this->freeSpace,
            'usage_percentage' => $this->usagePercentage,
        ];
    }
}
