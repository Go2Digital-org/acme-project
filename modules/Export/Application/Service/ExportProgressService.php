<?php

declare(strict_types=1);

namespace Modules\Export\Application\Service;

use Illuminate\Support\Facades\Redis;
use Modules\Export\Domain\Model\ExportJob;
use Modules\Export\Domain\Model\ExportProgress;
use Modules\Export\Domain\Repository\ExportJobRepositoryInterface;
use Modules\Export\Domain\ValueObject\ExportId;
use Modules\Export\Domain\ValueObject\ExportProgress as ExportProgressValueObject;

final readonly class ExportProgressService
{
    private const REDIS_KEY_PREFIX = 'export:progress:';

    private const BATCH_UPDATE_THRESHOLD = 5; // Update every 5% progress

    public function __construct(
        private ExportJobRepositoryInterface $repository
    ) {}

    public function updateProgress(
        ExportId $exportId,
        int $processedRecords,
        int $totalRecords,
        string $message = ''
    ): void {
        $percentage = $totalRecords > 0
            ? (int) round(($processedRecords / $totalRecords) * 100)
            : 0;

        $progress = ExportProgressValueObject::create(
            percentage: $percentage,
            message: $message ?: "Processing record {$processedRecords} of {$totalRecords}",
            processedRecords: $processedRecords,
            totalRecords: $totalRecords
        );

        // Update Redis cache for real-time updates
        $this->updateRedisProgress($exportId, $progress);

        // Update database periodically (batch updates)
        if ($this->shouldUpdateDatabase($exportId, $percentage)) {
            $this->updateDatabaseProgress($exportId, $progress);
        }

        // Log detailed progress
        $this->logProgress($exportId, $progress);
    }

    public function getProgress(ExportId $exportId): ?ExportProgressValueObject
    {
        // Try Redis first for real-time data
        $redisProgress = $this->getRedisProgress($exportId);
        if ($redisProgress instanceof \Modules\Export\Domain\ValueObject\ExportProgress) {
            return $redisProgress;
        }

        // Fallback to database
        $exportJob = $this->repository->findByExportId($exportId);

        return $exportJob?->getCurrentProgressValueObject();
    }

    public function clearProgress(ExportId $exportId): void
    {
        $redisKey = self::REDIS_KEY_PREFIX . $exportId->toString();
        Redis::del($redisKey);
    }

    /**
     * @return array<string, ExportProgressValueObject>
     */
    public function getAllActiveProgress(): array
    {
        $pattern = self::REDIS_KEY_PREFIX . '*';
        $keys = Redis::keys($pattern);

        $progressData = [];
        foreach ($keys as $key) {
            $exportId = str_replace(self::REDIS_KEY_PREFIX, '', $key);
            $data = Redis::hgetall($key);

            if ($data) {
                $progressData[$exportId] = ExportProgressValueObject::create(
                    percentage: (int) $data['percentage'],
                    message: $data['message'],
                    processedRecords: (int) $data['processed_records'],
                    totalRecords: (int) $data['total_records']
                );
            }
        }

        return $progressData;
    }

    public function initializeProgress(ExportId $exportId, int $totalRecords): void
    {
        $progress = ExportProgressValueObject::create(
            percentage: 0,
            message: 'Starting export...',
            processedRecords: 0,
            totalRecords: $totalRecords
        );

        $this->updateRedisProgress($exportId, $progress);
        $this->updateDatabaseProgress($exportId, $progress);
    }

    private function updateRedisProgress(ExportId $exportId, ExportProgressValueObject $progress): void
    {
        $redisKey = self::REDIS_KEY_PREFIX . $exportId->toString();

        Redis::hmset($redisKey, [
            'percentage' => $progress->percentage,
            'message' => $progress->message,
            'processed_records' => $progress->processedRecords,
            'total_records' => $progress->totalRecords,
            'updated_at' => now()->toISOString(),
        ]);

        // Set expiry to cleanup stale data
        Redis::expire($redisKey, 86400); // 24 hours
    }

    private function getRedisProgress(ExportId $exportId): ?ExportProgressValueObject
    {
        $redisKey = self::REDIS_KEY_PREFIX . $exportId->toString();
        $data = Redis::hgetall($redisKey);

        if (empty($data)) {
            return null;
        }

        return ExportProgressValueObject::create(
            percentage: (int) $data['percentage'],
            message: $data['message'],
            processedRecords: (int) $data['processed_records'],
            totalRecords: (int) $data['total_records']
        );
    }

    private function shouldUpdateDatabase(ExportId $exportId, int $percentage): bool
    {
        static $lastUpdates = [];

        $key = $exportId->toString();
        $lastPercentage = $lastUpdates[$key] ?? 0;

        $shouldUpdate = ($percentage - $lastPercentage) >= self::BATCH_UPDATE_THRESHOLD
            || $percentage === 100
            || $percentage === 0;

        if ($shouldUpdate) {
            $lastUpdates[$key] = $percentage;
        }

        return $shouldUpdate;
    }

    private function updateDatabaseProgress(ExportId $exportId, ExportProgressValueObject $progress): void
    {
        $exportJob = $this->repository->findByExportId($exportId);

        if ($exportJob instanceof ExportJob) {
            $exportJob->updateProgress($progress);
            $this->repository->store($exportJob);
        }
    }

    private function logProgress(ExportId $exportId, ExportProgressValueObject $progress): void
    {
        ExportProgress::create([
            'export_id' => $exportId->toString(),
            'percentage' => $progress->percentage,
            'message' => $progress->message,
            'processed_records' => $progress->processedRecords,
            'total_records' => $progress->totalRecords,
        ]);
    }
}
