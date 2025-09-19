<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Notification\Domain\Event\NotificationReadEvent;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\Notification\Domain\ValueObject\NotificationStatus;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;
use Psr\Log\LoggerInterface;

/**
 * Handler for getting notification details query.
 *
 * Returns detailed notification information with optional metadata,
 * events history, and automatic read marking.
 */
final readonly class GetNotificationDetailsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(QueryInterface $query): mixed
    {
        if (! $query instanceof GetNotificationDetailsQuery) {
            throw new InvalidArgumentException('Expected GetNotificationDetailsQuery');
        }

        $this->validateQuery($query);

        try {
            // Get the notification
            $notification = $this->repository->findById((string) $query->notificationId);

            if (! $notification instanceof Notification) {
                throw NotificationException::notificationNotFound((string) $query->notificationId);
            }

            // Verify the notification belongs to the user
            if ($notification->notifiable_id !== $query->userId && $notification->sender_id !== $query->userId) {
                throw NotificationException::accessDenied(
                    "Notification {$query->notificationId} does not belong to user {$query->userId}",
                );
            }

            // Mark as read if requested and user is the recipient and notification is unread
            if ($query->markAsRead && (string) $notification->notifiable_id === (string) $query->userId && $notification->read_at === null) { // @phpstan-ignore-line
                $readAt = Carbon::now();
                $this->repository->updateById($notification->id, [
                    'status' => NotificationStatus::SENT,
                    'read_at' => $readAt,
                ]);

                // Refresh notification data
                $notification = $this->repository->findById($notification->id);
                if (! $notification instanceof Notification) {
                    throw new Exception('Notification not found after update');
                }

                // Dispatch read event
                Event::dispatch(new NotificationReadEvent(
                    notification: $notification,
                    readBy: (string) $query->userId,
                    readContext: [
                        'source' => 'details_query_handler',
                        'query_class' => $query::class,
                        'auto_marked_read' => true,
                    ],
                    readAt: $readAt->toDateTimeImmutable(),
                ));
            }

            // Build response data
            $details = [
                'id' => $notification->id,
                'notifiable_id' => $notification->notifiable_id,
                'sender_id' => $notification->sender_id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'channel' => $notification->channel,
                'priority' => $notification->priority,
                'status' => $notification->status,
                'data' => $notification->data,
                'created_at' => $notification->created_at->toISOString(),
                'updated_at' => $notification->updated_at->toISOString(),
                'read_at' => $notification->read_at?->toISOString(),
                'sent_at' => $notification->sent_at?->toISOString(),
                'scheduled_for' => $notification->scheduled_for?->toISOString(),
                'failed_at' => $notification->metadata['failed_at'] ?? null,
            ];

            // Include metadata if requested
            if ($query->includeMetadata) {
                $details['metadata'] = $notification->metadata;
            }

            // Include events history if requested
            if ($query->includeEvents) {
                $details['events'] = $this->getNotificationEvents();
            }

            // Add computed fields
            $details['is_read'] = $notification->read_at !== null;
            $details['is_scheduled'] = $notification->scheduled_for !== null;
            $details['is_sent'] = in_array($notification->status, ['sent', 'delivered'], true);
            $details['is_failed'] = $notification->status === NotificationStatus::FAILED;
            $details['age_seconds'] = $notification->created_at ? Carbon::now()->diffInSeconds($notification->created_at) : null;

            // Add scheduling information if applicable
            if ($notification->scheduled_for) {
                $details['is_past_due'] = $notification->scheduled_for->isPast();
                $details['time_until_scheduled'] = $notification->scheduled_for->diffInSeconds(Carbon::now());
            }

            $this->logger->debug('Notification details retrieved successfully', [
                'notification_id' => $query->notificationId,
                'user_id' => $query->userId,
                'marked_as_read' => $query->markAsRead && (string) $notification->notifiable_id === (string) $query->userId, // @phpstan-ignore-line
                'include_metadata' => $query->includeMetadata,
                'include_events' => $query->includeEvents,
            ]);

            return $details;
        } catch (NotificationException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Failed to get notification details', [
                'notification_id' => $query->notificationId,
                'user_id' => $query->userId,
                'error' => $e->getMessage(),
            ]);

            throw NotificationException::queryFailed(
                "Failed to get notification details: {$e->getMessage()}",
            );
        }
    }

    /**
     * Get events history for a notification (simulated - in real implementation,
     * this would query an event log table or audit trail).
     *
     * @return array<int, array<string, string>>
     */
    private function getNotificationEvents(): array
    {
        // In a real system, you would query an events/audit log table
        return [
            [
                'event' => 'created',
                'timestamp' => Carbon::now()->subMinutes(10)->toISOString() ?? '',
                'source' => 'command_handler',
            ],
            // Additional events would be retrieved from event log
        ];
    }

    /**
     * Validate the get notification details query.
     */
    private function validateQuery(GetNotificationDetailsQuery $query): void
    {
        if ($query->notificationId <= 0) {
            throw NotificationException::invalidData(
                'notification_id',
                'Notification ID must be a positive integer',
            );
        }

        if ($query->userId <= 0) {
            throw NotificationException::invalidRecipient('User ID must be a positive integer');
        }
    }
}
