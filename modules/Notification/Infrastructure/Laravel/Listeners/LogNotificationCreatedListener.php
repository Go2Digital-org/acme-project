<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Domain\Event\NotificationCreatedEvent;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener that logs notification creation events.
 *
 * This listener provides audit trail and debugging capabilities for notification
 * creation events by logging them with appropriate context.
 */
final class LogNotificationCreatedListener implements ShouldQueue
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
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the notification created event.
     */
    public function handle(NotificationCreatedEvent $event): void
    {
        $this->logger->info('Notification created', [
            'notification_id' => $event->notification->id,
            'notifiable_id' => $event->notification->notifiable_id,
            'type' => $event->notification->type,
            'channel' => $event->notification->channel,
            'priority' => $event->notification->priority,
            'source' => $event->source,
            'context' => $event->context,
            'title' => $event->notification->title,
            'scheduled_for' => $event->notification->scheduled_for?->toISOString(),
            'expires_at' => $event->notification->expires_at?->toISOString(),
            'created_at' => $event->getOccurredAt()->format('Y-m-d H:i:s'),
        ]);

        // Log high-priority notifications with additional context
        if ($event->notification->isHighPriority()) {
            $this->logger->notice('High-priority notification created', [
                'notification_id' => $event->notification->id,
                'notifiable_id' => $event->notification->notifiable_id,
                'priority' => $event->notification->priority,
                'type' => $event->notification->type,
                'title' => $event->notification->title,
                'source' => $event->source,
            ]);
        }

        // Track notification creation metrics
        $this->recordMetrics($event);
    }

    /**
     * Handle a job failure.
     */
    public function failed(NotificationCreatedEvent $event, Throwable $exception): void
    {
        $this->logger->error('Failed to log notification creation', [
            'notification_id' => $event->notification->id ?? 'unknown',
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Record metrics for notification creation.
     */
    private function recordMetrics(NotificationCreatedEvent $event): void
    {
        try {
            // Use Laravel's built-in metrics or integrate with external service
            if (function_exists('metrics')) {
                metrics()->increment('notifications.created', [
                    'type' => $event->notification->type,
                    'channel' => $event->notification->channel,
                    'priority' => $event->notification->priority,
                    'source' => $event->source,
                ]);
            }
        } catch (Throwable $e) {
            $this->logger->warning('Failed to record notification creation metrics', [
                'error' => $e->getMessage(),
                'notification_id' => $event->notification->id,
            ]);
        }
    }
}
