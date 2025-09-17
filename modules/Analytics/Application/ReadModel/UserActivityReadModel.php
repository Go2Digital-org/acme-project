<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * User Activity read model optimized for user behavior analytics and engagement insights.
 */
class UserActivityReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        int $userId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($userId, $data, $version);
        $this->setCacheTtl(600); // 10 minutes for user activity (more dynamic)
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'user_activity',
            'user:' . $this->id,
            'activity_analytics',
        ]);
    }

    // Core Activity Data
    /**
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return $this->get('summary', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTimeline(): array
    {
        return $this->get('timeline', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAggregateTimeline(): array
    {
        return $this->get('aggregate_timeline', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSessionData(): array
    {
        return $this->get('sessions', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getEngagementMetrics(): array
    {
        return $this->get('engagement', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getBehaviorPatterns(): array
    {
        return $this->get('behavior_patterns', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getActivityByType(): array
    {
        return $this->get('activity_by_type', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMostActiveUsers(): array
    {
        return $this->get('most_active_users', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getComparisons(): array
    {
        return $this->get('comparisons', []);
    }

    // Activity Summary Metrics
    public function getTotalEvents(): int
    {
        $summary = $this->getSummary();

        return $summary['total_events'] ?? 0;
    }

    public function getUniqueUsers(): int
    {
        $summary = $this->getSummary();

        return $summary['unique_users'] ?? 0;
    }

    public function getUniqueSessions(): int
    {
        $summary = $this->getSummary();

        return $summary['unique_sessions'] ?? 0;
    }

    public function getEventsPerUser(): float
    {
        $summary = $this->getSummary();

        return $summary['events_per_user'] ?? 0.0;
    }

    public function getEventsPerSession(): float
    {
        $summary = $this->getSummary();

        return $summary['events_per_session'] ?? 0.0;
    }

    // Session Analysis
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSessionsList(): array
    {
        $sessions = $this->getSessionData();

        return $sessions['sessions'] ?? [];
    }

    public function getAverageSessionDuration(): float
    {
        $sessions = $this->getSessionData();

        return $sessions['avg_session_duration_seconds'] ?? 0.0;
    }

    public function getAverageEventsPerSession(): float
    {
        $sessions = $this->getSessionData();

        return $sessions['avg_events_per_session'] ?? 0.0;
    }

    public function getTotalSessions(): int
    {
        $sessions = $this->getSessionData();

        return $sessions['total_sessions'] ?? 0;
    }

    public function hasSessionData(): bool
    {
        return $this->getSessionsList() !== [];
    }

    // Engagement Analysis
    public function getPageViews(): int
    {
        $engagement = $this->getEngagementMetrics();

        return $engagement['page_views'] ?? 0;
    }

    public function getCampaignViews(): int
    {
        $engagement = $this->getEngagementMetrics();

        return $engagement['campaign_views'] ?? 0;
    }

    public function getDonations(): int
    {
        $engagement = $this->getEngagementMetrics();

        return $engagement['donations'] ?? 0;
    }

    public function getShares(): int
    {
        $engagement = $this->getEngagementMetrics();

        return $engagement['shares'] ?? 0;
    }

    public function getBookmarks(): int
    {
        $engagement = $this->getEngagementMetrics();

        return $engagement['bookmarks'] ?? 0;
    }

    public function getEngagementScore(): float
    {
        $engagement = $this->getEngagementMetrics();

        return $engagement['engagement_score'] ?? 0.0;
    }

    public function hasEngagementData(): bool
    {
        return $this->getEngagementMetrics() !== [];
    }

    // Behavior Pattern Analysis
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getHourlyPattern(): array
    {
        $patterns = $this->getBehaviorPatterns();

        return $patterns['hourly_pattern'] ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getWeeklyPattern(): array
    {
        $patterns = $this->getBehaviorPatterns();

        return $patterns['weekly_pattern'] ?? [];
    }

    public function hasBehaviorPatterns(): bool
    {
        $patterns = $this->getBehaviorPatterns();

        return ! empty($patterns['hourly_pattern']) || ! empty($patterns['weekly_pattern']);
    }

    public function getMostActiveHour(): int
    {
        $hourlyPattern = $this->getHourlyPattern();
        if ($hourlyPattern === []) {
            return 0;
        }

        $maxEvents = 0;
        $mostActiveHour = 0;

        foreach ($hourlyPattern as $pattern) {
            if ($pattern['event_count'] > $maxEvents) {
                $maxEvents = $pattern['event_count'];
                $mostActiveHour = (int) $pattern['hour'];
            }
        }

        return $mostActiveHour;
    }

    public function getMostActiveDayOfWeek(): int
    {
        $weeklyPattern = $this->getWeeklyPattern();
        if ($weeklyPattern === []) {
            return 1; // Sunday as default
        }

        $maxEvents = 0;
        $mostActiveDay = 1;

        foreach ($weeklyPattern as $pattern) {
            if ($pattern['event_count'] > $maxEvents) {
                $maxEvents = $pattern['event_count'];
                $mostActiveDay = (int) $pattern['day_of_week'];
            }
        }

        return $mostActiveDay;
    }

    // Activity Type Analysis
    /** @return array<int, mixed> */
    public function getTopActivityTypes(): array
    {
        $activities = $this->getActivityByType();

        // Sort by count descending and take top 5
        usort($activities, fn ($a, $b) => $b['count'] <=> $a['count']);

        return array_slice($activities, 0, 5);
    }

    public function getActivityTypeCount(): int
    {
        return count($this->getActivityByType());
    }

    public function getTotalUniqueActivities(): int
    {
        $activities = $this->getActivityByType();
        $totalUnique = 0;

        foreach ($activities as $activity) {
            $totalUnique += $activity['unique_users'] ?? 0;
        }

        return $totalUnique;
    }

    // User Timeline (for individual users)
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUserTimeline(): array
    {
        return $this->getTimeline();
    }

    public function getTimelineEventCount(): int
    {
        return count($this->getUserTimeline());
    }

    /** @return array<string, mixed>|null */
    public function getLatestActivity(): ?array
    {
        $timeline = $this->getUserTimeline();

        return $timeline === [] ? null : $timeline[0];
    }

    // Most Active Users (for aggregate views)
    /** @return array<int, array<string, mixed>> */
    public function getMostActiveUsersList(): array
    {
        return $this->getMostActiveUsers();
    }

    public function getMostActiveUsersCount(): int
    {
        return count($this->getMostActiveUsers());
    }

    // Comparison Analysis
    /** @return array<string, mixed> */
    public function getCurrentPeriodActivity(): array
    {
        $comparisons = $this->getComparisons();

        return $comparisons['current_period'] ?? [];
    }

    /** @return array<string, mixed> */
    public function getPreviousPeriodActivity(): array
    {
        $comparisons = $this->getComparisons();

        return $comparisons['previous_period'] ?? [];
    }

    public function hasComparisonData(): bool
    {
        $comparisons = $this->getComparisons();

        return ! empty($comparisons['current_period']) && ! empty($comparisons['previous_period']);
    }

    public function getActivityGrowthRate(): float
    {
        if (! $this->hasComparisonData()) {
            return 0.0;
        }

        $current = $this->getCurrentPeriodActivity();
        $previous = $this->getPreviousPeriodActivity();

        $currentTotal = $current['total_events'] ?? 0;
        $previousTotal = $previous['total_events'] ?? 0;

        if ($previousTotal == 0) {
            return $currentTotal > 0 ? 100.0 : 0.0;
        }

        return (($currentTotal - $previousTotal) / $previousTotal) * 100;
    }

    public function getEngagementGrowthRate(): float
    {
        if (! $this->hasComparisonData()) {
            return 0.0;
        }

        $this->getEngagementScore();

        // For comparison, we'd need previous engagement score
        // This is simplified - in real implementation, you'd track this properly
        return 0.0;
    }

    // Activity Classification
    public function isHighlyActive(): bool
    {
        return $this->getEngagementScore() > 100 && $this->getTotalEvents() > 50;
    }

    public function isModeratelyActive(): bool
    {
        return $this->getEngagementScore() > 25 && $this->getTotalEvents() > 10;
    }

    public function isLowActivity(): bool
    {
        if ($this->getEngagementScore() <= 25) {
            return true;
        }

        return $this->getTotalEvents() <= 10;
    }

    public function getActivityLevel(): string
    {
        if ($this->isHighlyActive()) {
            return 'high';
        }

        if ($this->isModeratelyActive()) {
            return 'moderate';
        }

        return 'low';
    }

    // Summary Methods
    /**
     * Get a comprehensive summary of user activity.
     *
     * @return array<string, mixed>
     */
    public function getActivitySummary(): array
    {
        return [
            'user_id' => $this->getId(),
            'activity_level' => $this->getActivityLevel(),
            'metrics' => [
                'total_events' => $this->getTotalEvents(),
                'unique_sessions' => $this->getUniqueSessions(),
                'engagement_score' => $this->getEngagementScore(),
                'events_per_session' => $this->getEventsPerSession(),
            ],
            'engagement' => [
                'page_views' => $this->getPageViews(),
                'campaign_views' => $this->getCampaignViews(),
                'donations' => $this->getDonations(),
                'shares' => $this->getShares(),
                'bookmarks' => $this->getBookmarks(),
            ],
            'patterns' => [
                'most_active_hour' => $this->getMostActiveHour(),
                'most_active_day' => $this->getMostActiveDayOfWeek(),
                'has_behavior_patterns' => $this->hasBehaviorPatterns(),
            ],
            'sessions' => [
                'total_sessions' => $this->getTotalSessions(),
                'avg_duration_seconds' => $this->getAverageSessionDuration(),
                'avg_events_per_session' => $this->getAverageEventsPerSession(),
                'has_session_data' => $this->hasSessionData(),
            ],
            'timeline' => [
                'event_count' => $this->getTimelineEventCount(),
                'latest_activity' => $this->getLatestActivity(),
            ],
            'comparisons' => [
                'has_data' => $this->hasComparisonData(),
                'activity_growth_rate' => $this->getActivityGrowthRate(),
            ],
        ];
    }

    /**
     * Get key performance indicators for user activity.
     *
     * @return array<string, mixed>
     */
    public function getActivityKPIs(): array
    {
        return [
            'total_events' => $this->getTotalEvents(),
            'engagement_score' => $this->getEngagementScore(),
            'total_sessions' => $this->getTotalSessions(),
            'donations' => $this->getDonations(),
            'campaign_views' => $this->getCampaignViews(),
            'activity_level' => $this->getActivityLevel(),
            'activity_growth_rate' => $this->getActivityGrowthRate(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAnalyticsArray(): array
    {
        return [
            'user_activity' => [
                'user_id' => $this->getId(),
                'activity_summary' => $this->getActivitySummary(),
                'kpis' => $this->getActivityKPIs(),
                'summary' => $this->getSummary(),
                'timeline' => $this->getTimeline(),
                'aggregate_timeline' => $this->getAggregateTimeline(),
                'sessions' => $this->getSessionData(),
                'engagement' => $this->getEngagementMetrics(),
                'behavior_patterns' => $this->getBehaviorPatterns(),
                'activity_by_type' => $this->getActivityByType(),
                'most_active_users' => $this->getMostActiveUsers(),
                'comparisons' => $this->getComparisons(),
            ],
            'metadata' => [
                'version' => $this->getVersion(),
                'cache_ttl' => $this->getCacheTtl(),
                'cache_tags' => $this->getCacheTags(),
            ],
        ];
    }
}
