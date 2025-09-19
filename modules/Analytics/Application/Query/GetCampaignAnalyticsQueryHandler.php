<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Query;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Analytics\Application\ReadModel\CampaignAnalyticsReadModel;
use Modules\Analytics\Application\Service\WidgetDataAggregationService;
use Modules\Analytics\Domain\ValueObject\TimeRange;
use Psr\Log\LoggerInterface;

class GetCampaignAnalyticsQueryHandler
{
    public function __construct(
        private readonly WidgetDataAggregationService $aggregationService,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(GetCampaignAnalyticsQuery $query): ?CampaignAnalyticsReadModel
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

            // Collect analytics data
            $analyticsData = $this->collectAnalyticsData($query, $timeRange, $filters);

            // Create read model
            $readModel = new CampaignAnalyticsReadModel(
                campaignId: $query->campaignId ?? 0,
                data: $analyticsData,
                version: (string) time()
            );

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->info('Campaign analytics query processed', [
                'campaign_id' => $query->campaignId,
                'organization_id' => $query->organizationId,
                'time_range' => $query->timeRange,
                'processing_time_ms' => $processingTime,
            ]);

            return $readModel;
        } catch (Exception $e) {
            $this->logger->error('Failed to process campaign analytics query', [
                'campaign_id' => $query->campaignId,
                'organization_id' => $query->organizationId,
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
    private function collectAnalyticsData(GetCampaignAnalyticsQuery $query, TimeRange $timeRange, array $filters): array
    {
        $data = [];

        // Always include basic performance metrics
        $data['performance'] = $this->aggregationService->aggregateCampaignStats($timeRange, $filters);

        // Include donation metrics if requested or if no specific metrics requested
        if ($query->metrics === [] || in_array('donations', $query->metrics)) {
            $data['donations'] = $this->aggregationService->aggregateDonationStats($timeRange, $filters);
        }

        // Include trends if requested
        if ($query->includeTrends) {
            $data['donation_trends'] = $this->aggregationService->aggregateDonationTrends(
                $timeRange,
                $query->granularity ?? 'day',
                $filters
            );
        }

        // Include donor information if requested
        if ($query->metrics === [] || in_array('donors', $query->metrics)) {
            $data['top_donors'] = $this->aggregationService->aggregateTopDonors($timeRange, 10, $filters);
        }

        // Include engagement metrics if requested
        if (in_array('engagement', $query->metrics)) {
            $data['engagement'] = $this->collectEngagementMetrics($query, $timeRange);
        }

        // Include conversion metrics if requested
        if (in_array('conversion', $query->metrics)) {
            $data['conversion'] = $this->collectConversionMetrics($query, $timeRange);
        }

        // Include breakdowns if requested
        if ($query->includeBreakdowns) {
            $data['breakdowns'] = $this->collectBreakdowns($query, $timeRange);
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
    private function collectEngagementMetrics(GetCampaignAnalyticsQuery $query, TimeRange $timeRange): array
    {
        $baseQuery = DB::table('analytics_events')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->when($query->campaignId, fn ($q) => $q->where('campaign_id', $query->campaignId))
            ->when($query->organizationId, fn ($q) => $q->where('organization_id', $query->organizationId));

        $totalViews = (clone $baseQuery)->where('event_type', 'campaign_view')->count();
        $uniqueViews = (clone $baseQuery)->where('event_type', 'campaign_view')->distinct('user_id')->count();
        $shareClicks = (clone $baseQuery)->where('event_type', 'campaign_share')->count();
        $bookmarks = (clone $baseQuery)->where('event_type', 'campaign_bookmark')->count();

        return [
            'total_views' => $totalViews,
            'unique_views' => $uniqueViews,
            'share_clicks' => $shareClicks,
            'bookmarks' => $bookmarks,
            'engagement_rate' => $uniqueViews > 0 ? ($shareClicks + $bookmarks) / $uniqueViews * 100 : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectConversionMetrics(GetCampaignAnalyticsQuery $query, TimeRange $timeRange): array
    {
        // Get campaign views and donations for conversion calculation
        $viewsQuery = DB::table('analytics_events')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->where('event_type', 'campaign_view')
            ->when($query->campaignId, fn ($q) => $q->where('campaign_id', $query->campaignId))
            ->when($query->organizationId, fn ($q) => $q->where('organization_id', $query->organizationId));

        $donationsQuery = DB::table('donations')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->where('status', 'completed')
            ->when($query->campaignId, fn ($q) => $q->where('campaign_id', $query->campaignId))
            ->when($query->organizationId, fn ($q) => $q->whereIn('user_id', function ($sub) use ($query): void {
                $sub->select('id')->from('users')->where('organization_id', $query->organizationId);
            }));

        $uniqueViewers = $viewsQuery->distinct('user_id')->count();
        $donorCount = $donationsQuery->distinct('user_id')->count();
        $conversionRate = $uniqueViewers > 0 ? ($donorCount / $uniqueViewers) * 100 : 0;

        return [
            'unique_viewers' => $uniqueViewers,
            'donors' => $donorCount,
            'conversion_rate' => $conversionRate,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectBreakdowns(GetCampaignAnalyticsQuery $query, TimeRange $timeRange): array
    {
        $breakdowns = [];

        // Donation amount breakdowns
        $amountBreakdowns = DB::table('donations')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->where('status', 'completed')
            ->when($query->campaignId, fn ($q) => $q->where('campaign_id', $query->campaignId))
            ->selectRaw("
                CASE 
                    WHEN amount < 25 THEN 'Under €25'
                    WHEN amount < 50 THEN '€25-49'
                    WHEN amount < 100 THEN '€50-99'
                    WHEN amount < 250 THEN '€100-249'
                    ELSE '€250+'
                END as amount_range,
                COUNT(*) as count,
                SUM(amount) as total_amount
            ")
            ->groupBy('amount_range')
            ->orderByRaw('MIN(amount)')
            ->get();

        $breakdowns['donation_amounts'] = $amountBreakdowns->toArray();

        // Payment method breakdown
        $paymentMethods = DB::table('donations')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->where('status', 'completed')
            ->when($query->campaignId, fn ($q) => $q->where('campaign_id', $query->campaignId))
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('payment_method')
            ->orderByDesc('count')
            ->get();

        $breakdowns['payment_methods'] = $paymentMethods->toArray();

        return $breakdowns;
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

        $currentStats = $this->aggregationService->aggregateCampaignStats($timeRange, $filters);
        $previousStats = $this->aggregationService->aggregateCampaignStats($previousRange, $filters);

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
}
