<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Query;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Analytics\Application\ReadModel\DonationMetricsReadModel;
use Modules\Analytics\Application\Service\WidgetDataAggregationService;
use Modules\Analytics\Domain\ValueObject\TimeRange;
use Modules\Shared\Application\Service\CacheService;
use Psr\Log\LoggerInterface;

class GetDonationMetricsQueryHandler
{
    public function __construct(
        private readonly WidgetDataAggregationService $aggregationService,
        private readonly LoggerInterface $logger,
        private readonly CacheService $cacheService,
    ) {}

    public function handle(GetDonationMetricsQuery $query): ?DonationMetricsReadModel
    {
        try {
            $startTime = microtime(true);

            // Parse time range
            $timeRange = $this->parseTimeRange($query->timeRange);

            // Build filters
            $filters = [];
            if ($query->campaignId) {
                $filters['campaign_id'] = $query->campaignId;
            }
            if ($query->organizationId) {
                $filters['organization_id'] = $query->organizationId;
            }
            if ($query->donorId) {
                $filters['donor_id'] = $query->donorId;
            }

            // Use cached donation metrics with intelligent cache key generation
            $metricsData = $this->cacheService->rememberDonationMetrics(
                $filters,
                $query->timeRange ?? 'last_30_days',
                $query->metrics ?? []
            );

            // Create read model
            $readModel = new DonationMetricsReadModel(
                entityId: $query->donorId ?? ($query->campaignId ?? 0),
                data: $metricsData,
                version: (string) time()
            );

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->info('Donation metrics query processed', [
                'campaign_id' => $query->campaignId,
                'organization_id' => $query->organizationId,
                'donor_id' => $query->donorId,
                'time_range' => $query->timeRange,
                'processing_time_ms' => $processingTime,
            ]);

            return $readModel;
        } catch (Exception $e) {
            $this->logger->error('Failed to process donation metrics query', [
                'campaign_id' => $query->campaignId,
                'organization_id' => $query->organizationId,
                'donor_id' => $query->donorId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    private function parseTimeRange(?string $timeRangeStr): TimeRange
    {
        if (! $timeRangeStr) {
            return TimeRange::last30Days();
        }

        return match ($timeRangeStr) {
            'today' => TimeRange::today(),
            'yesterday' => TimeRange::yesterday(),
            'this_week' => TimeRange::thisWeek(),
            'last_week' => TimeRange::lastWeek(),
            'this_month' => TimeRange::thisMonth(),
            'last_month' => TimeRange::lastMonth(),
            'last_30_days' => TimeRange::last30Days(),
            'last_90_days' => TimeRange::last90Days(),
            'this_year' => TimeRange::thisYear(),
            'last_year' => TimeRange::lastYear(),
            default => TimeRange::last30Days()
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function collectMetricsData(GetDonationMetricsQuery $query, TimeRange $timeRange, array $filters): array
    {
        $data = [];

        // Always include basic donation statistics
        $data['statistics'] = $this->aggregationService->aggregateDonationStats($timeRange, $filters);

        // Include trends if requested
        if ($query->includeTrends) {
            $data['trends'] = $this->aggregationService->aggregateDonationTrends(
                $timeRange,
                $query->granularity ?? 'day',
                $filters
            );
        }

        // Include top donors if not filtering by specific donor
        if (! $query->donorId && ($query->metrics === [] || in_array('donors', $query->metrics))) {
            $data['top_donors'] = $this->aggregationService->aggregateTopDonors($timeRange, 20, $filters);
        }

        // Include segmentation if requested
        if ($query->includeSegmentation) {
            $data['segmentation'] = $this->collectSegmentation($query, $timeRange);
        }

        // Include geographic data if requested
        if (in_array('geographic', $query->metrics)) {
            $data['geographic'] = $this->collectGeographicMetrics($timeRange, $filters);
        }

        // Include temporal patterns if requested
        if (in_array('temporal', $query->metrics)) {
            $data['temporal'] = $this->collectTemporalPatterns($query, $timeRange);
        }

        // Include retention metrics if requested
        if (in_array('retention', $query->metrics)) {
            $data['retention'] = $this->collectRetentionMetrics($query, $timeRange);
        }

        // Include comparisons if requested
        if ($query->includeComparisons) {
            $data['comparisons'] = $this->collectComparisons($timeRange, $filters);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectSegmentation(GetDonationMetricsQuery $query, TimeRange $timeRange): array
    {
        $segmentation = [];

        // Amount-based segmentation
        $amountSegments = DB::table('donations')
            ->when($query->organizationId, function ($q) use ($query): void {
                $q->join('users', 'donations.user_id', '=', 'users.id')
                    ->where('users.organization_id', $query->organizationId);
            })
            ->whereBetween('donations.created_at', [$timeRange->start, $timeRange->end])
            ->where('donations.status', 'completed')
            ->when($query->campaignId, fn ($q) => $q->where('donations.campaign_id', $query->campaignId))
            ->selectRaw("
                CASE 
                    WHEN donations.amount < 25 THEN 'Micro (< €25)'
                    WHEN donations.amount < 100 THEN 'Small (€25-99)'
                    WHEN donations.amount < 500 THEN 'Medium (€100-499)'
                    ELSE 'Major (€500+)'
                END as segment,
                COUNT(*) as donation_count,
                SUM(donations.amount) as total_amount,
                AVG(donations.amount) as avg_amount,
                COUNT(DISTINCT donations.user_id) as unique_donors
            ")
            ->groupBy('segment')
            ->orderByRaw('MIN(donations.amount)')
            ->get();

        $segmentation['amount_segments'] = $amountSegments->toArray();

        // Frequency-based segmentation
        $frequencySegments = DB::table('donations')
            ->join(DB::raw('(
                SELECT user_id, COUNT(*) as donation_count
                FROM donations 
                WHERE created_at BETWEEN ? AND ? AND status = "completed"
                GROUP BY user_id
            ) as donor_frequency'), 'donations.user_id', '=', 'donor_frequency.user_id')
            ->whereBetween('donations.created_at', [$timeRange->start, $timeRange->end])
            ->where('donations.status', 'completed')
            ->when($query->campaignId, fn ($q) => $q->where('donations.campaign_id', $query->campaignId))
            ->selectRaw("
                CASE 
                    WHEN donor_frequency.donation_count = 1 THEN 'One-time'
                    WHEN donor_frequency.donation_count <= 3 THEN 'Occasional'
                    WHEN donor_frequency.donation_count <= 10 THEN 'Regular'
                    ELSE 'Frequent'
                END as frequency_segment,
                COUNT(DISTINCT donations.user_id) as donor_count,
                SUM(donations.amount) as total_amount,
                AVG(donations.amount) as avg_donation
            ")
            ->addBinding([$timeRange->start, $timeRange->end], 'select')
            ->groupBy('frequency_segment')
            ->get();

        $segmentation['frequency_segments'] = $frequencySegments->toArray();

        // Payment method segmentation
        $paymentSegments = DB::table('donations')
            ->when($query->organizationId, function ($q) use ($query): void {
                $q->join('users', 'donations.user_id', '=', 'users.id')
                    ->where('users.organization_id', $query->organizationId);
            })
            ->whereBetween('donations.created_at', [$timeRange->start, $timeRange->end])
            ->where('donations.status', 'completed')
            ->when($query->campaignId, fn ($q) => $q->where('donations.campaign_id', $query->campaignId))
            ->selectRaw('
                donations.payment_method,
                COUNT(*) as donation_count,
                SUM(donations.amount) as total_amount,
                AVG(donations.amount) as avg_amount,
                COUNT(DISTINCT donations.user_id) as unique_donors
            ')
            ->groupBy('donations.payment_method')
            ->orderByDesc('total_amount')
            ->get();

        $segmentation['payment_methods'] = $paymentSegments->toArray();

        return $segmentation;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function collectGeographicMetrics(TimeRange $timeRange, array $filters): array
    {
        // This would require geographic data in user profiles or donation records
        // For now, return organization-based geographic data as a proxy
        $organizationStats = $this->aggregationService->aggregateOrganizationStats($timeRange, $filters);

        return [
            'by_organization' => $organizationStats['organizations'] ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectTemporalPatterns(GetDonationMetricsQuery $query, TimeRange $timeRange): array
    {
        // Hour of day pattern
        $hourlyPattern = DB::table('donations')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->where('status', 'completed')
            ->when($query->campaignId, fn ($q) => $q->where('campaign_id', $query->campaignId))
            ->selectRaw('
                HOUR(created_at) as hour,
                COUNT(*) as donation_count,
                SUM(amount) as total_amount
            ')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Day of week pattern
        $weeklyPattern = DB::table('donations')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->where('status', 'completed')
            ->when($query->campaignId, fn ($q) => $q->where('campaign_id', $query->campaignId))
            ->selectRaw('
                DAYOFWEEK(created_at) as day_of_week,
                COUNT(*) as donation_count,
                SUM(amount) as total_amount
            ')
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->get();

        return [
            'hourly_pattern' => $hourlyPattern->toArray(),
            'weekly_pattern' => $weeklyPattern->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectRetentionMetrics(GetDonationMetricsQuery $query, TimeRange $timeRange): array
    {
        // Calculate donor retention rates
        $retentionQuery = DB::table('donations as d1')
            ->join('donations as d2', 'd1.user_id', '=', 'd2.user_id')
            ->where('d1.status', 'completed')
            ->where('d2.status', 'completed')
            ->whereBetween('d1.created_at', [$timeRange->start, $timeRange->end])
            ->where('d2.created_at', '>', 'd1.created_at')
            ->when($query->campaignId, fn ($q) => $q->where('d1.campaign_id', $query->campaignId))
            ->selectRaw('
                COUNT(DISTINCT d1.user_id) as returning_donors,
                AVG(DATEDIFF(d2.created_at, d1.created_at)) as avg_days_to_return
            ')
            ->first();

        $totalDonors = DB::table('donations')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->where('status', 'completed')
            ->when($query->campaignId, fn ($q) => $q->where('campaign_id', $query->campaignId))
            ->distinct('user_id')
            ->count();

        $returningDonors = $retentionQuery->returning_donors ?? 0;
        $retentionRate = $totalDonors > 0 ? ($returningDonors / $totalDonors) * 100 : 0;

        return [
            'total_donors' => $totalDonors,
            'returning_donors' => $returningDonors,
            'retention_rate' => $retentionRate,
            'avg_days_to_return' => $retentionQuery->avg_days_to_return ?? 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function collectComparisons(TimeRange $timeRange, array $filters): array
    {
        // Get previous period data for comparison
        $duration = $timeRange->getDurationInDays();
        $previousStart = $timeRange->start->copy()->subDays($duration);
        $previousEnd = $timeRange->start->copy()->subDay();
        $previousRange = TimeRange::custom($previousStart, $previousEnd, 'Previous Period');

        $currentStats = $this->aggregationService->aggregateDonationStats($timeRange, $filters);
        $previousStats = $this->aggregationService->aggregateDonationStats($previousRange, $filters);

        return [
            'current_period' => $currentStats,
            'previous_period' => $previousStats,
            'period_comparison' => [
                'start_date' => $timeRange->start->toDateString(),
                'end_date' => $timeRange->end->toDateString(),
                'previous_start' => $previousStart->toDateString(),
                'previous_end' => $previousEnd->toDateString(),
            ],
        ];
    }

    /**
     * Load donation metrics data with optimized queries.
     * This method is called by the CacheService when cache misses occur.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $metrics
     * @return array<string, mixed>
     */
    public function loadDonationMetricsData(array $filters, string $timeRange, array $metrics = []): array
    {
        $timeRangeObj = $this->parseTimeRange($timeRange);

        // Create a mock query object for the existing collection logic
        $query = new GetDonationMetricsQuery(
            campaignId: $filters['campaign_id'] ?? null,
            organizationId: $filters['organization_id'] ?? null,
            donorId: $filters['donor_id'] ?? null,
            timeRange: $timeRange,
            metrics: $metrics,
            includeComparisons: in_array('comparisons', $metrics),
            includeTrends: in_array('trends', $metrics) || $metrics === [],
            includeSegmentation: in_array('segmentation', $metrics),
        );

        return $this->collectMetricsData($query, $timeRangeObj, $filters);
    }

    /**
     * Invalidate donation metrics cache for specific entities.
     */
    public function invalidateCache(?int $campaignId = null, ?int $organizationId = null, ?int $donorId = null): void
    {
        $this->cacheService->invalidateDonationMetrics($campaignId, $organizationId);

        if ($donorId) {
            // Invalidate user-specific donation metrics
            $this->cacheService->flushByTags(['donation_metrics', "user:{$donorId}"]);
        }
    }

    /**
     * Pre-warm cache for common donation metric queries.
     */
    public function warmCommonMetrics(): void
    {
        $commonTimeRanges = ['today', 'this_week', 'this_month', 'last_30_days'];

        foreach ($commonTimeRanges as $timeRange) {
            try {
                // Warm global metrics (no filters)
                $this->cacheService->rememberDonationMetrics([], $timeRange, []);

                // Warm top organizations metrics
                $topOrganizations = $this->getTopOrganizationIds(10);
                foreach ($topOrganizations as $orgId) {
                    $this->cacheService->rememberDonationMetrics(
                        ['organization_id' => $orgId],
                        $timeRange,
                        []
                    );
                }

                // Warm top campaigns metrics
                $topCampaigns = $this->getTopCampaignIds(20);
                foreach ($topCampaigns as $campaignId) {
                    $this->cacheService->rememberDonationMetrics(
                        ['campaign_id' => $campaignId],
                        $timeRange,
                        []
                    );
                }
            } catch (Exception $e) {
                $this->logger->warning('Failed to warm donation metrics cache', [
                    'time_range' => $timeRange,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get cache statistics for donation metrics.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getCacheStatistics(array $filters = [], string $timeRange = 'last_30_days'): array
    {
        $key = $this->buildCacheKey($filters, $timeRange, []);

        return [
            'cache_key' => $key,
            'cached' => $this->cacheService->has($key),
            'cache_tags' => $this->buildCacheTags($filters),
        ];
    }

    /**
     * Get top organization IDs by donation volume.
     *
     * @return array<int>
     */
    private function getTopOrganizationIds(int $limit = 10): array
    {
        return DB::table('donations')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->join('organizations', 'campaigns.organization_id', '=', 'organizations.id')
            ->where('donations.status', 'completed')
            ->where('donations.created_at', '>=', now()->subMonth())
            ->groupBy('organizations.id')
            ->orderByRaw('SUM(donations.amount) DESC')
            ->limit($limit)
            ->pluck('organizations.id')
            ->toArray();
    }

    /**
     * Get top campaign IDs by donation volume.
     *
     * @return array<int>
     */
    private function getTopCampaignIds(int $limit = 20): array
    {
        return DB::table('donations')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subMonth())
            ->groupBy('campaign_id')
            ->orderByRaw('SUM(amount) DESC')
            ->limit($limit)
            ->pluck('campaign_id')
            ->toArray();
    }

    /**
     * Build cache key for donation metrics.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $metrics
     */
    private function buildCacheKey(array $filters, string $timeRange, array $metrics): string
    {
        $filterKey = md5(json_encode($filters) ?: '');
        $metricsKey = md5(json_encode($metrics) ?: '');

        return "donation_metrics:{$timeRange}:filters:{$filterKey}:metrics:{$metricsKey}";
    }

    /**
     * Build cache tags for donation metrics.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string>
     */
    private function buildCacheTags(array $filters): array
    {
        $tags = ['donation_metrics', 'donations'];

        if (isset($filters['campaign_id'])) {
            $tags[] = "campaign:{$filters['campaign_id']}";
        }

        if (isset($filters['organization_id'])) {
            $tags[] = "org:{$filters['organization_id']}";
        }

        if (isset($filters['donor_id'])) {
            $tags[] = "user:{$filters['donor_id']}";
        }

        return $tags;
    }
}
