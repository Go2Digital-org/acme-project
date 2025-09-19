<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Repository;

use DateTime;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;

/**
 * Eloquent implementation of the notification repository.
 *
 * Handles database operations for notifications using Laravel's Eloquent ORM.
 * Optimized for high-performance operations with 20K+ concurrent users.
 */
final readonly class NotificationEloquentRepository implements NotificationRepositoryInterface
{
    public function __construct(
        private Notification $model,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Notification
    {
        return $this->model->create($data);
    }

    public function findById(string $id): ?Notification
    {
        return $this->model->find($id);
    }

    public function findByRecipientId(
        string $recipientId,
        int $page = 1,
        int $perPage = 20,
        ?string $status = null,
    ): LengthAwarePaginator {
        $query = $this->model->where('notifiable_id', $recipientId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getUnreadNotifications(string $recipientId, int $limit = 10): Collection
    {
        return $this->model->where('notifiable_id', $recipientId)
            ->unread()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getSendableNotifications(int $limit = 100): Collection
    {
        return $this->model->query()->where('status', 'pending')
            ->where('scheduled_for', '<=', now())
            ->limit($limit)
            ->get();
    }

    public function findByType(
        string $type,
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->model->query()->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findByPriority(
        string $priority,
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->model->query()->where('priority', $priority)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findByChannel(
        string $channel,
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->model->where('channel', $channel)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getRecentNotifications(
        string $recipientId,
        int $hours = 24,
        int $limit = 50,
    ): Collection {
        return $this->model->where('notifiable_id', $recipientId)
            ->recent($hours)
            ->limit($limit)
            ->get();
    }

    public function countUnreadForRecipient(string $recipientId): int
    {
        return $this->model->where('notifiable_id', $recipientId)
            ->unread()
            ->count();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateById(string $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data) > 0;
    }

    public function deleteById(string $id): bool
    {
        return $this->model->where('id', $id)->delete() > 0;
    }

    public function markAsRead(string $id): bool
    {
        $notification = $this->findById($id);

        if (! $notification instanceof Notification) {
            return false;
        }

        return $this->updateById($id, ['read_at' => now()]);
    }

    public function markAllAsReadForRecipient(string $recipientId): int
    {
        return $this->model->where('notifiable_id', $recipientId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function markAsSent(string $id): bool
    {
        return $this->updateById($id, [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $id, string $errorMessage = ''): bool
    {
        return $this->updateById($id, [
            'status' => 'failed',
            'metadata' => [
                'error_message' => $errorMessage,
                'failed_at' => now()->toISOString(),
            ],
        ]);
    }

    public function cancel(string $id, string $reason = ''): bool
    {
        return $this->updateById($id, [
            'status' => 'cancelled',
            'metadata' => [
                'cancellation_reason' => $reason,
                'cancelled_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getFailedNotificationsForRetry(int $limit = 50): Collection
    {
        return $this->model->where('status', 'failed')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getScheduledNotifications(int $limit = 100): Collection
    {
        return $this->model->where('status', 'pending')
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        // Apply filters
        foreach ($filters as $field => $value) {
            if ($field === 'search' && $value !== null) {
                $query->where(function (Builder $q) use ($value): void {
                    $q->where('title', 'like', "%{$value}%")
                        ->orWhere('message', 'like', "%{$value}%");
                });

                continue;
            }

            if ($field === 'read_at' && $value === 'null') {
                $query->whereNull('read_at');

                continue;
            }

            if ($value !== null) {
                $query->where($field, $value);
            }
        }

        /** @var LengthAwarePaginator<int, Notification> $result */
        $result = $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatistics(
        ?string $recipientId = null,
        ?DateTime $startDate = null,
        ?DateTime $endDate = null,
    ): array {
        $query = $this->model->newQuery();

        if ($recipientId) {
            $query->where('notifiable_id', $recipientId);
        }

        if ($startDate instanceof DateTime) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate instanceof DateTime) {
            $query->where('created_at', '<=', $endDate);
        }

        return [
            'total' => $query->count(),
            'sent' => $query->where('status', 'sent')->count(),
            'failed' => $query->where('status', 'failed')->count(),
            'pending' => $query->where('status', 'pending')->count(),
            'unread' => $query->whereNull('read_at')->count(),
            'by_type' => $query->groupBy('type')
                ->selectRaw('type, count(*) as count')
                ->pluck('count', 'type')
                ->toArray(),
            'by_channel' => $query->groupBy('channel')
                ->selectRaw('channel, count(*) as count')
                ->pluck('count', 'channel')
                ->toArray(),
        ];
    }

    public function cleanupOldNotifications(int $daysOld = 90): int
    {
        $cutoffDate = now()->subDays($daysOld);

        return $this->model->where('created_at', '<', $cutoffDate)
            ->whereNotNull('read_at') // Only delete read notifications
            ->delete();
    }

    public function getNotificationsWithReadReceipts(
        string $recipientId,
        int $limit = 100,
    ): Collection {
        return $this->model->where('notifiable_id', $recipientId)
            ->whereNotNull('read_at')
            ->orderBy('read_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * @param array<string> $ids
     * @param array<string, mixed> $data
     */
    public function bulkUpdate(array $ids, array $data): int
    {
        return $this->model->whereIn('id', $ids)->update($data);
    }

    public function findBySender(
        string $senderId,
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->model->where('sender_id', $senderId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatusCountsForRecipient(string $recipientId): array
    {
        return $this->model->where('notifiable_id', $recipientId)
            ->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function hasNotificationsOfType(
        string $recipientId,
        string $type,
        ?DateTime $since = null,
    ): bool {
        $query = $this->model->where('notifiable_id', $recipientId)
            ->where('type', $type);

        if ($since instanceof DateTime) {
            $query->where('created_at', '>=', $since);
        }

        return $query->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function getDeliveryMetrics(
        ?DateTime $startDate = null,
        ?DateTime $endDate = null,
    ): array {
        $query = $this->model->newQuery();

        if ($startDate instanceof DateTime) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate instanceof DateTime) {
            $query->where('created_at', '<=', $endDate);
        }

        $total = $query->count();
        $sent = $query->where('status', 'sent')->count();
        $failed = $query->where('status', 'failed')->count();

        return [
            'total_notifications' => $total,
            'successful_deliveries' => $sent,
            'failed_deliveries' => $failed,
            'delivery_rate' => $total > 0 ? round(($sent / $total) * 100, 2) : 0,
            'failure_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
            'average_delivery_time' => $this->model->whereNotNull('sent_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, sent_at)) as avg_time')
                ->value('avg_time') ?? 0,
        ];
    }

    /**
     * @param array<array<string, mixed>> $notifications
     */
    public function bulkCreate(array $notifications): int
    {
        $now = now();

        // Add timestamps to all notifications
        $notificationsWithTimestamps = array_map(fn (array $notification): array => array_merge($notification, [
            'id' => $notification['id'] ?? (string) Str::uuid(),
            'created_at' => $now,
            'updated_at' => $now,
        ]), $notifications);

        // Use chunk insertion for better memory management
        $chunks = array_chunk($notificationsWithTimestamps, 1000);
        $totalInserted = 0;

        DB::transaction(function () use ($chunks, &$totalInserted): void {
            foreach ($chunks as $chunk) {
                $this->model->insert($chunk);
                $totalInserted += count($chunk);
            }
        });

        return $totalInserted;
    }

    /**
     * @param  array<string, mixed>  $filters
     */

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function cursorPaginate(
        string $recipientId,
        ?string $cursor = null,
        int $limit = 20,
        array $filters = [],
    ): array {
        $query = $this->model->where('notifiable_id', $recipientId);

        // Apply filters
        $this->applyFilters($query, $filters);

        // Apply cursor pagination
        if ($cursor) {
            $decodedCursor = base64_decode($cursor, true);
            if ($decodedCursor === false) {
                return [
                    'data' => collect(),
                    'next_cursor' => null,
                    'has_more' => false,
                ];
            }
            $cursorData = json_decode($decodedCursor, true);

            if ($cursorData && isset($cursorData['created_at'], $cursorData['id'])) {
                $query->where(function (Builder $q) use ($cursorData): void {
                    $q->where('created_at', '<', $cursorData['created_at'])
                        ->orWhere(function (Builder $subQ) use ($cursorData): void {
                            $subQ->where('created_at', '=', $cursorData['created_at'])
                                ->where('id', '<', $cursorData['id']);
                        });
                });
            }
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit + 1)
            ->get();

        $hasMore = count($notifications) > $limit;

        if ($hasMore) {
            $notifications->pop();
        }

        $nextCursor = null;

        if ($hasMore && $notifications->isNotEmpty()) {
            $lastNotification = $notifications->last();
            $jsonData = json_encode([
                'created_at' => $lastNotification->created_at->toISOString(),
                'id' => $lastNotification->id,
            ]);
            $nextCursor = $jsonData !== false ? base64_encode($jsonData) : null;
        }

        return [
            'data' => $notifications,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDigest(string $recipientId, int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $query = $this->model->where('notifiable_id', $recipientId)
            ->where('created_at', '>=', $since);

        return [
            'total_count' => $query->count(),
            'unread_count' => $query->clone()->whereNull('read_at')->count(),
            'by_type' => $query->clone()
                ->groupBy('type')
                ->selectRaw('type, count(*) as count')
                ->pluck('count', 'type')
                ->toArray(),
            'by_priority' => $query->clone()
                ->groupBy('priority')
                ->selectRaw('priority, count(*) as count')
                ->pluck('count', 'priority')
                ->toArray(),
            'recent_notifications' => $query->clone()
                ->latest()
                ->limit(5)
                ->select(['id', 'title', 'type', 'priority', 'created_at'])
                ->get(),
            'period' => "{$hours} hours",
        ];
    }

    /**
     * @param array<string> $notificationIds
     */
    public function batchMarkAsRead(array $notificationIds, string $recipientId): int
    {
        return $this->model->whereIn('id', $notificationIds)
            ->where('notifiable_id', $recipientId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */

    /**
     * @return array<string, mixed>
     */
    public function getTimeSeriesData(
        $periodOrDateRange,
        string|int $groupBy = 'day',
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        array $filters = [],
    ): array {
        // Handle different parameter formats for backwards compatibility
        if (is_string($periodOrDateRange)) {
            $period = $periodOrDateRange;
        }

        if (is_array($periodOrDateRange)) {
            $period = 'day'; // Default for array format
            $filters = array_merge($filters, $periodOrDateRange);
        }

        if (! is_string($periodOrDateRange) && ! is_array($periodOrDateRange)) {
            $period = 'day'; // Default fallback
        }

        $groupByPeriod = is_string($groupBy) ? $groupBy : 'day';

        $periodFormat = match ($groupByPeriod) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $query = $this->model->newQuery();
        $this->applyFilters($query, $filters);

        // Apply date range filters if provided
        if ($startDate instanceof DateTimeInterface) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate instanceof DateTimeInterface) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->selectRaw("
                DATE_FORMAT(created_at, '{$periodFormat}') as period,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN read_at IS NOT NULL THEN 1 ELSE 0 END) as read
            ")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period')
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $filters
     */

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getCountsByField(string $field, array $filters = []): array
    {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $filters);

        return $query->groupBy($field)
            ->selectRaw("{$field}, COUNT(*) as count")
            ->pluck('count', $field)
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countByFilters(array $filters): int
    {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $filters);

        return $query->count();
    }

    /**
     * @param array<string, mixed> $filters
     * @return Collection<int, Notification>
     */
    public function findByFilters(array $filters, int $limit = 100): Collection
    {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $filters);

        return $query->limit($limit)->get();
    }

    public function archiveOldNotifications(int $daysOld): int
    {
        $cutoffDate = now()->subDays($daysOld);

        return $this->model->newQuery()
            ->where('created_at', '<', $cutoffDate)
            ->whereIn('status', ['sent', 'delivered', 'failed'])
            ->count(); // For now just return count, actual archiving would move to another table
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function deleteByFilters(array $filters): int
    {
        $query = $this->model->newQuery();
        $this->applyFilters($query, $filters);

        return $query->delete();
    }

    /**
     * @param array<string, mixed> $data
     * @return Collection<int, Notification>
     */
    public function findDuplicateNotifications(
        string $recipientId,
        string $type,
        array $data,
        int $withinMinutes = 60,
    ): Collection {
        $since = now()->subMinutes($withinMinutes);
        $dataJson = json_encode($data);

        return $this->model->where('notifiable_id', $recipientId)
            ->where('type', $type)
            ->where('created_at', '>=', $since)
            ->whereRaw('JSON_EXTRACT(data, "$") = ?', [$dataJson])
            ->get();
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotificationsForRetry(
        int $maxRetries = 3,
        int $minFailureAgeMinutes = 30,
    ): Collection {
        $minFailureTime = now()->subMinutes($minFailureAgeMinutes);

        return $this->model->where('status', 'failed')
            ->where('created_at', '<=', $minFailureTime)
            ->whereRaw('JSON_EXTRACT(metadata, "$.retry_count") < ? OR JSON_EXTRACT(metadata, "$.retry_count") IS NULL', [$maxRetries])
            ->orderBy('created_at', 'asc')
            ->limit(100)
            ->get();
    }

    public function archiveNotifications(int $daysOld = 365, int $batchSize = 1000): int
    {
        $cutoffDate = now()->subDays($daysOld);
        $archivedCount = 0;

        // Process in batches to avoid memory issues
        do {
            $batch = $this->model->where('created_at', '<', $cutoffDate)
                ->whereNotNull('read_at')
                ->limit($batchSize)
                ->get(['id']);

            if ($batch->isEmpty()) {
                break;
            }

            $ids = $batch->pluck('id')->toArray();

            DB::transaction(function () use ($ids, &$archivedCount): void {
                // Insert into archive table (assuming it exists)
                DB::statement("
                    INSERT INTO notifications_archive 
                    SELECT * FROM notifications 
                    WHERE id IN ('" . implode("','", $ids) . "')
                ");

                // Delete from main table
                $deleted = $this->model->whereIn('id', $ids)->delete();
                $archivedCount += (int) $deleted;
            });
        } while (count($batch) === $batchSize);

        return $archivedCount;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRealTimeCounts(): array
    {
        return [
            'total_pending' => $this->model->where('status', 'pending')->count(),
            'total_sent_today' => $this->model
                ->where('status', 'sent')
                ->whereDate('created_at', today())
                ->count(),
            'total_failed_today' => $this->model
                ->where('status', 'failed')
                ->whereDate('created_at', today())
                ->count(),
            'high_priority_pending' => $this->model
                ->where('status', 'pending')
                ->where('priority', 'high')
                ->count(),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return Collection<int, Notification>
     */
    public function fullTextSearch(
        string $query,
        ?string $recipientId = null,
        array $filters = [],
        int $limit = 50,
    ): Collection {
        $searchQuery = $this->model->newQuery();

        // Apply full-text search
        $searchQuery->whereRaw('MATCH(title, message) AGAINST(? IN NATURAL LANGUAGE MODE)', [$query]);

        if ($recipientId) {
            $searchQuery->where('notifiable_id', $recipientId);
        }

        $this->applyFilters($searchQuery, $filters);

        return $searchQuery->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function getEngagementMetrics(
        ?DateTime $startDate = null,
        ?DateTime $endDate = null,
    ): array {
        $query = $this->model->newQuery();

        if ($startDate instanceof DateTime) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate instanceof DateTime) {
            $query->where('created_at', '<=', $endDate);
        }

        $total = $query->count();
        $read = $query->clone()->whereNotNull('read_at')->count();
        $clicked = $query->clone()->whereNotNull('clicked_at')->count();

        return [
            'total_notifications' => $total,
            'read_notifications' => $read,
            'clicked_notifications' => $clicked,
            'read_rate' => $total > 0 ? round(($read / $total) * 100, 2) : 0,
            'click_through_rate' => $read > 0 ? round(($clicked / $read) * 100, 2) : 0,
            'engagement_score' => $total > 0 ? round((($read * 0.6 + $clicked * 0.4) / $total) * 100, 2) : 0,
            'average_time_to_read' => $query->clone()
                ->whereNotNull('read_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, read_at)) as avg_time')
                ->value('avg_time') ?? 0,
        ];
    }

    public function purgeFailedNotifications(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);

        return $this->model->where('status', 'failed')
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }

    public function findByContext(
        string $contextType,
        string $contextId,
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->model->whereJsonContains('data->context', [
            'type' => $contextType,
            'id' => $contextId,
        ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function updateMetadata(string $id, array $metadata): bool
    {
        return $this->model->where('id', $id)
            ->update(['metadata' => DB::raw("JSON_MERGE_PATCH(COALESCE(metadata, '{}'), '" . json_encode($metadata) . "')")]) > 0;
    }

    public function delete(string $id): bool
    {
        $notification = $this->findById($id);

        if (! $notification instanceof Notification) {
            return false;
        }

        return $notification->delete() !== false;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Notification $notification, array $data): bool
    {
        return $notification->update($data);
    }

    public function save(Notification $notification): bool
    {
        return $notification->save();
    }

    /**
     * Apply filters to a query builder instance.
     *
     * @param Builder<Notification> $query
     * @param array<string, mixed> $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if ($value === null) {
                continue;
            }

            switch ($field) {
                case 'status':
                    $query->where('status', $value);
                    break;
                case 'type':
                    $query->where('type', $value);
                    break;
                case 'priority':
                    $query->where('priority', $value);
                    break;
                case 'channel':
                    $query->where('channel', $value);
                    break;
                case 'sender_id':
                    $query->where('sender_id', $value);
                    break;
                case 'unread':
                    /** @var Builder<Notification> $query */
                    if ((bool) $value) {
                        $query->whereNull('read_at');
                        break;
                    }
                    $query->whereNotNull('read_at');
                    break;
                case 'date_from':
                    $query->where('created_at', '>=', $value);
                    break;
                case 'date_to':
                    $query->where('created_at', '<=', $value);
                    break;
            }
        }
    }

    /**
     * @param array<string> $ids
     * @return Collection<int, Notification>
     */
    public function findByIds(array $ids): Collection
    {
        return $this->model->whereIn('id', $ids)->get();
    }

    public function markAllAsReadForUser(string $userId): int
    {
        return $this->model
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function getUnreadCount(string $userId): int
    {
        return $this->model
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
