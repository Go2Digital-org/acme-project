<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\Laravel\Repository;

use Illuminate\Support\Facades\DB;
use Modules\Search\Domain\Repository\SearchAnalyticsRepositoryInterface;

class SearchAnalyticsEloquentRepository implements SearchAnalyticsRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function trackSearch(
        string $query,
        int $resultCount,
        float $processingTime,
        ?int $userId = null,
        array $metadata = [],
    ): void {
        DB::table('search_analytics')->insert([
            'query' => $query,
            'result_count' => $resultCount,
            'processing_time' => $processingTime,
            'user_id' => $userId,
            'metadata' => json_encode($metadata),
            'created_at' => now(),
        ]);
    }

    public function trackClick(
        string $query,
        string $resultId,
        int $position,
        ?int $userId = null,
    ): void {
        DB::table('search_clicks')->insert([
            'query' => $query,
            'result_id' => $resultId,
            'position' => $position,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAnalytics(string $startDate, string $endDate): array
    {
        $analytics = DB::table('search_analytics')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as searches'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users'),
                DB::raw('AVG(result_count) as avg_results'),
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $analytics->map(fn ($item): array => [
            'date' => $item->date,
            'searches' => (int) $item->searches,
            'unique_users' => (int) $item->unique_users,
            'avg_results' => (float) $item->avg_results,
        ])->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function getTopQueries(int $limit = 10, string $period = 'day'): array
    {
        $startDate = match ($period) {
            'hour' => now()->subHour(),
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            default => now()->subDay(),
        };

        $queries = DB::table('search_analytics as sa')
            ->select(
                'sa.query',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(sc.position) as avg_position'),
                DB::raw('COUNT(sc.id) / COUNT(*) * 100 as ctr'),
            )
            ->leftJoin('search_clicks as sc', 'sa.query', '=', 'sc.query')
            ->where('sa.created_at', '>=', $startDate)
            ->groupBy('sa.query')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();

        return $queries->map(fn ($item): array => [
            'query' => $item->query,
            'count' => (int) $item->count,
            'avg_clicks' => (float) ($item->avg_position ?? 0),
            'ctr' => (float) ($item->ctr ?? 0),
        ])->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function getNoResultQueries(int $limit = 10): array
    {
        $queries = DB::table('search_analytics')
            ->select('query', DB::raw('COUNT(*) as count'))
            ->where('result_count', 0)
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();

        return $queries->map(fn ($item): array => [
            'query' => $item->query,
            'count' => (int) $item->count,
        ])->toArray();
    }

    public function getClickThroughRate(string $period = 'day'): float
    {
        $startDate = match ($period) {
            'hour' => now()->subHour(),
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            default => now()->subDay(),
        };

        $searches = DB::table('search_analytics')
            ->where('created_at', '>=', $startDate)
            ->count();

        if ($searches === 0) {
            return 0.0;
        }

        $clicks = DB::table('search_clicks')
            ->where('created_at', '>=', $startDate)
            ->count();

        return round(($clicks / $searches) * 100, 2);
    }

    public function getAveragePosition(): float
    {
        $avgPosition = DB::table('search_clicks')
            ->where('created_at', '>=', now()->subDays(7))
            ->avg('position');

        return (float) ($avgPosition ?? 0);
    }

    public function cleanup(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        $deletedAnalytics = DB::table('search_analytics')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        $deletedClicks = DB::table('search_clicks')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        return $deletedAnalytics + $deletedClicks;
    }
}
