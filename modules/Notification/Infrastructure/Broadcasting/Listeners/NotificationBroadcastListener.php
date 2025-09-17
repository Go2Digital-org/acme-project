<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Broadcasting\Listeners;

use DateTime;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Domain\Event\NotificationCreatedEvent;
use Modules\Notification\Infrastructure\Broadcasting\Service\NotificationBroadcaster;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener that broadcasts notification-related events.
 *
 * This listener handles domain events related to notifications and broadcasts
 * them to appropriate channels for real-time updates.
 */
class NotificationBroadcastListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationBroadcaster $broadcaster,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle notification created events.
     */
    public function handle(NotificationCreatedEvent $event): void
    {
        try {
            $notification = $event->notification;

            $this->broadcaster->broadcastNotificationCreated(
                $notification,
                [
                    'id' => $notification->id,
                    'notifiable_id' => $notification->notifiable_id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'priority' => $notification->priority,
                    'channel' => $notification->channel,
                    'created_at' => $notification->created_at->format('c'),
                ],
            );
        } catch (Exception $e) {
            $this->logger->error('Failed to broadcast notification created', [
                'notification_id' => $event->notification->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * The queue to push the job onto.
     */
    public function viaQueue(): string
    {
        return 'broadcasts';
    }

    /**
     * Determine the number of times the job may be attempted.
     */
    public function retryUntil(): DateTime
    {
        return now()->addMinutes(5);
    }

    /**
     * Handle a job failure.
     */
    public function failed(object $event, Throwable $exception): void
    {
        $eventType = class_basename($event);

        $this->logger->error('Notification broadcast permanently failed', [
            'event_type' => $eventType,
            'error' => $exception->getMessage(),
        ]);
    }
}
