<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Repository;

use DateTime;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Notification\Domain\Model\Notification;

/**
 * Repository interface for notification persistence operations.
 *
 * Defines the contract for notification data access following hexagonal architecture.
 * Infrastructure implementations handle the actual database operations.
 */
interface NotificationRepositoryInterface
{
    /**
     * Create a new notification.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Notification;

    /**
     * Find notification by ID.
     */
    public function findById(string $id): ?Notification;

    /**
     * Get notifications for a specific recipient.
     *
     * @return LengthAwarePaginator<int, Notification>
     */
    public function findByRecipientId(
        string $recipientId,
        int $page = 1,
        int $perPage = 20,
        ?string $status = null,
    ): LengthAwarePaginator;

    /**
     * Get unread notifications for a recipient.
     *
     * @return Collection<int, Notification>
     */
    public function getUnreadNotifications(
        string $recipientId,
        int $limit = 10,
    ): Collection;

    /**
     * Get notifications that can be sent now.
     *
     * @return Collection<int, Notification>
     */
    public function getSendableNotifications(int $limit = 100): Collection;

    /**
     * Get notifications by type.
     *
     * @return LengthAwarePaginator<int, Notification>
     */
    public function findByType(
        string $type,
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator;

    /**
     * Get notifications by priority.
     *
     * @return LengthAwarePaginator<int, Notification>
     */
    public function findByPriority(
        string $priority,
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator;

    /**
     * Get notifications by channel.
     *
     * @return LengthAwarePaginator<int, Notification>
     */
    public function findByChannel(
        string $channel,
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator;

    /**
     * Get recent notifications for a recipient.
     *
     * @return Collection<int, Notification>
     */
    public function getRecentNotifications(
        string $recipientId,
        int $hours = 24,
        int $limit = 50,
    ): Collection;

    /**
     * Count unread notifications for a recipient.
     */
    public function countUnreadForRecipient(string $recipientId): int;

    /**
     * Update notification by ID.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function updateById(string $id, array $data): bool;

    /**
     * Delete notification by ID.
     */
    public function deleteById(string $id): bool;

    /**
     * Mark notification as read.
     */
    public function markAsRead(string $id): bool;

    /**
     * Mark all notifications as read for a recipient.
     */
    public function markAllAsReadForRecipient(string $recipientId): int;

    /**
     * Mark notification as sent.
     */
    public function markAsSent(string $id): bool;

    /**
     * Mark notification as failed with error message.
     */
    public function markAsFailed(string $id, string $errorMessage = ''): bool;

    /**
     * Cancel a pending notification.
     */
    public function cancel(string $id, string $reason = ''): bool;

    /**
     * Get failed notifications for retry.
     *
     * @return Collection<int, Notification>
     */
    public function getFailedNotificationsForRetry(int $limit = 50): Collection;

    /**
     * Get notifications scheduled for future delivery.
     *
     * @return Collection<int, Notification>
     */
    public function getScheduledNotifications(int $limit = 100): Collection;

    /**
     * Search notifications with filters.
     *
     * @param  array<string, mixed>  $filters
     * @param  string  $sortBy
     * @param  string  $sortOrder
     * @param  int  $page
     * @param  int  $perPage
     * @return LengthAwarePaginator<int, Notification>
     */
    public function search(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator;

    /**
     * Get notification statistics for analytics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(
        ?string $recipientId = null,
        ?DateTime $startDate = null,
        ?DateTime $endDate = null,
    ): array;

    /**
     * Clean up old notifications.
     */
    public function cleanupOldNotifications(int $daysOld = 90): int;

    /**
     * Get notifications with read receipts.
     *
     * @return Collection<int, Notification>
     */
    public function getNotificationsWithReadReceipts(
        string $recipientId,
        int $limit = 100,
    ): Collection;

    /**
     * Bulk update notifications.
     */
    /**
     * @param  array<string>  $ids
     * @param  array<string, mixed>  $data
     */
    public function bulkUpdate(array $ids, array $data): int;

    /**
     * Get notifications by sender.
     *
     * @return LengthAwarePaginator<int, Notification>
     */
    public function findBySender(
        string $senderId,
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator;

    /**
     * Get notification count by status for a recipient.
     *
     * @return array<string, mixed>
     */
    public function getStatusCountsForRecipient(string $recipientId): array;

    /**
     * Check if recipient has notifications of a specific type.
     */
    public function hasNotificationsOfType(
        string $recipientId,
        string $type,
        ?DateTime $since = null,
    ): bool;

    /**
     * Get notification delivery metrics.
     */
    /**
     * @return array<string, mixed>
     */
    public function getDeliveryMetrics(
        ?DateTime $startDate = null,
        ?DateTime $endDate = null,
    ): array;

    /**
     * Bulk create notifications for performance.
     *
     * @param  array<array<string, mixed>>  $notifications
     */
    public function bulkCreate(array $notifications): int;

    /**
     * Get notifications with cursor-based pagination for better performance.
     *
     * @param  string  $recipientId
     * @param  string|null  $cursor
     * @param  int  $limit
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function cursorPaginate(
        string $recipientId,
        ?string $cursor = null,
        int $limit = 20,
        array $filters = [],
    ): array;

    /**
     * Get notification digest for user (summary of recent notifications).
     */
    /**
     * @return array<string, mixed>
     */
    public function getDigest(
        string $recipientId,
        int $hours = 24,
    ): array;

    /**
     * Batch mark multiple notifications as read.
     */
    /**
     * @param  array<string>  $notificationIds
     */
    public function batchMarkAsRead(array $notificationIds, string $recipientId): int;

    /**
     * Find duplicate notifications to prevent spam.
     *
     * @param  string  $recipientId
     * @param  string  $type
     * @param  array<string, mixed>  $data
     * @param  int  $withinMinutes
     * @return Collection<int, Notification>
     */
    public function findDuplicateNotifications(
        string $recipientId,
        string $type,
        array $data,
        int $withinMinutes = 60,
    ): Collection;

    /**
     * Get notifications requiring retry based on failure patterns.
     *
     * @return Collection<int, Notification>
     */
    public function getNotificationsForRetry(
        int $maxRetries = 3,
        int $minFailureAgeMinutes = 30,
    ): Collection;

    /**
     * Archive old notifications to improve performance.
     */
    public function archiveNotifications(
        int $daysOld = 365,
        int $batchSize = 1000,
    ): int;

    /**
     * Get real-time notification counts for dashboard.
     *
     * @return array<string, mixed>
     */
    public function getRealTimeCounts(): array;

    /**
     * Search notifications using full-text search capabilities.
     *
     * @param  string  $query
     * @param  string|null  $recipientId
     * @param  array<string, mixed>  $filters
     * @param  int  $limit
     * @return Collection<int, Notification>
     */
    public function fullTextSearch(
        string $query,
        ?string $recipientId = null,
        array $filters = [],
        int $limit = 50,
    ): Collection;

    /**
     * Get notification engagement metrics.
     *
     * @return array<string, mixed>
     */
    public function getEngagementMetrics(
        ?DateTime $startDate = null,
        ?DateTime $endDate = null,
    ): array;

    /**
     * Purge failed notifications that are too old to retry.
     */
    public function purgeFailedNotifications(int $daysOld = 30): int;

    /**
     * Get notifications by campaign or organization context.
     *
     * @return LengthAwarePaginator<int, Notification>
     */
    public function findByContext(
        string $contextType,
        string $contextId,
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator;

    /**
     * Update notification metadata without triggering full model events.
     */
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function updateMetadata(string $id, array $metadata): bool;

    /**
     * Get counts by a specific field.
     *
     * @param  string  $field
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getCountsByField(string $field, array $filters = []): array;

    /**
     * Count notifications by filters.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function countByFilters(array $filters): int;

    /**
     * Find notifications by filters.
     *
     * @param  array<string, mixed>  $filters
     * @param  int  $limit
     * @return Collection<int, Notification>
     */
    public function findByFilters(array $filters, int $limit = 100): Collection;

    /**
     * Get time series data with extended parameters.
     *
     * @param  array<string, mixed>|string  $periodOrDateRange
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getTimeSeriesData(
        $periodOrDateRange,
        string|int $groupBy = 'day',
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        array $filters = [],
    ): array;

    /**
     * Archive old notifications by moving to archive table.
     */
    public function archiveOldNotifications(int $daysOld): int;

    /**
     * Delete notifications by filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function deleteByFilters(array $filters): int;

    /**
     * Delete a notification by ID.
     */
    public function delete(string $id): bool;

    /**
     * Update a notification.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Notification $notification, array $data): bool;

    /**
     * Save a notification.
     */
    public function save(Notification $notification): bool;

    /**
     * Find notifications by array of IDs.
     *
     * @param  array<string>  $ids
     * @return Collection<int, Notification>
     */
    public function findByIds(array $ids): Collection;

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsReadForUser(string $userId): int;

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadCount(string $userId): int;
}
