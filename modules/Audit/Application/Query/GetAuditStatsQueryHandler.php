<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Query;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Audit\Domain\Repository\AuditRepositoryInterface;

class GetAuditStatsQueryHandler
{
    public function __construct(
        private readonly AuditRepositoryInterface $repository
    ) {}

    /** @return array<string, mixed> */
    public function handle(GetAuditStatsQuery $query): array
    {
        $queryBuilder = $this->repository->newQuery();

        if ($query->startDate !== null) {
            $queryBuilder->where('created_at', '>=', $query->startDate);
        }

        if ($query->endDate !== null) {
            $queryBuilder->where('created_at', '<=', $query->endDate);
        }

        if ($query->auditableType !== null) {
            $queryBuilder->where('auditable_type', $query->auditableType);
        }

        if ($query->userId !== null) {
            $queryBuilder->where('user_id', $query->userId);
        }

        // Base statistics
        $totalCount = $queryBuilder->count();
        $uniqueUsers = $queryBuilder->distinct('user_id')->count('user_id');
        $uniqueEntities = $queryBuilder->distinct('auditable_type', 'auditable_id')->count();

        // Event breakdown
        $eventStats = $queryBuilder->groupBy('event')
            ->select('event', DB::raw('count(*) as count'))
            ->pluck('count', 'event')
            ->toArray();

        // Entity type breakdown
        $entityTypeStats = $queryBuilder->groupBy('auditable_type')
            ->select('auditable_type', DB::raw('count(*) as count'))
            ->pluck('count', 'auditable_type')
            ->toArray();

        // Time-based statistics
        $timeSeriesData = [];
        if ($query->groupBy !== null) {
            $dateFormat = match ($query->groupBy) {
                'day' => '%Y-%m-%d',
                'week' => '%Y-%u',
                'month' => '%Y-%m',
                'year' => '%Y',
                default => '%Y-%m-%d',
            };

            $timeSeriesData = $queryBuilder
                ->select(DB::raw("DATE_FORMAT(created_at, '$dateFormat') as period"), DB::raw('count(*) as count'))
                ->groupBy('period')
                ->orderBy('period')
                ->pluck('count', 'period')
                ->toArray();
        }

        // Recent activity (last 24 hours)
        $recentActivity = $this->repository->newQuery()
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->count();

        return [
            'total_audits' => $totalCount,
            'unique_users' => $uniqueUsers,
            'unique_entities' => $uniqueEntities,
            'recent_activity' => $recentActivity,
            'event_breakdown' => $eventStats,
            'entity_type_breakdown' => $entityTypeStats,
            'time_series_data' => $timeSeriesData,
            'period' => [
                'start_date' => $query->startDate,
                'end_date' => $query->endDate,
                'group_by' => $query->groupBy,
            ],
        ];
    }
}
