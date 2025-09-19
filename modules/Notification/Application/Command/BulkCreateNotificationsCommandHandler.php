<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Command;

use Carbon\CarbonInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Notification\Domain\Event\NotificationCreatedEvent;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\Notification\Domain\ValueObject\NotificationChannel;
use Modules\Notification\Domain\ValueObject\NotificationPriority;
use Modules\Notification\Domain\ValueObject\NotificationType;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Psr\Log\LoggerInterface;

/**
 * Handler for bulk creating notifications.
 *
 * Validates all notifications, creates them in a single transaction,
 * and dispatches events for each created notification.
 */
final readonly class BulkCreateNotificationsCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {}

    /**
     * Handle the bulk create notifications command.
     *
     * @return array<int, Notification>
     */
    public function handle(CommandInterface $command): mixed
    {
        if (! $command instanceof BulkCreateNotificationsCommand) {
            throw new InvalidArgumentException('Expected BulkCreateNotificationsCommand');
        }

        $this->validateCommand($command);

        $startTime = microtime(true);
        $createdNotifications = [];

        try {
            $createdNotifications = DB::transaction(function () use ($command) {
                $notifications = [];

                foreach ($command->notifications as $index => $notificationData) {
                    try {
                        // Merge global metadata with individual notification metadata
                        $metadata = array_merge(
                            $command->globalMetadata,
                            $notificationData['metadata'],
                        );

                        // Add batch information to metadata
                        if ($command->batchId !== null) {
                            $metadata['batch_id'] = $command->batchId;
                        }

                        if ($command->source !== null) {
                            $metadata['source'] = $command->source;
                        }

                        $metadata['bulk_index'] = $index;
                        $metadata['bulk_total'] = count($command->notifications);

                        $notification = $this->repository->create([
                            'notifiable_id' => $notificationData['notifiable_id'],
                            'sender_id' => $notificationData['sender_id'] ?? null,
                            'title' => $notificationData['title'],
                            'message' => $notificationData['message'],
                            'type' => $notificationData['type'],
                            'channel' => $notificationData['channel'],
                            'priority' => $notificationData['priority'],
                            'status' => 'pending',
                            'data' => $notificationData['data'],
                            'metadata' => $metadata,
                            'scheduled_for' => $notificationData['scheduled_for'] ?? null,
                        ]);

                        $notifications[] = $notification;
                    } catch (Exception $e) {
                        $this->logger->error('Failed to create notification in bulk operation', [
                            'index' => $index,
                            'notification_data' => $notificationData,
                            'error' => $e->getMessage(),
                            'batch_id' => $command->batchId,
                        ]);

                        throw NotificationException::bulkCreationFailed(
                            "Failed to create notification at index {$index}: {$e->getMessage()}",
                        );
                    }
                }

                return $notifications;
            });

            // Dispatch events for all successfully created notifications
            foreach ($createdNotifications as $notification) {
                Event::dispatch(new NotificationCreatedEvent(
                    notification: $notification,
                    source: 'bulk_command_handler',
                    context: [
                        'command_class' => $command::class,
                        'batch_id' => $command->batchId,
                        'bulk_total' => count($command->notifications),
                        'created_via' => 'bulk_application_service',
                    ],
                ));
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->info('Bulk notification creation completed successfully', [
                'count' => count($createdNotifications),
                'batch_id' => $command->batchId,
                'execution_time_ms' => $executionTime,
                'source' => $command->source,
            ]);

            return $createdNotifications;
        } catch (Exception $e) {
            $this->logger->error('Bulk notification creation failed', [
                'error' => $e->getMessage(),
                'batch_id' => $command->batchId,
                'notifications_count' => count($command->notifications),
                'created_count' => count($createdNotifications),
            ]);

            throw $e;
        }
    }

    /**
     * Validate the bulk command data.
     */
    private function validateCommand(BulkCreateNotificationsCommand $command): void
    {
        if ($command->notifications === []) {
            throw NotificationException::invalidData('notifications', 'Notifications array cannot be empty');
        }

        if (count($command->notifications) > 1000) {
            throw NotificationException::bulkCreationFailed('Cannot create more than 1000 notifications in a single batch');
        }

        foreach ($command->notifications as $index => $notificationData) {
            $this->validateNotificationData($notificationData, (int) $index);
        }
    }

    /**
     * Validate individual notification data in the bulk operation.
     *
     * @param  array<string, mixed>  $notificationData
     */
    private function validateNotificationData(array $notificationData, int $index): void
    {
        $requiredFields = ['notifiable_id', 'title', 'message', 'type', 'channel', 'priority'];

        foreach ($requiredFields as $field) {
            if (! isset($notificationData[$field])) {
                throw NotificationException::invalidData(
                    "notifications[{$index}].{$field}",
                    "Required field '{$field}' is missing",
                );
            }
        }

        // Validate recipient ID
        if (empty($notificationData['notifiable_id'])) {
            throw NotificationException::invalidRecipient(
                "Recipient ID cannot be empty at index {$index}",
            );
        }

        // Validate notification type
        $type = $notificationData['type'];
        if (! is_string($type) || ! NotificationType::isValid($type)) {
            throw NotificationException::invalidType(
                "Invalid notification type '" . (is_string($type) ? $type : 'invalid') . "' at index {$index}",
            );
        }

        // Validate notification channel
        $channel = $notificationData['channel'];
        if (! is_string($channel) || ! NotificationChannel::isValid($channel)) {
            throw NotificationException::unsupportedChannel(
                "Unsupported channel '" . (is_string($channel) ? $channel : 'invalid') . "' at index {$index}",
            );
        }

        // Validate notification priority
        $priority = $notificationData['priority'];
        if (! is_string($priority) || ! NotificationPriority::isValid($priority)) {
            throw NotificationException::invalidPriority(
                "Invalid priority '" . (is_string($priority) ? $priority : 'invalid') . "' at index {$index}",
            );
        }

        // Validate title and message
        $title = $notificationData['title'];
        if (! is_string($title) || in_array(trim($title), ['', '0'], true)) {
            throw NotificationException::invalidData(
                "notifications[{$index}].title",
                'Title must be a non-empty string',
            );
        }

        $message = $notificationData['message'];
        if (! is_string($message) || in_array(trim($message), ['', '0'], true)) {
            throw NotificationException::invalidData(
                "notifications[{$index}].message",
                'Message must be a non-empty string',
            );
        }

        // Validate scheduled time if provided
        if (isset($notificationData['scheduled_for'])
            && $notificationData['scheduled_for'] instanceof CarbonInterface
            && $notificationData['scheduled_for']->isPast()) {
            throw NotificationException::invalidData(
                "notifications[{$index}].scheduled_for",
                'Scheduled time cannot be in the past',
            );
        }

        // Validate data arrays
        if (isset($notificationData['data']) && ! is_array($notificationData['data'])) {
            throw NotificationException::invalidData(
                "notifications[{$index}].data",
                'Data must be an array',
            );
        }

        if (isset($notificationData['metadata']) && ! is_array($notificationData['metadata'])) {
            throw NotificationException::invalidData(
                "notifications[{$index}].metadata",
                'Metadata must be an array',
            );
        }
    }
}
