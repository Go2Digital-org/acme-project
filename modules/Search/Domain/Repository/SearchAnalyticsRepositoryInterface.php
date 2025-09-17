<?php

declare(strict_types=1);

namespace Modules\Search\Domain\Repository;

interface SearchAnalyticsRepositoryInterface
{
    /**
     * Track a search query.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function trackSearch(
        string $query,
        int $resultCount,
        float $processingTime,
        ?int $userId = null,
        array $metadata = [],
    ): void;

    /**
     * Track a search click.
     */
    public function trackClick(
        string $query,
        string $resultId,
        int $position,
        ?int $userId = null,
    ): void;

    /**
     * Get search analytics for a date range.
     *
     * @return array<array{date: string, searches: int, unique_users: int, avg_results: float}>
     */
    public function getAnalytics(string $startDate, string $endDate): array;

    /**
     * Get top performing queries.
     *
     * @return array<array{query: string, count: int, avg_clicks: float, ctr: float}>
     */
    public function getTopQueries(int $limit = 10, string $period = 'day'): array;

    /**
     * Get queries with no results.
     *
     * @return array<array{query: string, count: int}>
     */
    public function getNoResultQueries(int $limit = 10): array;

    /**
     * Get click-through rate for searches.
     */
    public function getClickThroughRate(string $period = 'day'): float;

    /**
     * Get average search ranking position.
     */
    public function getAveragePosition(): float;

    /**
     * Clean up old analytics data.
     */
    public function cleanup(int $daysToKeep = 90): int;
}
