<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Service;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Modules\User\Infrastructure\Laravel\Models\User;

class NotificationService
{
    /**
     * Get paginated notifications for the user.
     *
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    public function getNotifications(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->notifications()->paginate($perPage);
    }

    /**
     * Get recent unread notifications for dropdown display.
     */
    /**
     * @return Collection<int, DatabaseNotification>
     */
    public function getRecentUnreadNotifications(User $user, int $limit = 5): Collection
    {
        return $user->notifications()
            ->whereNull('read_at')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get all notifications ordered by creation date.
     *
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    public function getAllNotifications(User $user, int $perPage = 50): LengthAwarePaginator
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()
            ->where('id', $notificationId)
            ->first();

        if (! $notification) {
            return false;
        }

        $notification->markAsRead();

        return true;
    }

    /**
     * Mark all user notifications as read.
     */
    public function clearAll(User $user): bool
    {
        $affected = $user->notifications()->update(['read_at' => now()]);

        return $affected > 0;
    }

    /**
     * Get unread notification count.
     */
    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * Format notification data for API response.
     *
     * @return array<string, mixed>
     */
    public function formatNotificationForApi(DatabaseNotification $notification): array
    {
        $type = $notification->getAttribute('type');
        $createdAt = $notification->getAttribute('created_at');

        return [
            'id' => $notification->getAttribute('id'),
            'type' => $this->getNotificationTypeLabel((string) $type),
            'title' => $this->getNotificationTitle($notification),
            'message' => $this->getNotificationMessage($notification),
            'created_at' => $createdAt,
            'read_at' => $notification->getAttribute('read_at'),
            'data' => $notification->getAttribute('data'),
        ];
    }

    /**
     * Get notification type label.
     */
    private function getNotificationTypeLabel(string $type): string
    {
        $parts = explode('\\', $type);
        $className = end($parts);

        if ($className === '') {
            return 'Notification';
        }

        // Convert CamelCase to human readable
        $result = preg_replace('/(?<!^)[A-Z]/', ' $0', $className);

        return $result ?? 'Notification';
    }

    /**
     * Get notification title from data.
     */
    private function getNotificationTitle(DatabaseNotification $notification): string
    {
        /** @var array<string, mixed>|null $data */
        $data = $notification->getAttribute('data');

        return (is_array($data) && isset($data['title'])) ? (string) $data['title'] : 'Notification';
    }

    /**
     * Get notification message from data.
     */
    private function getNotificationMessage(DatabaseNotification $notification): string
    {
        /** @var array<string, mixed>|null $data */
        $data = $notification->getAttribute('data');

        return (is_array($data) && isset($data['message'])) ? (string) $data['message'] : 'You have a new notification.';
    }
}
