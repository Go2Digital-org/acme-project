<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Audit\Domain\Model\Audit;
use Modules\Audit\Domain\Repository\AuditRepositoryInterface;

class EloquentAuditRepository implements AuditRepositoryInterface
{
    public function find(int $id): ?Audit
    {
        return Audit::find($id);
    }

    public function findById(int $id): ?Audit
    {
        return $this->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Audit
    {
        return Audit::create($data);
    }

    /** @return Builder<Audit> */
    public function newQuery(): Builder
    {
        return Audit::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Audit>
     */
    public function getAllPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Audit::with(['auditable', 'user'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['model_type'])) {
            $query->where('auditable_type', $filters['model_type']);
        }

        if (isset($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, Audit>
     */
    public function getByModel(string $modelType, int $modelId, int $perPage = 15): LengthAwarePaginator
    {
        return Audit::with(['user'])
            ->forModel($modelType, $modelId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, Audit>
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Audit::with(['auditable'])
            ->byUser($userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, Audit>
     */
    public function getRecentActivity(int $days = 7, int $limit = 50): Collection
    {
        return Audit::with(['auditable', 'user'])
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function getActivityStats(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return [
            'total_changes' => Audit::where('created_at', '>=', $startDate)->count(),
            'by_event' => Audit::where('created_at', '>=', $startDate)
                ->select('event', DB::raw('count(*) as count'))
                ->groupBy('event')
                ->pluck('count', 'event')
                ->toArray(),
            'by_model' => Audit::where('created_at', '>=', $startDate)
                ->select('auditable_type', DB::raw('count(*) as count'))
                ->groupBy('auditable_type')
                ->pluck('count', 'auditable_type')
                ->toArray(),
            'most_active_users' => Audit::where('created_at', '>=', $startDate)
                ->whereNotNull('user_id')
                ->select('user_id', 'user_type', DB::raw('count(*) as count'))
                ->groupBy('user_id', 'user_type')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get()
                ->toArray(),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Audit>
     */
    public function searchAudits(string $search, int $perPage = 15): LengthAwarePaginator
    {
        return Audit::with(['auditable', 'user'])
            ->where(function ($query) use ($search): void {
                $query->where('auditable_type', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhere('tags', 'like', "%{$search}%")
                    ->orWhereRaw("JSON_SEARCH(old_values, 'one', ?) IS NOT NULL", ["%{$search}%"])
                    ->orWhereRaw("JSON_SEARCH(new_values, 'one', ?) IS NOT NULL", ["%{$search}%"]);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, array{model_type: string, model_id: int, changes_count: int}>
     */
    public function getModelsWithMostChanges(int $limit = 10): Collection
    {
        return Audit::select('auditable_type as model_type', 'auditable_id as model_id', DB::raw('count(*) as changes_count'))
            ->groupBy('auditable_type', 'auditable_id')
            ->orderBy('changes_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($item): array => [
                'model_type' => (string) $item['model_type'],
                'model_id' => (int) $item['model_id'],
                'changes_count' => (int) $item['changes_count'],
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserActivityHeatmap(int $userId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $activities = Audit::byUser($userId)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('count(*) as count')
            )
            ->groupBy('date', 'hour')
            ->get();

        $heatmap = [];
        foreach ($activities as $activity) {
            $date = (string) $activity['date'];
            $hour = (int) $activity['hour'];
            $count = (int) $activity['count'];
            $heatmap[$date][$hour] = $count;
        }

        return $heatmap;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Audit>
     */
    public function exportAudits(array $filters = []): Collection
    {
        $query = Audit::with(['auditable', 'user']);

        if (isset($filters['model_type'])) {
            $query->where('auditable_type', $filters['model_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function deleteOldAudits(int $days = 365): int
    {
        return Audit::where('created_at', '<', now()->subDays($days))->delete();
    }
}
