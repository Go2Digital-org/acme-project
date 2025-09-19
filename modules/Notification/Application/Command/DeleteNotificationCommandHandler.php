<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Notification\Domain\Event\NotificationDeletedEvent;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Psr\Log\LoggerInterface;

/**
 * Handler for deleting notifications.
 *
 * Supports both soft delete (archiving) and hard delete operations.
 * Validates user ownership before deletion.
 */
final readonly class DeleteNotificationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {}

    /**
     * Handle the delete notification command.
     *
     * @return array<string, mixed>
     */
    public function handle(CommandInterface $command): array
    {
        if (! $command instanceof DeleteNotificationCommand) {
            throw new InvalidArgumentException('Expected DeleteNotificationCommand');
        }

        $this->validateCommand($command);

        DB::transaction(function () use ($command): void {
            // Get the notification to verify ownership and capture data for event
            $notification = $this->repository->findById((string) $command->notificationId);

            if (! $notification instanceof Notification) {
                throw NotificationException::notificationNotFound((string) $command->notificationId);
            }

            // Verify the notification belongs to the user
            if ((string) $notification->notifiable_id !== (string) $command->userId) {
                throw NotificationException::accessDenied(
                    "Notification {$command->notificationId} does not belong to user {$command->userId}",
                );
            }

            $deletedAt = Carbon::now();
            $wasHardDeleted = false;

            try {
                if ($command->hardDelete) {
                    // Perform hard delete
                    $success = $this->repository->delete((string) $command->notificationId);
                    $wasHardDeleted = true;
                } else {
                    // Perform soft delete (archive)
                    $this->repository->update($notification, [
                        'status' => 'deleted',
                        'deleted_at' => $deletedAt,
                        'metadata' => array_merge($notification->metadata ?? [], [
                            'deletion_reason' => $command->reason,
                            'deleted_by_user' => $command->userId,
                            'deletion_metadata' => $command->metadata,
                        ]),
                    ]);
                    $success = true;
                }

                if (! $success) {
                    throw NotificationException::deletionFailed(
                        "Failed to delete notification {$command->notificationId}",
                    );
                }

                // Dispatch deletion event
                Event::dispatch(new NotificationDeletedEvent(
                    notificationId: $command->notificationId,
                    userId: $command->userId,
                    deletedAt: $deletedAt,
                    hardDelete: $command->hardDelete,
                    reason: $command->reason,
                    originalNotification: $notification,
                    source: 'command_handler',
                    context: array_merge($command->metadata, [
                        'command_class' => $command::class,
                        'deletion_type' => $command->hardDelete ? 'hard' : 'soft',
                    ]),
                ));

                $this->logger->info('Notification deleted successfully', [
                    'notification_id' => $command->notificationId,
                    'user_id' => $command->userId,
                    'hard_delete' => $command->hardDelete,
                    'reason' => $command->reason,
                ]);

                // Deletion completed successfully
            } catch (Exception $e) {
                $this->logger->error('Failed to delete notification', [
                    'notification_id' => $command->notificationId,
                    'user_id' => $command->userId,
                    'hard_delete' => $command->hardDelete,
                    'error' => $e->getMessage(),
                ]);

                throw NotificationException::deletionFailed(
                    "Failed to delete notification {$command->notificationId}: {$e->getMessage()}",
                );
            }
        });

        return [
            'deleted' => true,
            'notification_id' => $command->notificationId,
            'hard_delete' => $command->hardDelete,
            'deleted_at' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Validate the delete notification command.
     */
    private function validateCommand(DeleteNotificationCommand $command): void
    {
        if ($command->notificationId <= 0) {
            throw NotificationException::invalidData(
                'notification_id',
                'Notification ID must be a positive integer',
            );
        }

        if ($command->userId <= 0) {
            throw NotificationException::invalidRecipient('User ID must be a positive integer');
        }

        if ($command->reason !== null && trim($command->reason) === '') {
            throw NotificationException::invalidData(
                'reason',
                'Deletion reason cannot be empty when provided',
            );
        }

        if ($command->reason !== null && strlen($command->reason) > 500) {
            throw NotificationException::invalidData(
                'reason',
                'Deletion reason cannot exceed 500 characters',
            );
        }

        // metadata is already typed as array in the command
    }
}
