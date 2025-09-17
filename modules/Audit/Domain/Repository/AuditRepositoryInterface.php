<?php

declare(strict_types=1);

namespace Modules\Audit\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Audit\Domain\Model\Audit;

interface AuditRepositoryInterface
{
    public function find(int $id): ?Audit;

    public function findById(int $id): ?Audit;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Audit;

    /** @return Builder<Audit> */
    public function newQuery(): Builder;

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Audit>
     */
    public function getAllPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * @return LengthAwarePaginator<int, Audit>
     */
    public function getByModel(string $modelType, int $modelId, int $perPage = 15): LengthAwarePaginator;

    /**
     * @return LengthAwarePaginator<int, Audit>
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * @return Collection<int, Audit>
     */
    public function getRecentActivity(int $days = 7, int $limit = 50): Collection;

    /**
     * @return array<string, mixed>
     */
    public function getActivityStats(int $days = 30): array;

    /**
     * @return LengthAwarePaginator<int, Audit>
     */
    public function searchAudits(string $search, int $perPage = 15): LengthAwarePaginator;

    /**
     * @return Collection<int, array{model_type: string, model_id: int, changes_count: int}>
     */
    public function getModelsWithMostChanges(int $limit = 10): Collection;

    /**
     * @return array<string, mixed>
     */
    public function getUserActivityHeatmap(int $userId, int $days = 30): array;

    /**
     * @return Collection<int, Audit>
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Audit>
     */
    public function exportAudits(array $filters = []): Collection;

    public function deleteOldAudits(int $days = 365): int;
}
