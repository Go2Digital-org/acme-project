<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Service;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Analytics\Domain\ValueObject\MetricValue;
use Modules\Analytics\Domain\ValueObject\TimeRange;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Modules\Organization\Domain\Model\Organization;
use Psr\Log\LoggerInterface;

final readonly class WidgetDataAggregationService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * Aggregate donation statistics efficiently.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function aggregateDonationStats(TimeRange $timeRange, array $filters = []): array
    {
        $startTime = microtime(true);

        $baseQuery = $this->buildDonationBaseQuery($timeRange, $filters);

        // Use single query with multiple aggregations for performance
        $stats = $baseQuery
            ->selectRaw('
                COUNT(*) as total_donations,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount,
                COUNT(DISTINCT user_id) as unique_donors
            ')
            ->first();

        // Get previous period data for comparison
        $previousRange = $this->getPreviousPeriod($timeRange);
        $previousStats = $this->buildDonationBaseQuery($previousRange, $filters)
            ->selectRaw('
                COUNT(*) as total_donations,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount
            ')
            ->first();

        // Handle null results with defaults
        if (! $stats) {
            $stats = (object) [
                'total_donations' => 0,
                'total_amount' => 0.0,
                'average_amount' => 0.0,
                'min_amount' => 0.0,
                'max_amount' => 0.0,
                'unique_donors' => 0,
            ];
        }

        if (! $previousStats) {
            $previousStats = (object) [
                'total_donations' => 0,
                'total_amount' => 0.0,
                'average_amount' => 0.0,
            ];
        }

        // PHPStan assertions for dynamic object properties
        assert(property_exists($stats, 'total_donations'));
        assert(property_exists($stats, 'total_amount'));
        assert(property_exists($stats, 'average_amount'));
        assert(property_exists($stats, 'min_amount'));
        assert(property_exists($stats, 'max_amount'));
        assert(property_exists($stats, 'unique_donors'));
        assert(property_exists($previousStats, 'total_donations'));
        assert(property_exists($previousStats, 'total_amount'));
        assert(property_exists($previousStats, 'average_amount'));

        $computeTime = microtime(true) - $startTime;
        $this->logger->info('Donation stats aggregated', [
            'time_range' => $timeRange->label,
            'compute_time' => round($computeTime * 1000, 2) . 'ms',
            'total_donations' => $stats->total_donations,
        ]);

        return [
            'total_donations' => MetricValue::count(
                (int) $stats->total_donations,
                'Total Donations',
                (int) $previousStats->total_donations,
            ),
            'total_amount' => MetricValue::currency(
                (float) $stats->total_amount,
                'Total Amount',
                'EUR',
                (float) $previousStats->total_amount,
            ),
            'average_amount' => MetricValue::currency(
                (float) $stats->average_amount,
                'Average Donation',
                'EUR',
                (float) $previousStats->average_amount,
            ),
            'min_amount' => MetricValue::currency((float) $stats->min_amount, 'Minimum Donation', 'EUR'),
            'max_amount' => MetricValue::currency((float) $stats->max_amount, 'Maximum Donation', 'EUR'),
            'unique_donors' => MetricValue::count((int) $stats->unique_donors, 'Unique Donors'),
            'metadata' => [
                'compute_time_ms' => round($computeTime * 1000, 2),
                'query_count' => 2,
            ],
        ];
    }

    /**
     * Aggregate campaign performance efficiently.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function aggregateCampaignStats(TimeRange $timeRange, array $filters = []): array
    {
        $startTime = microtime(true);

        $baseQuery = $this->buildCampaignBaseQuery($timeRange, $filters);

        // Complex aggregation with subqueries for campaign performance
        $stats = $baseQuery
            ->selectRaw('
                COUNT(*) as total_campaigns,
                COUNT(CASE WHEN status = "active" THEN 1 END) as active_campaigns,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_campaigns,
                COUNT(CASE WHEN current_amount >= target_amount THEN 1 END) as successful_campaigns,
                SUM(target_amount) as total_target,
                SUM(current_amount) as total_raised,
                AVG(current_amount / NULLIF(target_amount, 0) * 100) as average_completion_rate
            ')
            ->first();

        // Handle null results with defaults
        if (! $stats) {
            $stats = (object) [
                'total_campaigns' => 0,
                'active_campaigns' => 0,
                'completed_campaigns' => 0,
                'successful_campaigns' => 0,
                'total_target' => 0.0,
                'total_raised' => 0.0,
                'average_completion_rate' => 0.0,
            ];
        }

        // PHPStan assertions for dynamic object properties
        assert(property_exists($stats, 'total_campaigns'));
        assert(property_exists($stats, 'active_campaigns'));
        assert(property_exists($stats, 'completed_campaigns'));
        assert(property_exists($stats, 'successful_campaigns'));
        assert(property_exists($stats, 'total_target'));
        assert(property_exists($stats, 'total_raised'));
        assert(property_exists($stats, 'average_completion_rate'));

        $successRate = $stats->total_campaigns > 0
            ? ($stats->successful_campaigns / $stats->total_campaigns) * 100
            : 0;

        $computeTime = microtime(true) - $startTime;

        return [
            'total_campaigns' => MetricValue::count((int) $stats->total_campaigns, 'Total Campaigns'),
            'active_campaigns' => MetricValue::count((int) $stats->active_campaigns, 'Active Campaigns'),
            'completed_campaigns' => MetricValue::count((int) $stats->completed_campaigns, 'Completed Campaigns'),
            'successful_campaigns' => MetricValue::count((int) $stats->successful_campaigns, 'Successful Campaigns'),
            'total_target' => MetricValue::currency((float) $stats->total_target, 'Total Target Amount', 'EUR'),
            'total_raised' => MetricValue::currency((float) $stats->total_raised, 'Total Raised', 'EUR'),
            'success_rate' => MetricValue::percentage($successRate, 'Success Rate'),
            'average_completion_rate' => MetricValue::percentage(
                (float) $stats->average_completion_rate,
                'Average Completion Rate',
            ),
            'metadata' => [
                'compute_time_ms' => round($computeTime * 1000, 2),
                'query_count' => 1,
            ],
        ];
    }

    /**
     * Aggregate top performers with efficient ranking.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function aggregateTopDonors(TimeRange $timeRange, int $limit = 10, array $filters = []): array
    {
        $startTime = microtime(true);

        $topDonors = DB::table('donations')
            ->join('users', 'donations.user_id', '=', 'users.id')
            ->whereBetween('donations.created_at', [$timeRange->start, $timeRange->end])
            ->where('donations.status', 'completed')
            ->when(! empty($filters['organization_id']), fn (QueryBuilder $query) => $query->where('users.organization_id', $filters['organization_id']))
            ->selectRaw('
                users.id,
                users.name,
                users.avatar_url,
                COUNT(donations.id) as donation_count,
                SUM(donations.amount) as total_amount,
                AVG(donations.amount) as average_amount,
                MAX(donations.created_at) as last_donation_at
            ')
            ->groupBy('users.id', 'users.name', 'users.avatar_url')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get();

        $computeTime = microtime(true) - $startTime;

        return [
            'donors' => $topDonors->map(fn ($donor, $index): array => [
                'rank' => $index + 1,
                'id' => $donor->id,
                'name' => $donor->name,
                'avatar_url' => $donor->avatar_url,
                'donation_count' => (int) $donor->donation_count,
                'total_amount' => MetricValue::currency((float) $donor->total_amount, 'Total Donated', 'EUR'),
                'average_amount' => MetricValue::currency((float) $donor->average_amount, 'Average Donation', 'EUR'),
                'last_donation_at' => $donor->last_donation_at,
            ])->toArray(),
            'metadata' => [
                'compute_time_ms' => round($computeTime * 1000, 2),
                'query_count' => 1,
                'limit' => $limit,
            ],
        ];
    }

    /**
     * Aggregate donation trends over time with configurable intervals.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function aggregateDonationTrends(TimeRange $timeRange, string $interval = 'day', array $filters = []): array
    {
        $startTime = microtime(true);

        $dateFormat = $this->getDateFormat($interval);
        $fillGaps = $this->shouldFillGaps($interval, $timeRange);

        $trends = DB::table('donations')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->where('status', 'completed')
            ->when(! empty($filters['campaign_id']), fn (QueryBuilder $query) => $query->where('campaign_id', $filters['campaign_id']))
            ->selectRaw("
                {$dateFormat} as period,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount
            ")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        // Fill gaps if needed for better visualization
        if ($fillGaps) {
            $trends = $this->fillTrendGaps($trends);
        }

        $computeTime = microtime(true) - $startTime;

        return [
            'data' => $trends->values()->toArray(),
            'metadata' => [
                'compute_time_ms' => round($computeTime * 1000, 2),
                'interval' => $interval,
                'filled_gaps' => $fillGaps,
                'data_points' => $trends->count(),
            ],
        ];
    }

    /**
     * Aggregate organization statistics.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function aggregateOrganizationStats(TimeRange $timeRange, array $filters = []): array
    {
        $startTime = microtime(true);

        $stats = DB::table('organizations')
            ->leftJoin('users', 'organizations.id', '=', 'users.organization_id')
            ->leftJoin('donations', function ($join) use ($timeRange): void {
                $join->on('users.id', '=', 'donations.donor_id')
                    ->whereBetween('donations.created_at', [$timeRange->start, $timeRange->end])
                    ->where('donations.status', 'completed');
            })
            ->when(! empty($filters['organization_id']), fn (QueryBuilder $query) => $query->where('organizations.id', $filters['organization_id']))
            ->selectRaw('
                organizations.id,
                organizations.name,
                COUNT(DISTINCT users.id) as employee_count,
                COUNT(donations.id) as donation_count,
                COALESCE(SUM(donations.amount), 0) as total_donations,
                COUNT(DISTINCT donations.donor_id) as active_donors
            ')
            ->groupBy('organizations.id', 'organizations.name')
            ->orderByDesc('total_donations')
            ->limit(10)
            ->get();

        $computeTime = microtime(true) - $startTime;

        return [
            'organizations' => $stats->map(function ($org, $index): array {
                $participationRate = $org->employee_count > 0
                    ? ($org->active_donors / $org->employee_count) * 100
                    : 0;

                return [
                    'rank' => $index + 1,
                    'id' => $org->id,
                    'name' => $org->name,
                    'employee_count' => (int) $org->employee_count,
                    'donation_count' => (int) $org->donation_count,
                    'total_donations' => MetricValue::currency((float) $org->total_donations, 'Total Donations', 'EUR'),
                    'active_donors' => (int) $org->active_donors,
                    'participation_rate' => MetricValue::percentage($participationRate, 'Participation Rate'),
                ];
            })->toArray(),
            'metadata' => [
                'compute_time_ms' => round($computeTime * 1000, 2),
                'query_count' => 1,
            ],
        ];
    }

    /**
     * Build base query for donations with common filters.
     *
     * @param  array<string, mixed>  $filters
     */
    private function buildDonationBaseQuery(TimeRange $timeRange, array $filters = []): QueryBuilder
    {
        return DB::table('donations')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->where('status', 'completed')
            ->when(! empty($filters['campaign_id']), fn (QueryBuilder $query) => $query->where('campaign_id', $filters['campaign_id']))
            ->when(! empty($filters['organization_id']), fn (QueryBuilder $query) => $query->whereIn('user_id', function ($subquery) use ($filters): void {
                $subquery->select('id')
                    ->from('users')
                    ->where('organization_id', $filters['organization_id']);
            }));
    }

    /**
     * Build base query for campaigns with common filters.
     *
     * @param  array<string, mixed>  $filters
     */
    private function buildCampaignBaseQuery(TimeRange $timeRange, array $filters = []): QueryBuilder
    {
        return DB::table('campaigns')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->when(! empty($filters['organization_id']), fn (QueryBuilder $query) => $query->where('organization_id', $filters['organization_id']))
            ->when(! empty($filters['status']), fn (QueryBuilder $query) => $query->where('status', $filters['status']));
    }

    /**
     * Get previous period for comparison.
     */
    private function getPreviousPeriod(TimeRange $timeRange): TimeRange
    {
        $duration = $timeRange->getDurationInDays();
        $previousStart = $timeRange->start->copy()->subDays($duration);
        $previousEnd = $timeRange->start->copy()->subDay();

        return TimeRange::custom($previousStart, $previousEnd, 'Previous Period');
    }

    /**
     * Get date format for SQL GROUP BY based on interval.
     */
    private function getDateFormat(string $interval): string
    {
        return match ($interval) {
            'hour' => "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')",
            'day' => "DATE_FORMAT(created_at, '%Y-%m-%d')",
            'week' => "DATE_FORMAT(created_at, '%Y-%u')",
            'month' => "DATE_FORMAT(created_at, '%Y-%m')",
            'year' => "DATE_FORMAT(created_at, '%Y')",
            default => "DATE_FORMAT(created_at, '%Y-%m-%d')",
        };
    }

    /**
     * Determine if gaps should be filled for better visualization.
     */
    private function shouldFillGaps(string $interval, TimeRange $timeRange): bool
    {
        $maxDataPoints = 100;
        $estimatedPoints = match ($interval) {
            'hour' => $timeRange->getDurationInDays() * 24,
            'day' => $timeRange->getDurationInDays(),
            'week' => ceil($timeRange->getDurationInDays() / 7),
            'month' => ceil($timeRange->getDurationInDays() / 30),
            default => $timeRange->getDurationInDays(),
        };

        return $estimatedPoints <= $maxDataPoints;
    }

    /**
     * Fill missing data points in trends for better visualization.
     *
     * @param  Collection<string, mixed>  $trends
     * @return Collection<string, mixed>
     */
    private function fillTrendGaps(Collection $trends): Collection
    {
        // This is a simplified implementation
        // In production, you'd want more sophisticated gap filling
        return $trends;
    }
}
