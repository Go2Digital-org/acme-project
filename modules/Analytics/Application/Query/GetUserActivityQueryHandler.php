<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Query;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Analytics\Application\ReadModel\UserActivityReadModel;
use Modules\Analytics\Domain\ValueObject\TimeRange;
use Psr\Log\LoggerInterface;

class GetUserActivityQueryHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(GetUserActivityQuery $query): ?UserActivityReadModel
    {
        try {
            $startTime = microtime(true);

            // Parse time range
            $timeRange = $this->parseTimeRange($query->timeRange);

            // Collect user activity data
            $activityData = $this->collectActivityData($query, $timeRange);

            // Create read model
            $readModel = new UserActivityReadModel(
                userId: $query->userId ?? 0,
                data: $activityData,
                version: (string) time()
            );

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->info('User activity query processed', [
                'user_id' => $query->userId,
                'organization_id' => $query->organizationId,
                'time_range' => $query->timeRange,
                'processing_time_ms' => $processingTime,
            ]);

            return $readModel;
        } catch (Exception $e) {
            $this->logger->error('Failed to process user activity query', [
                'user_id' => $query->userId,
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
     * @return array<string, mixed>
     */
    private function collectActivityData(GetUserActivityQuery $query, TimeRange $timeRange): array
    {
        $data = [];

        // Basic activity summary
        $data['summary'] = $this->collectActivitySummary($query, $timeRange);

        // Activity timeline - collect user-specific or aggregate data
        if ($query->userId) {
            $data['timeline'] = $this->collectUserTimeline($query, $timeRange);
        }

        if (! $query->userId) {
            $data['aggregate_timeline'] = $this->collectAggregateTimeline($query, $timeRange);
        }

        // Session data if requested
        if ($query->includeSessionData) {
            $data['sessions'] = $this->collectSessionData($query, $timeRange);
        }

        // Engagement metrics if requested
        if ($query->includeEngagementMetrics) {
            $data['engagement'] = $this->collectEngagementMetrics($query, $timeRange);
        }

        // Behavior patterns if requested
        if ($query->includeBehaviorPatterns) {
            $data['behavior_patterns'] = $this->collectBehaviorPatterns($query, $timeRange);
        }

        // Activity by type
        $data['activity_by_type'] = $this->collectActivityByType($query, $timeRange);

        // Most active users (if not filtering by specific user)
        if (! $query->userId) {
            $data['most_active_users'] = $this->collectMostActiveUsers($query, $timeRange);
        }

        // Comparisons if requested
        if ($query->includeComparisons) {
            $data['comparisons'] = $this->collectComparisons($query, $timeRange);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectActivitySummary(GetUserActivityQuery $query, TimeRange $timeRange): array
    {
        $baseQuery = DB::table('analytics_events')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->when($query->userId, fn ($q) => $q->where('user_id', $query->userId))
            ->when($query->organizationId, fn ($q) => $q->where('organization_id', $query->organizationId))
            ->when($query->activityTypes !== [], fn ($q) => $q->whereIn('event_type', $query->activityTypes));

        $totalEvents = (clone $baseQuery)->count();
        $uniqueUsers = (clone $baseQuery)->distinct('user_id')->count('user_id');
        $uniqueSessions = (clone $baseQuery)->distinct('session_id')->count('session_id');

        return [
            'total_events' => $totalEvents,
            'unique_users' => $uniqueUsers,
            'unique_sessions' => $uniqueSessions,
            'events_per_user' => $uniqueUsers > 0 ? $totalEvents / $uniqueUsers : 0,
            'events_per_session' => $uniqueSessions > 0 ? $totalEvents / $uniqueSessions : 0,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function collectUserTimeline(GetUserActivityQuery $query, TimeRange $timeRange): array
    {
        return DB::table('analytics_events')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->where('user_id', $query->userId)
            ->when($query->activityTypes !== [], fn ($q) => $q->whereIn('event_type', $query->activityTypes))
            ->select([
                'id',
                'event_type',
                'event_name',
                'campaign_id',
                'properties',
                'session_id',
                'created_at',
            ])
            ->orderByDesc('created_at')
            ->limit($query->limit)
            ->get()
            ->toArray();
    }

    /**
     * @return array<int, mixed>
     */
    private function collectAggregateTimeline(GetUserActivityQuery $query, TimeRange $timeRange): array
    {
        $dateFormat = match ($query->granularity) {
            'hour' => "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')",
            'day' => "DATE_FORMAT(created_at, '%Y-%m-%d')",
            'week' => "DATE_FORMAT(created_at, '%Y-%u')",
            'month' => "DATE_FORMAT(created_at, '%Y-%m')",
            default => "DATE_FORMAT(created_at, '%Y-%m-%d')",
        };

        return DB::table('analytics_events')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->when($query->organizationId, fn ($q) => $q->where('organization_id', $query->organizationId))
            ->when($query->activityTypes !== [], fn ($q) => $q->whereIn('event_type', $query->activityTypes))
            ->selectRaw("
                {$dateFormat} as period,
                COUNT(*) as event_count,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT session_id) as unique_sessions
            ")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function collectSessionData(GetUserActivityQuery $query, TimeRange $timeRange): array
    {
        $sessionStats = DB::table('analytics_events')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->when($query->userId, fn ($q) => $q->where('user_id', $query->userId))
            ->when($query->organizationId, fn ($q) => $q->where('organization_id', $query->organizationId))
            ->selectRaw('
                session_id,
                user_id,
                MIN(created_at) as session_start,
                MAX(created_at) as session_end,
                COUNT(*) as event_count,
                TIMESTAMPDIFF(SECOND, MIN(created_at), MAX(created_at)) as duration_seconds
            ')
            ->whereNotNull('session_id')
            ->groupBy('session_id', 'user_id')
            ->having('event_count', '>', 1)
            ->orderByDesc('session_start')
            ->limit($query->limit)
            ->get();

        $avgSessionDuration = $sessionStats->avg('duration_seconds') ?? 0;
        $avgEventsPerSession = $sessionStats->avg('event_count') ?? 0;

        return [
            'sessions' => $sessionStats->toArray(),
            'avg_session_duration_seconds' => $avgSessionDuration,
            'avg_events_per_session' => $avgEventsPerSession,
            'total_sessions' => $sessionStats->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectEngagementMetrics(GetUserActivityQuery $query, TimeRange $timeRange): array
    {
        $engagementQuery = DB::table('analytics_events')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->when($query->userId, fn ($q) => $q->where('user_id', $query->userId))
            ->when($query->organizationId, fn ($q) => $q->where('organization_id', $query->organizationId));

        $pageViews = (clone $engagementQuery)->where('event_type', 'page_view')->count();
        $campaignViews = (clone $engagementQuery)->where('event_type', 'campaign_view')->count();
        $donations = (clone $engagementQuery)->where('event_type', 'donation_completed')->count();
        $shares = (clone $engagementQuery)->where('event_type', 'campaign_share')->count();
        $bookmarks = (clone $engagementQuery)->where('event_type', 'campaign_bookmark')->count();

        $engagementScore = $this->calculateEngagementScore([
            'page_views' => $pageViews,
            'campaign_views' => $campaignViews,
            'donations' => $donations,
            'shares' => $shares,
            'bookmarks' => $bookmarks,
        ]);

        return [
            'page_views' => $pageViews,
            'campaign_views' => $campaignViews,
            'donations' => $donations,
            'shares' => $shares,
            'bookmarks' => $bookmarks,
            'engagement_score' => $engagementScore,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectBehaviorPatterns(GetUserActivityQuery $query, TimeRange $timeRange): array
    {
        // Hour of day activity pattern
        $hourlyPattern = DB::table('analytics_events')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->when($query->userId, fn ($q) => $q->where('user_id', $query->userId))
            ->when($query->organizationId, fn ($q) => $q->where('organization_id', $query->organizationId))
            ->selectRaw('
                HOUR(created_at) as hour,
                COUNT(*) as event_count
            ')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Day of week pattern
        $weeklyPattern = DB::table('analytics_events')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->when($query->userId, fn ($q) => $q->where('user_id', $query->userId))
            ->when($query->organizationId, fn ($q) => $q->where('organization_id', $query->organizationId))
            ->selectRaw('
                DAYOFWEEK(created_at) as day_of_week,
                COUNT(*) as event_count
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
     * @return array<int, mixed>
     */
    private function collectActivityByType(GetUserActivityQuery $query, TimeRange $timeRange): array
    {
        return DB::table('analytics_events')
            ->whereBetween('created_at', [$timeRange->start, $timeRange->end])
            ->when($query->userId, fn ($q) => $q->where('user_id', $query->userId))
            ->when($query->organizationId, fn ($q) => $q->where('organization_id', $query->organizationId))
            ->when($query->activityTypes !== [], fn ($q) => $q->whereIn('event_type', $query->activityTypes))
            ->selectRaw('
                event_type,
                event_name,
                COUNT(*) as count,
                COUNT(DISTINCT user_id) as unique_users
            ')
            ->groupBy('event_type', 'event_name')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    /**
     * @return array<int, mixed>
     */
    private function collectMostActiveUsers(GetUserActivityQuery $query, TimeRange $timeRange): array
    {
        return DB::table('analytics_events')
            ->join('users', 'analytics_events.user_id', '=', 'users.id')
            ->whereBetween('analytics_events.created_at', [$timeRange->start, $timeRange->end])
            ->when($query->organizationId, fn ($q) => $q->where('analytics_events.organization_id', $query->organizationId))
            ->when($query->activityTypes !== [], fn ($q) => $q->whereIn('analytics_events.event_type', $query->activityTypes))
            ->selectRaw('
                users.id,
                users.name,
                users.email,
                COUNT(analytics_events.id) as total_events,
                COUNT(DISTINCT analytics_events.event_type) as event_types,
                MAX(analytics_events.created_at) as last_activity
            ')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_events')
            ->limit(20)
            ->get()
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function collectComparisons(GetUserActivityQuery $query, TimeRange $timeRange): array
    {
        // Get previous period data for comparison
        $duration = $timeRange->getDurationInDays();
        $previousStart = $timeRange->start->copy()->subDays($duration);
        $previousEnd = $timeRange->start->copy()->subDay();

        $currentSummary = $this->collectActivitySummary($query, $timeRange);

        $previousQuery = new GetUserActivityQuery(
            userId: $query->userId,
            organizationId: $query->organizationId,
            timeRange: null, // We'll use the calculated previous range
            activityTypes: $query->activityTypes,
            includeSessionData: false,
            includeEngagementMetrics: false,
            includeBehaviorPatterns: false,
            includeComparisons: false
        );

        $previousRange = TimeRange::custom($previousStart, $previousEnd, 'Previous Period');
        $previousSummary = $this->collectActivitySummary($previousQuery, $previousRange);

        return [
            'current_period' => $currentSummary,
            'previous_period' => $previousSummary,
            'period_comparison' => [
                'start_date' => $timeRange->start->toDateString(),
                'end_date' => $timeRange->end->toDateString(),
                'previous_start' => $previousStart->toDateString(),
                'previous_end' => $previousEnd->toDateString(),
            ],
        ];
    }

    /**
     * @param  array<string, int>  $metrics
     */
    private function calculateEngagementScore(array $metrics): float
    {
        // Weighted scoring system
        $weights = [
            'page_views' => 1,
            'campaign_views' => 2,
            'donations' => 10,
            'shares' => 5,
            'bookmarks' => 3,
        ];

        $score = 0;
        foreach ($metrics as $metric => $count) {
            $score += ($weights[$metric] ?? 1) * $count;
        }

        return $score;
    }
}
