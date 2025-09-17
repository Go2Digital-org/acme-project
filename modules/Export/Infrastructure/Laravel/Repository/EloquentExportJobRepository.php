<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Laravel\Repository;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Export\Domain\Model\ExportJob;
use Modules\Export\Domain\Repository\ExportJobRepositoryInterface;
use Modules\Export\Domain\ValueObject\ExportId;
use Modules\Export\Domain\ValueObject\ExportStatus;
use Modules\Export\Infrastructure\Laravel\Model\ExportJobEloquent;

class EloquentExportJobRepository implements ExportJobRepositoryInterface
{
    public function __construct(
        private readonly ExportJobEloquent $model
    ) {}

    public function save(ExportJob $exportJob): void
    {
        $this->store($exportJob);
    }

    public function store(ExportJob $exportJob): void
    {
        $eloquentModel = $this->findEloquentByExportId($exportJob->getExportIdValueObject());

        if (! $eloquentModel instanceof ExportJobEloquent) {
            $eloquentModel = new ExportJobEloquent;
        }

        // Use getter methods or attributes from the domain model
        $resourceFilters = $exportJob->getAttribute('resource_filters');
        // Convert array to JSON string if needed (domain model casts as array, eloquent expects json string)
        if (is_array($resourceFilters)) {
            $resourceFilters = json_encode($resourceFilters);
        }

        $eloquentModel->fill([
            'export_id' => $exportJob->getExportIdValueObject()->toString(),
            'user_id' => $exportJob->getAttribute('user_id'),
            'organization_id' => $exportJob->getAttribute('organization_id'),
            'resource_type' => $exportJob->getAttribute('resource_type'),
            'resource_filters' => $resourceFilters,
            'format' => $exportJob->getFormatValueObject()->value,
            'status' => $exportJob->getStatusValueObject()->value,
            'file_path' => $exportJob->getAttribute('file_path'),
            'file_size' => $exportJob->getAttribute('file_size'),
            'error_message' => $exportJob->getAttribute('error_message'),
            'started_at' => $exportJob->getAttribute('started_at'),
            'completed_at' => $exportJob->getAttribute('completed_at'),
            'expires_at' => $exportJob->getAttribute('expires_at'),
            'total_records' => $exportJob->getAttribute('total_records') ?? 0,
            'processed_records' => $exportJob->getAttribute('processed_records') ?? 0,
            'current_percentage' => $exportJob->getAttribute('current_percentage') ?? 0,
            'current_message' => $exportJob->getAttribute('current_message'),
        ]);

        $eloquentModel->save();

        // Update the domain model with the database ID if it's new
        if ($exportJob->getAttribute('id') === null) {
            $exportJob->setAttribute('id', $eloquentModel->id);
        }
    }

    public function findById(ExportId $exportId): ?ExportJob
    {
        return $this->findByExportId($exportId);
    }

    public function findByExportId(ExportId $exportId): ?ExportJob
    {
        $eloquentModel = $this->findEloquentByExportId($exportId);

        return $eloquentModel instanceof ExportJobEloquent ? $this->toDomainModel($eloquentModel) : null;
    }

    public function findByIdOrFail(ExportId $exportId): ExportJob
    {
        $exportJob = $this->findByExportId($exportId);

        if (! $exportJob instanceof ExportJob) {
            throw new ModelNotFoundException("Export job with ID {$exportId->toString()} not found");
        }

        return $exportJob;
    }

    /**
     * @return array<int, ExportJob>
     */
    public function findByUser(int $userId, int $limit = 20): array
    {
        $eloquentModels = $this->model
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $eloquentModels->map(fn (ExportJobEloquent $model): ExportJob => $this->toDomainModel($model))->toArray();
    }

    /**
     * @return array<int, ExportJob>
     */
    public function findByOrganization(int $organizationId, int $limit = 20): array
    {
        $eloquentModels = $this->model
            ->where('organization_id', $organizationId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $eloquentModels->map(fn (ExportJobEloquent $model): ExportJob => $this->toDomainModel($model))->toArray();
    }

    public function findPendingJobs(int $limit = 50): array
    {
        $eloquentModels = $this->model
            ->where('status', ExportStatus::PENDING->value)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        return $eloquentModels->map(fn (ExportJobEloquent $model): ExportJob => $this->toDomainModel($model))->toArray();
    }

    public function findProcessingJobs(): array
    {
        $eloquentModels = $this->model
            ->where('status', ExportStatus::PROCESSING->value)
            ->get();

        return $eloquentModels->map(fn (ExportJobEloquent $model): ExportJob => $this->toDomainModel($model))->toArray();
    }

    public function findExpiredJobs(?Carbon $expiredBefore = null): array
    {
        $cutoffDate = $expiredBefore ?? now();

        $eloquentModels = $this->model
            ->where('expires_at', '<', $cutoffDate)
            ->whereNotIn('status', [ExportStatus::FAILED->value, ExportStatus::CANCELLED->value])
            ->get();

        return $eloquentModels->map(fn (ExportJobEloquent $model): ExportJob => $this->toDomainModel($model))->toArray();
    }

    public function findExpired(Carbon $cutoffDate, int $limit = 100): array
    {
        $eloquentModels = $this->model
            ->where('expires_at', '<', $cutoffDate)
            ->limit($limit)
            ->get();

        return $eloquentModels->map(fn (ExportJobEloquent $model): ExportJob => $this->toDomainModel($model))->toArray();
    }

    public function findJobsForCleanup(Carbon $olderThan): array
    {
        $eloquentModels = $this->model
            ->where('created_at', '<', $olderThan)
            ->whereIn('status', [ExportStatus::COMPLETED->value, ExportStatus::FAILED->value, ExportStatus::CANCELLED->value])
            ->get();

        return $eloquentModels->map(fn (ExportJobEloquent $model): ExportJob => $this->toDomainModel($model))->toArray();
    }

    public function countByStatus(ExportStatus $status, ?int $organizationId = null): int
    {
        $query = $this->model->where('status', $status->value);

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        return $query->count();
    }

    public function countPendingByUser(int $userId): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('status', ExportStatus::PENDING->value)
            ->count();
    }

    public function countTodayByUser(int $userId): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->whereDate('created_at', today())
            ->count();
    }

    public function getActiveJobsCount(?int $userId = null, ?int $organizationId = null): int
    {
        $query = $this->model->whereIn('status', [
            ExportStatus::PENDING->value,
            ExportStatus::PROCESSING->value,
        ]);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        return $query->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ExportJob>
     */
    public function getUserExportsHistory(
        int $userId,
        int $page = 1,
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator {
        $query = $this->model
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        // Apply filters
        $this->applyFilters($query, $filters);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform to domain models
        $paginator->getCollection()->transform(fn ($model): ExportJob => $this->toDomainModel($model));

        /** @var LengthAwarePaginator<int, ExportJob> $paginator */
        return $paginator;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ExportJob>
     */
    public function getOrganizationExportsHistory(
        int $organizationId,
        int $page = 1,
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator {
        $query = $this->model
            ->where('organization_id', $organizationId)
            ->orderBy('created_at', 'desc');

        // Apply filters
        $this->applyFilters($query, $filters);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform to domain models
        $paginator->getCollection()->transform(fn ($model): ExportJob => $this->toDomainModel($model));

        /** @var LengthAwarePaginator<int, ExportJob> $paginator */
        return $paginator;
    }

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
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        // Apply filters
        $this->applyFilters($query, $filters);

        $paginator = $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        // Transform to domain models
        $paginator->getCollection()->transform(fn ($model): ExportJob => $this->toDomainModel($model));

        /** @var LengthAwarePaginator<int, ExportJob> $paginator */
        return $paginator;
    }

    public function findOldestPendingJob(): ?ExportJob
    {
        $eloquentModel = $this->model
            ->where('status', ExportStatus::PENDING->value)
            ->orderBy('created_at', 'asc')
            ->first();

        return $eloquentModel ? $this->toDomainModel($eloquentModel) : null;
    }

    public function markAsProcessing(ExportId $exportId, int $totalRecords = 0): bool
    {
        return $this->model
            ->where('export_id', $exportId->toString())
            ->update([
                'status' => ExportStatus::PROCESSING->value,
                'started_at' => now(),
                'total_records' => $totalRecords,
                'current_message' => 'Starting export processing...',
            ]) > 0;
    }

    public function updateProgress(
        ExportId $exportId,
        int $percentage,
        string $message,
        int $processedRecords = 0
    ): bool {
        return $this->model
            ->where('export_id', $exportId->toString())
            ->update([
                'current_percentage' => $percentage,
                'current_message' => $message,
                'processed_records' => $processedRecords,
            ]) > 0;
    }

    public function markAsCompleted(
        ExportId $exportId,
        string $filePath,
        int $fileSize
    ): bool {
        return $this->model
            ->where('export_id', $exportId->toString())
            ->update([
                'status' => ExportStatus::COMPLETED->value,
                'completed_at' => now(),
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'current_percentage' => 100,
                'current_message' => 'Export completed successfully',
            ]) > 0;
    }

    public function markAsFailed(ExportId $exportId, string $errorMessage): bool
    {
        return $this->model
            ->where('export_id', $exportId->toString())
            ->update([
                'status' => ExportStatus::FAILED->value,
                'completed_at' => now(),
                'error_message' => $errorMessage,
                'current_message' => 'Export failed: ' . $errorMessage,
            ]) > 0;
    }

    public function deleteExpiredJobs(?Carbon $expiredBefore = null): int
    {
        $cutoffDate = $expiredBefore ?? now();

        return $this->model
            ->where('expires_at', '<', $cutoffDate)
            ->delete();
    }

    public function deleteById(ExportId $exportId): bool
    {
        return $this->model
            ->where('export_id', $exportId->toString())
            ->delete() > 0;
    }

    public function delete(ExportJob $exportJob): void
    {
        $this->deleteById($exportJob->getExportIdValueObject());
    }

    public function exists(ExportId $exportId): bool
    {
        return $this->model
            ->where('export_id', $exportId->toString())
            ->exists();
    }

    /**
     * @return array<string, int>
     */
    public function getStatistics(?int $organizationId = null, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = $this->model->newQuery();

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        if ($from instanceof Carbon) {
            $query->where('created_at', '>=', $from);
        }

        if ($to instanceof Carbon) {
            $query->where('created_at', '<=', $to);
        }

        return [
            'total' => $query->count(),
            'pending' => (clone $query)->where('status', ExportStatus::PENDING->value)->count(),
            'processing' => (clone $query)->where('status', ExportStatus::PROCESSING->value)->count(),
            'completed' => (clone $query)->where('status', ExportStatus::COMPLETED->value)->count(),
            'failed' => (clone $query)->where('status', ExportStatus::FAILED->value)->count(),
            'cancelled' => (clone $query)->where('status', ExportStatus::CANCELLED->value)->count(),
        ];
    }

    public function findSimilarRecentExport(
        int $userId,
        string $resourceType,
        array $filters,
        Carbon $since
    ): ?ExportJob {
        $eloquentModel = $this->model
            ->where('user_id', $userId)
            ->where('resource_type', $resourceType)
            ->where('resource_filters', json_encode($filters))
            ->where('created_at', '>=', $since)
            ->where('status', ExportStatus::COMPLETED->value)
            ->orderBy('created_at', 'desc')
            ->first();

        return $eloquentModel ? $this->toDomainModel($eloquentModel) : null;
    }

    private function findEloquentByExportId(ExportId $exportId): ?ExportJobEloquent
    {
        return $this->model
            ->where('export_id', $exportId->toString())
            ->first();
    }

    private function toDomainModel(ExportJobEloquent $eloquentModel): ExportJob
    {
        $domainModel = new ExportJob;

        // Map all attributes from eloquent to domain model
        $domainModel->setRawAttributes($eloquentModel->toArray(), true);
        $domainModel->exists = true;
        $domainModel->wasRecentlyCreated = false;

        return $domainModel;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(mixed $query, array $filters): void
    {
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['resource_type'])) {
            $query->where('resource_type', $filters['resource_type']);
        }

        if (isset($filters['format'])) {
            $query->where('format', $filters['format']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }
    }
}
