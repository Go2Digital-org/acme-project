<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Service;

use DateTime;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Modules\Notification\Application\Command\CreateNotificationCommand;
use Modules\Notification\Application\Command\CreateNotificationCommandHandler;
use Modules\Notification\Application\Command\MarkNotificationAsReadCommand;
use Modules\Notification\Application\Command\MarkNotificationAsReadCommandHandler;
use Modules\Notification\Application\Command\SendNotificationCommand;
use Modules\Notification\Application\Command\SendNotificationCommandHandler;
use Modules\Notification\Application\Query\GetUnreadNotificationCountQuery;
use Modules\Notification\Application\Query\GetUnreadNotificationCountQueryHandler;
use Modules\Notification\Application\Query\GetUserNotificationsQuery;
use Modules\Notification\Application\Query\GetUserNotificationsQueryHandler;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Application service that orchestrates notification operations.
 *
 * Provides a high-level interface for notification functionality while
 * delegating to appropriate command and query handlers.
 */
final readonly class NotificationService
{
    public function __construct(
        private CreateNotificationCommandHandler $createHandler,
        private MarkNotificationAsReadCommandHandler $markReadHandler,
        private SendNotificationCommandHandler $sendHandler,
        private GetUserNotificationsQueryHandler $getUserNotificationsHandler,
        private GetUnreadNotificationCountQueryHandler $getUnreadCountHandler,
        private NotificationRepositoryInterface $repository,
    ) {}

    /**
     * Create a new notification.
     *
     * @param  array<string, mixed>  $data
     */
    public function createNotification(array $data): Notification
    {
        $command = CreateNotificationCommand::fromArray($data);

        return $this->createHandler->handle($command);
    }

    /**
     * Create and send a notification immediately.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $deliveryOptions
     */
    public function createAndSend(array $data, array $deliveryOptions = []): Notification
    {
        $notification = $this->createNotification($data);

        $sendCommand = new SendNotificationCommand(
            notificationId: $notification->id,
            forceImmediate: true,
            deliveryOptions: $deliveryOptions,
        );

        $this->sendHandler->handle($sendCommand);

        $freshNotification = $notification->fresh();

        return $freshNotification ?? $notification;
    }

    /**
     * Create and send a notification to a specific recipient via specified channels.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>  $channels
     */
    public function createAndSendNotification(
        string $recipientType,
        string $recipientId,
        string $type,
        array $data,
        array $channels = ['database'],
        ?DateTime $scheduledAt = null,
    ): string {
        // Prepare notification data with recipient information
        $notificationData = [
            'type' => $type,
            'notifiable_type' => $this->getNotifiableType($recipientType),
            'notifiable_id' => $recipientId,
            'data' => [
                ...$data,
                'channels' => $channels,
                'recipient_type' => $recipientType,
            ],
        ];

        // Add scheduled_at to data if provided
        if ($scheduledAt instanceof DateTime) {
            $notificationData['data']['scheduled_at'] = $scheduledAt->format('Y-m-d H:i:s');
        }

        // Create the notification
        $notification = $this->createNotification($notificationData);

        // Prepare delivery options for sending
        $deliveryOptions = [
            'channels' => $channels,
            'recipient_type' => $recipientType,
        ];

        // Add scheduling information if provided
        if ($scheduledAt instanceof DateTime) {
            $deliveryOptions['scheduled_at'] = $scheduledAt->format('Y-m-d H:i:s');
        }

        // Send the notification
        $sendCommand = new SendNotificationCommand(
            notificationId: $notification->id,
            forceImmediate: ! $scheduledAt instanceof DateTime, // Send immediately if not scheduled
            deliveryOptions: $deliveryOptions,
        );

        $this->sendHandler->handle($sendCommand);

        return $notification->id;
    }

    /**
     * Send an existing notification.
     *
     * @param  array<string, mixed>  $deliveryOptions
     */
    public function sendNotification(string $notificationId, array $deliveryOptions = []): bool
    {
        $command = new SendNotificationCommand(
            notificationId: $notificationId,
            deliveryOptions: $deliveryOptions,
        );

        return $this->sendHandler->handle($command);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(string $notificationId, string $userId): bool
    {
        $command = new MarkNotificationAsReadCommand(
            notificationId: $notificationId,
            userId: $userId,
        );

        return $this->markReadHandler->handle($command);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(string $userId): int
    {
        return $this->repository->markAllAsReadForRecipient($userId);
    }

    /**
     * Get paginated notifications for a user.
     *
     * @return LengthAwarePaginator<int, Notification>
     */
    public function getUserNotifications(
        string $userId,
        int $page = 1,
        int $perPage = 20,
        ?string $status = null,
        ?string $type = null,
        ?string $readAt = null,
    ): LengthAwarePaginator {
        $query = new GetUserNotificationsQuery(
            userId: $userId,
            page: $page,
            perPage: $perPage,
            status: $status,
            type: $type,
            readAt: $readAt,
        );

        return $this->getUserNotificationsHandler->handle($query);
    }

    /**
     * Get unread notifications for dropdown.
     *
     * @return Collection<int, Notification>
     */
    public function getUnreadNotifications(string $userId, int $limit = 5): Collection
    {
        return $this->repository->getUnreadNotifications($userId, $limit);
    }

    /**
     * Get unread notification count.
     */
    public function getUnreadCount(string $userId): int
    {
        $query = new GetUnreadNotificationCountQuery($userId);

        return $this->getUnreadCountHandler->handle($query);
    }

    /**
     * Get notification by ID.
     */
    public function getNotificationById(string $id): ?Notification
    {
        return $this->repository->findById($id);
    }

    /**
     * Cancel a scheduled notification.
     */
    public function cancelNotification(string $id, string $reason = ''): bool
    {
        return $this->repository->cancel($id, $reason);
    }

    /**
     * Get notification statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(
        ?string $userId = null,
        ?DateTime $startDate = null,
        ?DateTime $endDate = null,
    ): array {
        return $this->repository->getStatistics($userId, $startDate, $endDate);
    }

    /**
     * Search notifications with filters.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Notification>
     */
    public function searchNotifications(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $page = 1,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->repository->search(
            filters: $filters,
            sortBy: $sortBy,
            sortOrder: $sortOrder,
            page: $page,
            perPage: $perPage,
        );
    }

    /**
     * Process scheduled notifications.
     */
    public function processScheduledNotifications(int $limit = 100): int
    {
        $notifications = $this->repository->getScheduledNotifications($limit);
        $processed = 0;

        foreach ($notifications as $notification) {
            if ($notification->canBeSentNow()) {
                try {
                    $this->sendNotification($notification->id);
                    $processed++;
                } catch (Exception $e) {
                    // Log error but continue processing other notifications
                    logger()->error('Failed to send scheduled notification', [
                        'notification_id' => $notification->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $processed;
    }

    /**
     * Clean up old notifications.
     */
    public function cleanupOldNotifications(int $daysOld = 90): int
    {
        return $this->repository->cleanupOldNotifications($daysOld);
    }

    /**
     * Get delivery metrics for analytics.
     *
     * @return array<string, mixed>
     */
    public function getDeliveryMetrics(
        ?DateTime $startDate = null,
        ?DateTime $endDate = null,
    ): array {
        return $this->repository->getDeliveryMetrics($startDate, $endDate);
    }

    /**
     * Map recipient type to notifiable model class.
     */
    private function getNotifiableType(string $recipientType): string
    {
        return match ($recipientType) {
            'user', 'organization_admin', 'admin' => User::class,
            'organization' => 'Modules\\Organization\\Infrastructure\\Laravel\\Models\\Organization',
            default => throw new InvalidArgumentException("Unsupported recipient type: {$recipientType}"),
        };
    }
}
