<?php

declare(strict_types=1);

namespace Modules\Audit\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Audit statistics read model for dashboard and reporting purposes.
 */
class AuditStatsReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        int $statsId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($statsId, $data, $version);
        $this->setCacheTtl(900); // 15 minutes for stats
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'audit_stats',
            'stats:audit',
            'period:' . $this->getPeriodKey(),
        ]);
    }

    // Period Information
    public function getStartDate(): ?string
    {
        return $this->get('start_date');
    }

    public function getEndDate(): ?string
    {
        return $this->get('end_date');
    }

    public function getGroupBy(): ?string
    {
        return $this->get('group_by');
    }

    private function getPeriodKey(): string
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();
        $groupBy = $this->getGroupBy();

        return md5(($start ?? '') . ($end ?? '') . ($groupBy ?? ''));
    }

    public function getFormattedPeriod(): string
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        if (! $start && ! $end) {
            return 'All time';
        }

        if ($start && $end) {
            $startTimestamp = strtotime($start);
            $endTimestamp = strtotime($end);
            if ($startTimestamp !== false && $endTimestamp !== false) {
                return date('M j, Y', $startTimestamp) . ' - ' . date('M j, Y', $endTimestamp);
            }
        }

        if ($start) {
            $startTimestamp = strtotime($start);
            if ($startTimestamp !== false) {
                return 'Since ' . date('M j, Y', $startTimestamp);
            }
        }

        if ($end !== null && $end !== '' && $end !== '0') {
            $endTimestamp = strtotime($end);
            if ($endTimestamp !== false) {
                return 'Until ' . date('M j, Y', $endTimestamp);
            }
        }

        return 'Custom period';
    }

    // Basic Statistics
    public function getTotalAudits(): int
    {
        return (int) $this->get('total_audits', 0);
    }

    public function getUniqueUsers(): int
    {
        return (int) $this->get('unique_users', 0);
    }

    public function getUniqueEntities(): int
    {
        return (int) $this->get('unique_entities', 0);
    }

    public function getRecentActivity(): int
    {
        return (int) $this->get('recent_activity', 0);
    }

    // Event Breakdown
    /**
     * @return array<string, int>
     */
    public function getEventBreakdown(): array
    {
        return $this->get('event_breakdown', []);
    }

    public function getCreatedEvents(): int
    {
        return (int) ($this->getEventBreakdown()['created'] ?? 0);
    }

    public function getUpdatedEvents(): int
    {
        return (int) ($this->getEventBreakdown()['updated'] ?? 0);
    }

    public function getDeletedEvents(): int
    {
        return (int) ($this->getEventBreakdown()['deleted'] ?? 0);
    }

    public function getLoginEvents(): int
    {
        return (int) ($this->getEventBreakdown()['login'] ?? 0);
    }

    public function getFailedLoginEvents(): int
    {
        return (int) ($this->getEventBreakdown()['failed_login'] ?? 0);
    }

    public function getMostCommonEvent(): ?string
    {
        $events = $this->getEventBreakdown();

        if ($events === []) {
            return null;
        }

        $maxValue = max($events);
        $keys = array_keys($events, $maxValue);

        return $keys[0];
    }

    // Entity Type Breakdown
    /**
     * @return array<string, int>
     */
    public function getEntityTypeBreakdown(): array
    {
        return $this->get('entity_type_breakdown', []);
    }

    public function getMostAuditedEntityType(): ?string
    {
        $entityTypes = $this->getEntityTypeBreakdown();

        if ($entityTypes === []) {
            return null;
        }

        $maxValue = max($entityTypes);
        $keys = array_keys($entityTypes, $maxValue);

        return $keys[0];
    }

    public function getEntityTypeCount(): int
    {
        return count($this->getEntityTypeBreakdown());
    }

    // Time Series Data
    /**
     * @return array<string, int>
     */
    public function getTimeSeriesData(): array
    {
        return $this->get('time_series_data', []);
    }

    public function getPeakActivityPeriod(): ?string
    {
        $timeSeriesData = $this->getTimeSeriesData();

        if ($timeSeriesData === []) {
            return null;
        }

        $maxValue = max($timeSeriesData);
        $keys = array_keys($timeSeriesData, $maxValue);

        return $keys[0];
    }

    public function getPeakActivityCount(): int
    {
        $timeSeriesData = $this->getTimeSeriesData();

        if ($timeSeriesData === []) {
            return 0;
        }

        return max($timeSeriesData);
    }

    public function getAverageActivityPerPeriod(): float
    {
        $timeSeriesData = $this->getTimeSeriesData();

        if ($timeSeriesData === []) {
            return 0.0;
        }

        return round(array_sum($timeSeriesData) / count($timeSeriesData), 2);
    }

    // Activity Trends
    public function getGrowthRate(): float
    {
        $timeSeriesData = $this->getTimeSeriesData();

        if (count($timeSeriesData) < 2) {
            return 0.0;
        }

        $values = array_values($timeSeriesData);
        $firstPeriod = reset($values);
        $lastPeriod = end($values);

        if ($firstPeriod <= 0) {
            return 0.0;
        }

        return round((($lastPeriod - $firstPeriod) / $firstPeriod) * 100, 2);
    }

    public function getTrend(): string
    {
        $growthRate = $this->getGrowthRate();

        if ($growthRate > 10) {
            return 'increasing';
        }

        if ($growthRate < -10) {
            return 'decreasing';
        }

        return 'stable';
    }

    // Security Metrics
    public function getSecurityEvents(): int
    {
        $securityEvents = [
            'failed_login',
            'password_reset',
            'permission_changed',
            'role_changed',
            'account_locked',
            'suspicious_activity',
        ];

        $eventBreakdown = $this->getEventBreakdown();
        $total = 0;

        foreach ($securityEvents as $event) {
            $total += $eventBreakdown[$event] ?? 0;
        }

        return $total;
    }

    public function getFailureRate(): float
    {
        $totalEvents = $this->getTotalAudits();
        $failedLogins = $this->getFailedLoginEvents();

        if ($totalEvents <= 0) {
            return 0.0;
        }

        return round(($failedLogins / $totalEvents) * 100, 2);
    }

    public function getSecurityScore(): float
    {
        $score = 100.0;

        // Deduct for high failure rate
        $failureRate = $this->getFailureRate();
        $score -= min($failureRate * 2, 30);

        // Deduct for high security event ratio
        $securityEventRatio = $this->getTotalAudits() > 0
            ? ($this->getSecurityEvents() / $this->getTotalAudits()) * 100
            : 0;
        $score -= min($securityEventRatio, 40);

        return max($score, 0.0);
    }

    // Top Users by Activity
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopUsers(): array
    {
        return $this->get('top_users', []);
    }

    /** @return array<string, mixed>|null */
    public function getMostActiveUser(): ?array
    {
        $topUsers = $this->getTopUsers();

        return $topUsers === [] ? null : reset($topUsers);
    }

    // Top Entities by Changes
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopEntities(): array
    {
        return $this->get('top_entities', []);
    }

    /** @return array<string, mixed>|null */
    public function getMostChangedEntity(): ?array
    {
        $topEntities = $this->getTopEntities();

        return $topEntities === [] ? null : reset($topEntities);
    }

    // Activity Distribution
    /**
     * @return array<string, int>
     */
    public function getHourlyDistribution(): array
    {
        return $this->get('hourly_distribution', []);
    }

    /**
     * @return array<string, int>
     */
    public function getDailyDistribution(): array
    {
        return $this->get('daily_distribution', []);
    }

    public function getPeakActivityHour(): int
    {
        $hourlyDistribution = $this->getHourlyDistribution();

        if ($hourlyDistribution === []) {
            return 0;
        }

        $maxValue = max($hourlyDistribution);
        $keys = array_keys($hourlyDistribution, $maxValue);

        return (int) $keys[0];
    }

    public function getPeakActivityDay(): string
    {
        $dailyDistribution = $this->getDailyDistribution();

        if ($dailyDistribution === []) {
            return '';
        }

        $maxValue = max($dailyDistribution);
        $keys = array_keys($dailyDistribution, $maxValue);

        return $keys[0];
    }

    // Comparison Metrics
    /** @return array<string, mixed> */
    public function getComparisonMetrics(): array
    {
        $current = $this->getTotalAudits();
        $previous = (int) $this->get('previous_period_total', 0);

        $change = $current - $previous;
        $changePercent = $previous > 0 ? round(($change / $previous) * 100, 1) : 0;

        return [
            'current_period' => $current,
            'previous_period' => $previous,
            'absolute_change' => $change,
            'percent_change' => $changePercent,
            'trend' => $changePercent > 0 ? 'up' : ($changePercent < 0 ? 'down' : 'flat'),
        ];
    }

    // Health Indicators
    /** @return array<string, mixed> */
    public function getHealthIndicators(): array
    {
        return [
            'activity_level' => $this->getActivityLevel(),
            'security_score' => $this->getSecurityScore(),
            'failure_rate' => $this->getFailureRate(),
            'growth_rate' => $this->getGrowthRate(),
            'user_engagement' => $this->getUserEngagementScore(),
        ];
    }

    private function getActivityLevel(): string
    {
        $recentActivity = $this->getRecentActivity();
        $totalAudits = $this->getTotalAudits();

        if ($totalAudits <= 0) {
            return 'inactive';
        }

        $recentRatio = $recentActivity / $totalAudits;

        if ($recentRatio > 0.1) {
            return 'very_active';
        }

        if ($recentRatio > 0.05) {
            return 'active';
        }

        if ($recentRatio > 0.01) {
            return 'moderate';
        }

        return 'low';
    }

    private function getUserEngagementScore(): float
    {
        $uniqueUsers = $this->getUniqueUsers();
        $totalAudits = $this->getTotalAudits();

        if ($uniqueUsers <= 0 || $totalAudits <= 0) {
            return 0.0;
        }

        // Average activities per user
        $avgActivitiesPerUser = $totalAudits / $uniqueUsers;

        // Score based on engagement level
        if ($avgActivitiesPerUser >= 50) {
            return 90.0;
        }

        if ($avgActivitiesPerUser >= 20) {
            return 75.0;
        }

        if ($avgActivitiesPerUser >= 10) {
            return 60.0;
        }

        if ($avgActivitiesPerUser >= 5) {
            return 40.0;
        }

        return 20.0;
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toSummaryArray(): array
    {
        return [
            'period' => $this->getFormattedPeriod(),
            'total_audits' => $this->getTotalAudits(),
            'unique_users' => $this->getUniqueUsers(),
            'unique_entities' => $this->getUniqueEntities(),
            'recent_activity' => $this->getRecentActivity(),
            'most_common_event' => $this->getMostCommonEvent(),
            'most_audited_entity_type' => $this->getMostAuditedEntityType(),
            'peak_activity_period' => $this->getPeakActivityPeriod(),
            'peak_activity_count' => $this->getPeakActivityCount(),
            'average_activity_per_period' => $this->getAverageActivityPerPeriod(),
            'growth_rate' => $this->getGrowthRate(),
            'trend' => $this->getTrend(),
            'security_score' => $this->getSecurityScore(),
            'failure_rate' => $this->getFailureRate(),
            'peak_activity_hour' => $this->getPeakActivityHour(),
            'peak_activity_day' => $this->getPeakActivityDay(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDetailedArray(): array
    {
        return array_merge($this->toSummaryArray(), [
            'event_breakdown' => $this->getEventBreakdown(),
            'entity_type_breakdown' => $this->getEntityTypeBreakdown(),
            'time_series_data' => $this->getTimeSeriesData(),
            'top_users' => $this->getTopUsers(),
            'top_entities' => $this->getTopEntities(),
            'hourly_distribution' => $this->getHourlyDistribution(),
            'daily_distribution' => $this->getDailyDistribution(),
            'comparison_metrics' => $this->getComparisonMetrics(),
            'health_indicators' => $this->getHealthIndicators(),
            'security_events' => $this->getSecurityEvents(),
        ]);
    }
}
