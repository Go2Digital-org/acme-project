<?php

declare(strict_types=1);

namespace Modules\Audit\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * User audit trail read model optimized for user activity analysis.
 */
class UserAuditTrailReadModel extends AbstractReadModel
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
        $this->setCacheTtl(1800); // 30 minutes for user trails
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'user_audit_trail',
            'user:' . $this->id,
            'organization:' . $this->getOrganizationId(),
        ]);
    }

    // User Information
    public function getUserId(): int
    {
        return (int) $this->id;
    }

    public function getUserName(): string
    {
        return $this->get('user_name', '');
    }

    public function getUserEmail(): string
    {
        return $this->get('user_email', '');
    }

    public function getOrganizationId(): ?int
    {
        $orgId = $this->get('organization_id');

        return $orgId ? (int) $orgId : null;
    }

    public function getOrganizationName(): ?string
    {
        return $this->get('organization_name');
    }

    // Activity Statistics
    public function getTotalActivities(): int
    {
        return (int) $this->get('total_activities', 0);
    }

    public function getActivitiesInPeriod(): int
    {
        return (int) $this->get('activities_in_period', 0);
    }

    public function getFirstActivity(): ?string
    {
        return $this->get('first_activity');
    }

    public function getLastActivity(): ?string
    {
        return $this->get('last_activity');
    }

    public function getFormattedFirstActivity(): string
    {
        $firstActivity = $this->getFirstActivity();

        if (! $firstActivity) {
            return '';
        }

        $timestamp = strtotime($firstActivity);

        return $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : '';
    }

    public function getFormattedLastActivity(): string
    {
        $lastActivity = $this->getLastActivity();

        if (! $lastActivity) {
            return '';
        }

        $timestamp = strtotime($lastActivity);

        return $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : '';
    }

    // Activity Breakdown by Event Type
    /**
     * @return array<string, int>
     */
    public function getActivityBreakdown(): array
    {
        return $this->get('activity_breakdown', []);
    }

    public function getCreatedCount(): int
    {
        return (int) ($this->getActivityBreakdown()['created'] ?? 0);
    }

    public function getUpdatedCount(): int
    {
        return (int) ($this->getActivityBreakdown()['updated'] ?? 0);
    }

    public function getDeletedCount(): int
    {
        return (int) ($this->getActivityBreakdown()['deleted'] ?? 0);
    }

    public function getLoginCount(): int
    {
        return (int) ($this->getActivityBreakdown()['login'] ?? 0);
    }

    public function getFailedLoginCount(): int
    {
        return (int) ($this->getActivityBreakdown()['failed_login'] ?? 0);
    }

    // Entity Type Activity
    /**
     * @return array<string, int>
     */
    public function getEntityTypeBreakdown(): array
    {
        return $this->get('entity_type_breakdown', []);
    }

    public function getCampaignActivities(): int
    {
        $breakdown = $this->getEntityTypeBreakdown();

        return array_sum(array_filter($breakdown, fn ($key): bool => str_contains($key, 'Campaign'), ARRAY_FILTER_USE_KEY));
    }

    public function getDonationActivities(): int
    {
        $breakdown = $this->getEntityTypeBreakdown();

        return array_sum(array_filter($breakdown, fn ($key): bool => str_contains($key, 'Donation'), ARRAY_FILTER_USE_KEY));
    }

    public function getUserManagementActivities(): int
    {
        $breakdown = $this->getEntityTypeBreakdown();

        return array_sum(array_filter($breakdown, fn ($key): bool => str_contains($key, 'User'), ARRAY_FILTER_USE_KEY));
    }

    // Risk Analysis
    public function getSuspiciousActivityCount(): int
    {
        return (int) $this->get('suspicious_activity_count', 0);
    }

    public function getFailedAttempts(): int
    {
        return $this->getFailedLoginCount();
    }

    public function getRiskScore(): float
    {
        $score = 0.0;

        // Failed login attempts contribute to risk
        $failedLogins = $this->getFailedLoginCount();
        if ($failedLogins > 3) {
            $score += min($failedLogins * 10, 50);
        }

        // Suspicious activities
        $suspiciousCount = $this->getSuspiciousActivityCount();
        $score += $suspiciousCount * 20;

        // Unusual activity patterns (too many deletions)
        $deletions = $this->getDeletedCount();
        $total = $this->getTotalActivities();
        if ($total > 0 && ($deletions / $total) > 0.3) {
            $score += 25;
        }

        return min($score, 100.0);
    }

    public function getRiskLevel(): string
    {
        $score = $this->getRiskScore();

        if ($score >= 70) {
            return 'high';
        }

        if ($score >= 40) {
            return 'medium';
        }

        return 'low';
    }

    // Activity Patterns
    /**
     * @return array<int, int>
     */
    public function getHourlyActivity(): array
    {
        return $this->get('hourly_activity', []);
    }

    /**
     * @return array<string, int>
     */
    public function getDailyActivity(): array
    {
        return $this->get('daily_activity', []);
    }

    public function getPeakActivityHour(): int
    {
        $hourlyActivity = $this->getHourlyActivity();

        if ($hourlyActivity === []) {
            return 0;
        }

        $maxValue = max($hourlyActivity);
        $keys = array_keys($hourlyActivity, $maxValue);

        return $keys[0];
    }

    public function getMostActiveDay(): string
    {
        $dailyActivity = $this->getDailyActivity();

        if ($dailyActivity === []) {
            return '';
        }

        $maxValue = max($dailyActivity);
        $keys = array_keys($dailyActivity, $maxValue);

        return $keys[0];
    }

    // Recent Activities
    /**
     * @return array<string, mixed>
     */
    public function getRecentActivities(): array
    {
        return $this->get('recent_activities', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTopChanges(): array
    {
        return $this->get('top_changes', []);
    }

    // IP Address Analysis
    /**
     * @return array<string, int>
     */
    public function getIpAddressBreakdown(): array
    {
        return $this->get('ip_address_breakdown', []);
    }

    public function getUniqueIpCount(): int
    {
        return count($this->getIpAddressBreakdown());
    }

    public function getMostUsedIpAddress(): ?string
    {
        $ipBreakdown = $this->getIpAddressBreakdown();

        if ($ipBreakdown === []) {
            return null;
        }

        $maxValue = max($ipBreakdown);
        $keys = array_keys($ipBreakdown, $maxValue);

        return $keys[0];
    }

    // Browser/Device Analysis
    /**
     * @return array<string, int>
     */
    public function getBrowserBreakdown(): array
    {
        return $this->get('browser_breakdown', []);
    }

    /**
     * @return array<string, int>
     */
    public function getDeviceBreakdown(): array
    {
        return $this->get('device_breakdown', []);
    }

    public function getPrimaryBrowser(): ?string
    {
        $browserBreakdown = $this->getBrowserBreakdown();

        if ($browserBreakdown === []) {
            return null;
        }

        $maxValue = max($browserBreakdown);
        $keys = array_keys($browserBreakdown, $maxValue);

        return $keys[0];
    }

    public function getPrimaryDevice(): ?string
    {
        $deviceBreakdown = $this->getDeviceBreakdown();

        if ($deviceBreakdown === []) {
            return null;
        }

        $maxValue = max($deviceBreakdown);
        $keys = array_keys($deviceBreakdown, $maxValue);

        return $keys[0];
    }

    // Calculated Metrics
    public function getAverageActivitiesPerDay(): float
    {
        $total = $this->getTotalActivities();
        $firstActivity = $this->getFirstActivity();

        if ($total <= 0 || ! $firstActivity) {
            return 0.0;
        }

        $daysSinceFirst = max(1, (time() - strtotime($firstActivity)) / 86400);

        return round($total / $daysSinceFirst, 2);
    }

    public function getDaysSinceLastActivity(): int
    {
        $lastActivity = $this->getLastActivity();

        if (! $lastActivity) {
            return 0;
        }

        return (int) ((time() - strtotime($lastActivity)) / 86400);
    }

    public function getActivityTrend(): string
    {
        $recent = $this->getActivitiesInPeriod();
        $average = $this->getAverageActivitiesPerDay() * 30; // Monthly average

        if ($recent > $average * 1.2) {
            return 'increasing';
        }

        if ($recent < $average * 0.8) {
            return 'decreasing';
        }

        return 'stable';
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toSummaryArray(): array
    {
        return [
            'user_id' => $this->getUserId(),
            'user_name' => $this->getUserName(),
            'user_email' => $this->getUserEmail(),
            'organization_id' => $this->getOrganizationId(),
            'organization_name' => $this->getOrganizationName(),
            'total_activities' => $this->getTotalActivities(),
            'activities_in_period' => $this->getActivitiesInPeriod(),
            'first_activity' => $this->getFormattedFirstActivity(),
            'last_activity' => $this->getFormattedLastActivity(),
            'days_since_last_activity' => $this->getDaysSinceLastActivity(),
            'average_activities_per_day' => $this->getAverageActivitiesPerDay(),
            'activity_trend' => $this->getActivityTrend(),
            'risk_score' => $this->getRiskScore(),
            'risk_level' => $this->getRiskLevel(),
            'peak_activity_hour' => $this->getPeakActivityHour(),
            'most_active_day' => $this->getMostActiveDay(),
            'unique_ip_count' => $this->getUniqueIpCount(),
            'primary_browser' => $this->getPrimaryBrowser(),
            'primary_device' => $this->getPrimaryDevice(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDetailedArray(): array
    {
        return array_merge($this->toSummaryArray(), [
            'activity_breakdown' => $this->getActivityBreakdown(),
            'entity_type_breakdown' => $this->getEntityTypeBreakdown(),
            'hourly_activity' => $this->getHourlyActivity(),
            'daily_activity' => $this->getDailyActivity(),
            'recent_activities' => $this->getRecentActivities(),
            'top_changes' => $this->getTopChanges(),
            'ip_address_breakdown' => $this->getIpAddressBreakdown(),
            'browser_breakdown' => $this->getBrowserBreakdown(),
            'device_breakdown' => $this->getDeviceBreakdown(),
            'suspicious_activity_count' => $this->getSuspiciousActivityCount(),
            'failed_login_count' => $this->getFailedLoginCount(),
        ]);
    }
}
