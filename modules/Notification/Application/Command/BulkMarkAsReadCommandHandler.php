<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Notification\Domain\Event\NotificationReadEvent;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Psr\Log\LoggerInterface;

/**
 * Handler for bulk marking notifications as read.
 *
 * Supports marking specific notifications or all notifications for a user,
 * with optional filtering by type and channel.
 */
final readonly class BulkMarkAsReadCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {}

    /**
     * Handle the bulk mark as read command.
     *
     * @return array{count: int, notifications: array<Notification>}
     */
    public function handle(CommandInterface $command): mixed
    {
        if (! $command instanceof BulkMarkAsReadCommand) {
            throw new InvalidArgumentException('Expected BulkMarkAsReadCommand');
        }

        $this->validateCommand($command);

        $startTime = microtime(true);
        $updatedNotifications = [];
        $count = 0;

        try {
            [$count, $updatedNotifications] = DB::transaction(function () use ($command): array {
                if ($command->markAllAsRead) {
                    return $this->markAllUserNotificationsAsRead($command);
                }

                return $this->markSpecificNotificationsAsRead($command);
            });

            // Dispatch events for all updated notifications
            foreach ($updatedNotifications as $notification) {
                Event::dispatch(new NotificationReadEvent(
                    notification: $notification,
                    readBy: 'system',
                    readContext: array_merge($command->metadata, [
                        'command_class' => $command::class,
                        'bulk_operation' => true,
                        'bulk_count' => $count,
                        'mark_all' => $command->markAllAsRead,
                    ]),
                    readAt: null,
                ));
            }
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->info('Bulk mark as read completed successfully', [
                'user_id' => $command->userId,
                'count' => $count,
                'mark_all' => $command->markAllAsRead,
                'execution_time_ms' => $executionTime,
                'type_filter' => $command->type,
                'channel_filter' => $command->channel,
            ]);

            return [
                'count' => $count,
                'notifications' => $updatedNotifications,
            ];
        } catch (Exception $e) {
            $this->logger->error('Bulk mark as read failed', [
                'error' => $e->getMessage(),
                'user_id' => $command->userId,
                'notification_ids' => $command->notificationIds,
                'mark_all' => $command->markAllAsRead,
            ]);

            throw $e;
        }
    }

    /**
     * Mark all user notifications as read with optional filters.
     *
     * @return array{int, array<Notification>}
     */
    private function markAllUserNotificationsAsRead(BulkMarkAsReadCommand $command): array
    {
        $filters = [
            'notifiable_id' => $command->userId,
            'status' => 'unread',
        ];

        if ($command->type !== null) {
            $filters['type'] = $command->type;
        }

        if ($command->channel !== null) {
            $filters['channel'] = $command->channel;
        }

        // Get notifications that will be updated (for event dispatching)
        $notifications = $this->repository->search($filters)->items();

        // Update notifications
        $count = $this->repository->markAllAsReadForRecipient((string) $command->userId);
        // Refresh notifications to get updated data
        $updatedNotifications = [];

        foreach ($notifications as $notification) {
            $updated = $this->repository->findById($notification->id);

            if ($updated instanceof Notification) {
                $updatedNotifications[] = $updated;
            }
        }

        return [$count, $updatedNotifications];
    }

    /**
     * Mark specific notifications as read.
     *
     * @return array{int, array<Notification>}
     */
    private function markSpecificNotificationsAsRead(BulkMarkAsReadCommand $command): array
    {
        $updatedNotifications = [];
        $readAt = Carbon::now();

        foreach ($command->notificationIds as $notificationId) {
            $notification = $this->repository->findById((string) $notificationId);

            if (! $notification instanceof Notification) {
                $this->logger->warning('Notification not found during bulk mark as read', [
                    'notification_id' => $notificationId,
                    'user_id' => $command->userId,
                ]);

                continue;
            }

            // Verify the notification belongs to the user
            if ((string) $notification->notifiable_id !== (string) $command->userId) {
                throw NotificationException::accessDenied(
                    "Notification {$notificationId} does not belong to user {$command->userId}",
                );
            }

            // Skip if already read
            if ($notification->status === 'read') {
                continue;
            }

            $updateResult = $this->repository->updateById($notification->id, [
                'status' => 'read',
                'read_at' => $readAt,
            ]);

            if ($updateResult) {
                $updated = $this->repository->findById($notification->id);

                if ($updated instanceof Notification) {
                    $updatedNotifications[] = $updated;
                }
            }
        }

        return [count($updatedNotifications), $updatedNotifications];
    }

    /**
     * Validate the bulk mark as read command.
     */
    private function validateCommand(BulkMarkAsReadCommand $command): void
    {
        if ($command->userId <= 0) {
            throw NotificationException::invalidRecipient('User ID must be a positive integer');
        }

        if (! $command->markAllAsRead && $command->notificationIds === []) {
            throw NotificationException::invalidData(
                'notification_ids',
                'Either markAllAsRead must be true or notificationIds must be provided',
            );
        }

        if ($command->markAllAsRead && $command->notificationIds !== []) {
            throw NotificationException::invalidData(
                'operation',
                'Cannot specify both markAllAsRead and specific notification IDs',
            );
        }

        if (! $command->markAllAsRead && count($command->notificationIds) > 100) {
            throw NotificationException::invalidData(
                'notification_ids',
                'Cannot mark more than 100 specific notifications as read in a single operation',
            );
        }

        // Validate notification IDs are positive integers
        foreach ($command->notificationIds as $id) {
            if ($id <= 0) {
                throw NotificationException::invalidData(
                    'notification_ids',
                    'All notification IDs must be positive integers',
                );
            }
        }
    }
}
