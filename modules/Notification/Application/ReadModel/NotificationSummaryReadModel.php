<?php

declare(strict_types=1);

namespace Modules\Notification\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Notification summary read model optimized for dashboard and overview data.
 */
final class NotificationSummaryReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        array $data,
        ?string $version = null
    ) {
        parent::__construct(0, $data, $version); // Summary doesn't have single ID
        $this->setCacheTtl(300); // 5 minutes for notification summaries
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_values(array_merge(parent::getCacheTags(), [
            'notification_summary',
            'user:' . $this->getUserId(),
            'notifications',
        ]));
    }

    // User Information
    public function getUserId(): int
    {
        return (int) $this->get('user_id', 0);
    }

    // Pagination Information
    public function getCurrentPage(): int
    {
        return (int) $this->get('current_page', 1);
    }

    public function getPerPage(): int
    {
        return (int) $this->get('per_page', 15);
    }

    public function getTotal(): int
    {
        return (int) $this->get('total', 0);
    }

    public function getLastPage(): int
    {
        return (int) $this->get('last_page', 1);
    }

    public function hasMorePages(): bool
    {
        return $this->getCurrentPage() < $this->getLastPage();
    }

    // Notifications Data
    /**
     * @return array<string, mixed>
     */
    public function getNotifications(): array
    {
        return $this->get('notifications', []);
    }

    public function getNotificationCount(): int
    {
        return count($this->getNotifications());
    }

    public function isEmpty(): bool
    {
        return $this->getNotificationCount() === 0;
    }

    // Count Statistics
    public function getTotalNotifications(): int
    {
        return (int) $this->get('total_notifications', 0);
    }

    public function getUnreadCount(): int
    {
        return (int) $this->get('unread_count', 0);
    }

    public function getReadCount(): int
    {
        return (int) $this->get('read_count', 0);
    }

    public function getDismissedCount(): int
    {
        return (int) $this->get('dismissed_count', 0);
    }

    public function getTodayCount(): int
    {
        return (int) $this->get('today_count', 0);
    }

    public function getThisWeekCount(): int
    {
        return (int) $this->get('this_week_count', 0);
    }

    public function getThisMonthCount(): int
    {
        return (int) $this->get('this_month_count', 0);
    }

    // Type Breakdown
    /**
     * @return array<string, int>
     */
    public function getTypeBreakdown(): array
    {
        return $this->get('type_breakdown', []);
    }

    /**
     * @return array<string, int>
     */
    public function getCategoryBreakdown(): array
    {
        return $this->get('category_breakdown', []);
    }

    /**
     * @return array<string, int>
     */
    public function getPriorityBreakdown(): array
    {
        return $this->get('priority_breakdown', []);
    }

    // Priority Counts
    public function getCriticalCount(): int
    {
        $breakdown = $this->getPriorityBreakdown();

        return $breakdown['critical'] ?? 0;
    }

    public function getHighPriorityCount(): int
    {
        $breakdown = $this->getPriorityBreakdown();

        return $breakdown['high'] ?? 0;
    }

    public function getNormalPriorityCount(): int
    {
        $breakdown = $this->getPriorityBreakdown();

        return $breakdown['normal'] ?? 0;
    }

    public function getLowPriorityCount(): int
    {
        $breakdown = $this->getPriorityBreakdown();

        return $breakdown['low'] ?? 0;
    }

    // Category Counts
    public function getCampaignNotificationsCount(): int
    {
        $breakdown = $this->getCategoryBreakdown();

        return $breakdown['campaign'] ?? 0;
    }

    public function getDonationNotificationsCount(): int
    {
        $breakdown = $this->getCategoryBreakdown();

        return $breakdown['donation'] ?? 0;
    }

    public function getSystemNotificationsCount(): int
    {
        $breakdown = $this->getCategoryBreakdown();

        return $breakdown['system'] ?? 0;
    }

    public function getUserNotificationsCount(): int
    {
        $breakdown = $this->getCategoryBreakdown();

        return $breakdown['user'] ?? 0;
    }

    public function getOrganizationNotificationsCount(): int
    {
        $breakdown = $this->getCategoryBreakdown();

        return $breakdown['organization'] ?? 0;
    }

    // Status Helpers
    public function hasUnreadNotifications(): bool
    {
        return $this->getUnreadCount() > 0;
    }

    public function hasCriticalNotifications(): bool
    {
        return $this->getCriticalCount() > 0;
    }

    public function hasHighPriorityNotifications(): bool
    {
        return $this->getHighPriorityCount() > 0;
    }

    public function hasUrgentNotifications(): bool
    {
        if ($this->getCriticalCount() > 0) {
            return true;
        }

        return $this->getHighPriorityCount() > 0;
    }

    public function getReadPercentage(): float
    {
        $total = $this->getTotalNotifications();
        if ($total <= 0) {
            return 0.0;
        }

        return ($this->getReadCount() / $total) * 100;
    }

    // Recent Activity
    /**
     * @return array<string, mixed>
     */
    public function getRecentNotifications(int $limit = 5): array
    {
        $notifications = $this->getNotifications();

        return array_slice($notifications, 0, $limit);
    }

    /**
     * @return array<string, mixed>
     */
    public function getUnreadNotifications(): array
    {
        return array_filter($this->getNotifications(), fn (array $notification): bool => ! ($notification['is_read'] ?? false));
    }

    /**
     * @return array<string, mixed>
     */
    public function getCriticalNotifications(): array
    {
        return array_filter($this->getNotifications(), fn (array $notification): bool => ($notification['priority'] ?? 'normal') === 'critical');
    }

    /**
     * @return array<string, mixed>
     */
    public function getHighPriorityNotifications(): array
    {
        return array_filter($this->getNotifications(), fn (array $notification): bool => ($notification['priority'] ?? 'normal') === 'high');
    }

    /**
     * @return array<string, mixed>
     */
    public function getActionableNotifications(): array
    {
        return array_filter($this->getNotifications(), fn (array $notification) => $notification['has_action'] ?? false);
    }

    // Delivery Status
    public function getDeliveredCount(): int
    {
        return (int) $this->get('delivered_count', 0);
    }

    public function getPendingDeliveryCount(): int
    {
        return (int) $this->get('pending_delivery_count', 0);
    }

    public function getFailedDeliveryCount(): int
    {
        return (int) $this->get('failed_delivery_count', 0);
    }

    public function getDeliverySuccessRate(): float
    {
        $total = $this->getTotalNotifications();
        if ($total <= 0) {
            return 0.0;
        }

        return ($this->getDeliveredCount() / $total) * 100;
    }

    public function hasDeliveryIssues(): bool
    {
        if ($this->getFailedDeliveryCount() > 0) {
            return true;
        }

        return $this->getPendingDeliveryCount() > 10;
    }

    // Channel Statistics
    /**
     * @return array<string, int>
     */
    public function getChannelBreakdown(): array
    {
        return $this->get('channel_breakdown', []);
    }

    public function getDatabaseNotificationsCount(): int
    {
        $breakdown = $this->getChannelBreakdown();

        return $breakdown['database'] ?? 0;
    }

    public function getEmailNotificationsCount(): int
    {
        $breakdown = $this->getChannelBreakdown();

        return $breakdown['email'] ?? 0;
    }

    public function getSmsNotificationsCount(): int
    {
        $breakdown = $this->getChannelBreakdown();

        return $breakdown['sms'] ?? 0;
    }

    public function getPushNotificationsCount(): int
    {
        $breakdown = $this->getChannelBreakdown();

        return $breakdown['push'] ?? 0;
    }

    // Time-based Statistics
    public function getAverageTimeToRead(): int
    {
        return (int) $this->get('average_time_to_read_minutes', 0);
    }

    public function getOldestUnreadNotificationAge(): int
    {
        return (int) $this->get('oldest_unread_notification_age_hours', 0);
    }

    public function hasStaleNotifications(): bool
    {
        return $this->getOldestUnreadNotificationAge() > 168; // 7 days
    }

    // Filter Information
    /**
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return $this->get('filters', []);
    }

    public function getStatusFilter(): ?string
    {
        $filters = $this->getFilters();

        return $filters['status'] ?? null;
    }

    public function getTypeFilter(): ?string
    {
        $filters = $this->getFilters();

        return $filters['type'] ?? null;
    }

    public function getPriorityFilter(): ?string
    {
        $filters = $this->getFilters();

        return $filters['priority'] ?? null;
    }

    public function getCategoryFilter(): ?string
    {
        $filters = $this->getFilters();

        return $filters['category'] ?? null;
    }

    public function hasActiveFilters(): bool
    {
        $filters = $this->getFilters();
        unset($filters['page'], $filters['per_page']);

        return $filters !== [];
    }

    // Badge Information
    public function getBadgeCount(): int
    {
        // Return unread count, but cap at 99 for UI purposes
        return min(99, $this->getUnreadCount());
    }

    public function shouldShowBadge(): bool
    {
        return $this->getUnreadCount() > 0;
    }

    public function getBadgeText(): string
    {
        $count = $this->getUnreadCount();
        if ($count > 99) {
            return '99+';
        }

        return (string) $count;
    }

    public function getBadgeColor(): string
    {
        return match (true) {
            $this->hasCriticalNotifications() => 'red',
            $this->hasHighPriorityNotifications() => 'orange',
            $this->hasUnreadNotifications() => 'blue',
            default => 'gray',
        };
    }

    // Health and Status
    public function getNotificationHealthScore(): float
    {
        $deliveryRate = $this->getDeliverySuccessRate();
        $readRate = $this->getReadPercentage();
        $staleScore = $this->hasStaleNotifications() ? 50 : 100;

        return $deliveryRate * 0.4 + $readRate * 0.4 + $staleScore * 0.2;
    }

    public function getNotificationHealthStatus(): string
    {
        $score = $this->getNotificationHealthScore();

        return match (true) {
            $score >= 80 => 'excellent',
            $score >= 60 => 'good',
            $score >= 40 => 'fair',
            default => 'poor',
        };
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->getUserId(),
            'pagination' => [
                'current_page' => $this->getCurrentPage(),
                'per_page' => $this->getPerPage(),
                'total' => $this->getTotal(),
                'last_page' => $this->getLastPage(),
                'has_more_pages' => $this->hasMorePages(),
            ],
            'notifications' => $this->getNotifications(),
            'counts' => [
                'total' => $this->getTotalNotifications(),
                'unread' => $this->getUnreadCount(),
                'read' => $this->getReadCount(),
                'dismissed' => $this->getDismissedCount(),
                'today' => $this->getTodayCount(),
                'this_week' => $this->getThisWeekCount(),
                'this_month' => $this->getThisMonthCount(),
            ],
            'breakdown' => [
                'types' => $this->getTypeBreakdown(),
                'categories' => $this->getCategoryBreakdown(),
                'priorities' => $this->getPriorityBreakdown(),
                'channels' => $this->getChannelBreakdown(),
            ],
            'priority_counts' => [
                'critical' => $this->getCriticalCount(),
                'high' => $this->getHighPriorityCount(),
                'normal' => $this->getNormalPriorityCount(),
                'low' => $this->getLowPriorityCount(),
            ],
            'category_counts' => [
                'campaign' => $this->getCampaignNotificationsCount(),
                'donation' => $this->getDonationNotificationsCount(),
                'system' => $this->getSystemNotificationsCount(),
                'user' => $this->getUserNotificationsCount(),
                'organization' => $this->getOrganizationNotificationsCount(),
            ],
            'delivery' => [
                'delivered_count' => $this->getDeliveredCount(),
                'pending_delivery_count' => $this->getPendingDeliveryCount(),
                'failed_delivery_count' => $this->getFailedDeliveryCount(),
                'delivery_success_rate' => $this->getDeliverySuccessRate(),
                'has_delivery_issues' => $this->hasDeliveryIssues(),
            ],
            'status' => [
                'has_unread' => $this->hasUnreadNotifications(),
                'has_critical' => $this->hasCriticalNotifications(),
                'has_high_priority' => $this->hasHighPriorityNotifications(),
                'has_urgent' => $this->hasUrgentNotifications(),
                'read_percentage' => $this->getReadPercentage(),
                'has_stale_notifications' => $this->hasStaleNotifications(),
            ],
            'timing' => [
                'average_time_to_read' => $this->getAverageTimeToRead(),
                'oldest_unread_age_hours' => $this->getOldestUnreadNotificationAge(),
            ],
            'filters' => $this->getFilters(),
            'badge' => [
                'count' => $this->getBadgeCount(),
                'text' => $this->getBadgeText(),
                'color' => $this->getBadgeColor(),
                'should_show' => $this->shouldShowBadge(),
            ],
            'health' => [
                'score' => $this->getNotificationHealthScore(),
                'status' => $this->getNotificationHealthStatus(),
            ],
        ];
    }

    /**
     * Get summary data for dashboard widgets
     *
     * @return array<string, mixed>
     */
    public function toDashboardSummary(): array
    {
        return [
            'unread_count' => $this->getUnreadCount(),
            'total_count' => $this->getTotalNotifications(),
            'has_critical' => $this->hasCriticalNotifications(),
            'has_urgent' => $this->hasUrgentNotifications(),
            'badge_count' => $this->getBadgeCount(),
            'badge_text' => $this->getBadgeText(),
            'badge_color' => $this->getBadgeColor(),
            'should_show_badge' => $this->shouldShowBadge(),
            'recent_notifications' => $this->getRecentNotifications(3),
        ];
    }

    /**
     * Get data optimized for mobile notifications overview
     *
     * @return array<string, mixed>
     */
    public function toMobileOverview(): array
    {
        return [
            'unread_count' => $this->getUnreadCount(),
            'has_urgent' => $this->hasUrgentNotifications(),
            'badge_count' => $this->getBadgeCount(),
            'badge_text' => $this->getBadgeText(),
            'recent_notifications' => array_map(fn (array $notification): array => [
                'id' => $notification['id'],
                'title' => $notification['title'],
                'short_message' => $notification['short_message'] ?? '',
                'priority' => $notification['priority'],
                'is_read' => $notification['is_read'],
                'created_at' => $notification['created_at'],
                'icon' => $notification['icon'] ?? 'bell',
                'color' => $notification['color'] ?? 'blue',
            ], $this->getRecentNotifications(5)),
        ];
    }
}
