<?php

declare(strict_types=1);

namespace Modules\Export\Domain\Repository;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Export\Domain\Model\ExportJob;
use Modules\Export\Domain\ValueObject\ExportId;
use Modules\Export\Domain\ValueObject\ExportStatus;

interface ExportJobRepositoryInterface
{
    public function save(ExportJob $exportJob): void;

    public function findById(ExportId $exportId): ?ExportJob;

    public function findByIdOrFail(ExportId $exportId): ExportJob;

    /**
     * @return array<int, ExportJob>
     */
    public function findByUser(int $userId, int $limit = 20): array;

    /**
     * @return array<int, ExportJob>
     */
    public function findByOrganization(int $organizationId, int $limit = 20): array;

    /**
     * @return array<int, ExportJob>
     */
    public function findPendingJobs(int $limit = 50): array;

    /**
     * @return array<int, ExportJob>
     */
    public function findProcessingJobs(): array;

    /**
     * @return array<int, ExportJob>
     */
    public function findExpiredJobs(?Carbon $expiredBefore = null): array;

    /**
     * @return array<int, ExportJob>
     */
    public function findJobsForCleanup(Carbon $olderThan): array;

    public function countByStatus(ExportStatus $status, ?int $organizationId = null): int;

    public function getActiveJobsCount(?int $userId = null, ?int $organizationId = null): int;

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ExportJob>
     */
    public function getUserExportsHistory(
        int $userId,
        int $page = 1,
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ExportJob>
     */
    public function getOrganizationExportsHistory(
        int $organizationId,
        int $page = 1,
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator;

    public function findOldestPendingJob(): ?ExportJob;

    public function markAsProcessing(ExportId $exportId, int $totalRecords = 0): bool;

    public function updateProgress(
        ExportId $exportId,
        int $percentage,
        string $message,
        int $processedRecords = 0
    ): bool;

    public function markAsCompleted(
        ExportId $exportId,
        string $filePath,
        int $fileSize
    ): bool;

    public function markAsFailed(ExportId $exportId, string $errorMessage): bool;

    public function deleteExpiredJobs(?Carbon $expiredBefore = null): int;

    public function deleteById(ExportId $exportId): bool;

    public function exists(ExportId $exportId): bool;

    /**
     * @return array<string, mixed>
     */
    public function getStatistics(?int $organizationId = null, ?Carbon $from = null, ?Carbon $to = null): array;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function findSimilarRecentExport(
        int $userId,
        string $resourceType,
        array $filters,
        Carbon $since
    ): ?ExportJob;

    // Additional methods for Application layer support
    public function store(ExportJob $exportJob): void;

    public function findByExportId(ExportId $exportId): ?ExportJob;

    public function countPendingByUser(int $userId): int;

    public function countTodayByUser(int $userId): int;

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ExportJob>
     */
    public function paginate(
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc'
    ): LengthAwarePaginator;

    /**
     * @return array<int, ExportJob>
     */
    public function findExpired(Carbon $cutoffDate, int $limit = 100): array;

    public function delete(ExportJob $exportJob): void;
}
