<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Notification\Domain\Event\NotificationCancelledEvent;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Psr\Log\LoggerInterface;

/**
 * Handler for canceling scheduled notifications.
 *
 * Supports canceling individual scheduled notifications or entire recurring series.
 */
final readonly class CancelScheduledNotificationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {}

    /**
     * Handle the cancel scheduled notification command.
     *
     * @return array<string, mixed>
     */
    public function handle(CommandInterface $command): mixed
    {
        if (! $command instanceof CancelScheduledNotificationCommand) {
            throw new InvalidArgumentException('Expected CancelScheduledNotificationCommand');
        }

        $this->validateCommand($command);

        return DB::transaction(function () use ($command): array {
            // Get the notification
            $notification = $this->repository->findById((string) $command->notificationId);

            if (! $notification instanceof Notification) {
                throw NotificationException::notificationNotFound((string) $command->notificationId);
            }

            // Verify the notification belongs to the user (sender or recipient)
            if ($notification->notifiable_id !== $command->userId
                && $notification->sender_id !== $command->userId) {
                throw NotificationException::accessDenied(
                    "Notification {$command->notificationId} does not belong to user {$command->userId}",
                );
            }

            // Check if notification is already sent or cancelled
            if (! in_array($notification->status, ['scheduled', 'pending'], true)) {
                throw NotificationException::invalidStatus(
                    "Cannot cancel notification with status '{$notification->status}'. Only scheduled or pending notifications can be cancelled.",
                );
            }

            $cancelledAt = Carbon::now();
            $cancelledNotifications = [];

            try {
                if ($command->cancelRecurring && $this->isRecurringNotification($notification)) {
                    $cancelledNotifications = $this->cancelRecurringSeries($notification, $command, $cancelledAt);
                } else {
                    $cancelledNotifications = [$this->cancelSingleNotification($notification, $command, $cancelledAt)];
                }

                // Dispatch cancellation events
                foreach ($cancelledNotifications as $cancelledNotification) {
                    Event::dispatch(new NotificationCancelledEvent(
                        notification: $cancelledNotification,
                        cancelledBy: $command->userId,
                        cancelledAt: $cancelledAt,
                        reason: $command->reason,
                        recurringCancellation: $command->cancelRecurring,
                        source: 'command_handler',
                        context: array_merge($command->metadata, [
                            'command_class' => $command::class,
                            'cancellation_type' => $command->cancelRecurring ? 'recurring_series' : 'single',
                        ]),
                    ));
                }

                $this->logger->info('Scheduled notification(s) cancelled successfully', [
                    'notification_id' => $command->notificationId,
                    'user_id' => $command->userId,
                    'cancel_recurring' => $command->cancelRecurring,
                    'cancelled_count' => count($cancelledNotifications),
                    'reason' => $command->reason,
                ]);

                return [
                    'cancelled' => true,
                    'notification_id' => $command->notificationId,
                    'cancelled_count' => count($cancelledNotifications),
                    'cancel_recurring' => $command->cancelRecurring,
                    'cancelled_at' => $cancelledAt->toISOString(),
                    'notifications' => $cancelledNotifications,
                ];
            } catch (Exception $e) {
                $this->logger->error('Failed to cancel scheduled notification', [
                    'notification_id' => $command->notificationId,
                    'user_id' => $command->userId,
                    'error' => $e->getMessage(),
                ]);

                throw NotificationException::cancellationFailed(
                    "Failed to cancel scheduled notification {$command->notificationId}: {$e->getMessage()}",
                );
            }
        });
    }

    /**
     * Cancel a single notification.
     */
    private function cancelSingleNotification(
        Notification $notification,
        CancelScheduledNotificationCommand $command,
        Carbon $cancelledAt,
    ): Notification {
        $metadata = array_merge($notification->metadata ?? [], [
            'cancelled_at' => $cancelledAt->toISOString(),
            'cancelled_by' => $command->userId,
            'cancellation_reason' => $command->reason,
            'cancellation_metadata' => $command->metadata,
        ]);

        $this->repository->updateById($notification->id, [
            'status' => 'cancelled',
            'metadata' => $metadata,
        ]);

        $updatedNotification = $this->repository->findById($notification->id);

        if (! $updatedNotification instanceof Notification) {
            throw NotificationException::notificationNotFound((string) $notification->id);
        }

        return $updatedNotification;
    }

    /**
     * Cancel an entire recurring notification series.
     *
     * @return array<int, Notification>
     */
    private function cancelRecurringSeries(
        Notification $notification,
        CancelScheduledNotificationCommand $command,
        Carbon $cancelledAt,
    ): array {
        // Get the schedule ID from metadata
        $scheduleId = $notification->metadata['schedule_id'] ?? null;

        if ($scheduleId === null) {
            throw NotificationException::invalidData(
                'schedule_id',
                'Cannot cancel recurring series: schedule ID not found in notification metadata',
            );
        }

        // Find all notifications in the recurring series that are still scheduled
        $recurringNotifications = $this->repository->search([
            'status' => ['scheduled', 'pending'],
            'metadata->schedule_id' => $scheduleId,
        ])->items();

        $cancelledNotifications = [];

        foreach ($recurringNotifications as $recurringNotification) {
            $metadata = array_merge($recurringNotification->metadata ?? [], [
                'cancelled_at' => $cancelledAt->toISOString(),
                'cancelled_by' => $command->userId,
                'cancellation_reason' => $command->reason,
                'recurring_series_cancelled' => true,
                'cancellation_metadata' => $command->metadata,
            ]);

            $this->repository->updateById($recurringNotification->id, [
                'status' => 'cancelled',
                'metadata' => $metadata,
            ]);

            $cancelled = $this->repository->findById($recurringNotification->id);

            if ($cancelled instanceof Notification) {
                $cancelledNotifications[] = $cancelled;
            }
        }

        return $cancelledNotifications;
    }

    /**
     * Check if a notification is part of a recurring series.
     */
    private function isRecurringNotification(Notification $notification): bool
    {
        return isset($notification->metadata['is_recurring']) && $notification->metadata['is_recurring'] === true;
    }

    /**
     * Validate the cancel scheduled notification command.
     */
    private function validateCommand(CancelScheduledNotificationCommand $command): void
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
                'Cancellation reason cannot be empty when provided',
            );
        }

        if ($command->reason !== null && strlen($command->reason) > 500) {
            throw NotificationException::invalidData(
                'reason',
                'Cancellation reason cannot exceed 500 characters',
            );
        }

        // metadata is already typed as array in the command
    }
}
