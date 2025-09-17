<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Listeners;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Domain\Event\NotificationSentEvent;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener that broadcasts notification sent events for real-time updates.
 *
 * This listener handles broadcasting notification events to connected clients
 * via WebSockets or Server-Sent Events for real-time notifications.
 */
final class BroadcastNotificationSentListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the queued listener may run.
     */
    public int $timeout = 30;

    public function __construct(
        private readonly BroadcastManager $broadcaster,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the notification sent event.
     */
    public function handle(NotificationSentEvent $event): void
    {
        try {
            // Broadcast to user's private channel
            $this->broadcastToUser($event);

            // Broadcast to admin channels for high-priority notifications
            if ($event->notification->isHighPriority()) {
                $this->broadcastToAdmins($event);
            }

            $this->logger->debug('Notification sent event broadcasted', [
                'notification_id' => $event->notification->id,
                'notifiable_id' => $event->notification->notifiable_id,
                'delivery_channel' => $event->deliveryChannel,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to broadcast notification sent event', [
                'notification_id' => $event->notification->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(NotificationSentEvent $event, Throwable $exception): void
    {
        $this->logger->error('Failed to broadcast notification sent event', [
            'notification_id' => $event->notification->id ?? 'unknown',
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Broadcast notification to user's private channel.
     */
    private function broadcastToUser(NotificationSentEvent $event): void
    {
        $channel = "user.{$event->notification->notifiable_id}.notifications";

        $payload = [
            'id' => $event->notification->id,
            'type' => is_object($event->notification->type) ? $event->notification->type->value : $event->notification->type,
            'title' => $event->notification->title,
            'message' => $event->notification->message,
            'priority' => is_object($event->notification->priority) ? $event->notification->priority->value : $event->notification->priority,
            'delivery_channel' => $event->deliveryChannel,
            'sent_at' => $event->notification->sent_at?->toISOString(),
            'actions' => $event->notification->actions,
            'metadata' => $event->notification->metadata,
        ];

        $this->broadcaster->connection('pusher')->push(
            channel: $channel,
            event: 'notification.sent',
            data: $payload,
        );
    }

    /**
     * Broadcast high-priority notifications to admin channels.
     */
    private function broadcastToAdmins(NotificationSentEvent $event): void
    {
        $channel = 'admin.notifications';

        $payload = [
            'id' => $event->notification->id,
            'notifiable_id' => $event->notification->notifiable_id,
            'type' => is_object($event->notification->type) ? $event->notification->type->value : $event->notification->type,
            'title' => $event->notification->title,
            'priority' => is_object($event->notification->priority) ? $event->notification->priority->value : $event->notification->priority,
            'delivery_channel' => $event->deliveryChannel,
            'sent_at' => $event->notification->sent_at?->toISOString(),
        ];

        $this->broadcaster->connection('pusher')->push(
            channel: $channel,
            event: 'admin.notification.sent',
            data: $payload,
        );
    }
}
