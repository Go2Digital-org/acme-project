<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Listeners;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Notification\Domain\Event\NotificationFailedEvent;
use Modules\Notification\Infrastructure\Laravel\Mail\NotificationFailureAlert;
use Modules\Notification\Infrastructure\Laravel\Service\NotificationPerformanceMonitor;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener that handles notification delivery failures.
 *
 * This listener implements failure recovery strategies, alerting,
 * and retry logic to ensure critical notifications are delivered.
 */
final class HandleNotificationFailureListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the queued listener may run.
     */
    public int $timeout = 60;

    private const MAX_RETRY_ATTEMPTS = 3;

    private const RETRY_DELAY_MINUTES = 5;

    private const CRITICAL_NOTIFICATION_TYPES = [
        'security_alert',
        'payment_failed',
        'account_locked',
        'system_maintenance',
    ];

    public function __construct(
        private readonly NotificationPerformanceMonitor $performanceMonitor,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the notification failed event.
     */
    public function handle(NotificationFailedEvent $event): void
    {
        try {
            // Record failure metrics
            $this->recordFailureMetrics($event);

            // Determine failure handling strategy
            $strategy = $this->determineFailureStrategy($event);

            // Execute the appropriate failure handling strategy
            match ($strategy) {
                'retry' => $this->scheduleRetry($event),
                'escalate' => $this->escalateFailure($event),
                'fallback' => $this->useFallbackChannel($event),
                'alert' => $this->sendFailureAlert($event),
                default => $this->logFailure($event),
            };

            // Update notification status
            $this->updateNotificationStatus();

        } catch (Throwable $exception) {
            $this->logger->critical('Failed to handle notification failure', [
                'notification_id' => (string) $event->notification->id,
                'original_failure' => $event->failureReason,
                'handler_exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(NotificationFailedEvent $event, Throwable $exception): void
    {
        $this->logger->critical('Failed to process notification failure event', [
            'notification_id' => (string) $event->notification->id,
            'original_failure' => $event->failureReason ?? 'unknown',
            'handler_exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Send immediate alert for critical failure handling failures
        $this->sendCriticalAlert($event, $exception);
    }

    /**
     * Record failure metrics for monitoring and analysis.
     */
    private function recordFailureMetrics(NotificationFailedEvent $event): void
    {
        $notification = $event->notification;
        $errorType = $this->categorizeError($event->failureReason, $event->exception);

        $this->performanceMonitor->recordFailure(
            notificationId: (string) $notification->id,
            type: $notification->type,
            channel: $notification->channel ?? 'unknown',
            errorMessage: $event->failureReason,
            errorType: $errorType,
            context: array_merge($event->failureContext, [
                'attempt_count' => $notification->attempt_count ?? 1,
                'priority' => $notification->priority,
                'created_at' => $notification->created_at->toISOString(),
                'failed_at' => $event->getOccurredAt()->format('c'),
            ])
        );
    }

    /**
     * Determine the appropriate failure handling strategy.
     */
    private function determineFailureStrategy(NotificationFailedEvent $event): string
    {
        $notification = $event->notification;
        $attemptCount = $notification->attempt_count ?? 1;
        $errorType = $this->categorizeError($event->failureReason, $event->exception);

        // Critical notifications always get escalated
        if (in_array($notification->type, self::CRITICAL_NOTIFICATION_TYPES, true)) {
            return $attemptCount >= self::MAX_RETRY_ATTEMPTS ? 'escalate' : 'retry';
        }

        // Temporary errors get retries
        if ($this->isTemporaryError($errorType) && $attemptCount < self::MAX_RETRY_ATTEMPTS) {
            return 'retry';
        }

        // Channel-specific errors might have fallbacks
        if ($notification->channel !== null && $this->hasFallbackChannel($notification->channel, $errorType)) {
            return 'fallback';
        }

        // High-priority notifications get alerts
        if ($notification->isHighPriority()) {
            return 'alert';
        }

        return 'log';
    }

    /**
     * Schedule a retry for the failed notification.
     */
    private function scheduleRetry(NotificationFailedEvent $event): void
    {
        $notification = $event->notification;
        $attemptCount = $notification->attempt_count ?? 1;
        $delayMinutes = self::RETRY_DELAY_MINUTES * $attemptCount; // Exponential backoff

        $this->logger->info('Scheduling notification retry', [
            'notification_id' => (string) $notification->id,
            'attempt_count' => $attemptCount + 1,
            'delay_minutes' => $delayMinutes,
            'failure_reason' => $event->failureReason,
        ]);

        // In a real implementation, you would dispatch a delayed job
        // dispatch(new RetryNotificationJob($notification))->delay(now()->addMinutes($delayMinutes));
    }

    /**
     * Escalate failure to administrators.
     */
    private function escalateFailure(NotificationFailedEvent $event): void
    {
        $notification = $event->notification;

        $this->logger->error('Escalating notification failure', [
            'notification_id' => (string) $notification->id,
            'type' => $notification->type,
            'channel' => $notification->channel,
            'failure_reason' => $event->failureReason,
            'attempt_count' => $notification->attempt_count ?? 1,
        ]);

        // Send alert to administrators
        try {
            Mail::to(config('notification.admin_alerts.email'))
                ->send(new NotificationFailureAlert($event));
        } catch (Throwable $exception) {
            $this->logger->critical('Failed to send failure escalation alert', [
                'notification_id' => (string) $notification->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Attempt to use a fallback notification channel.
     */
    private function useFallbackChannel(NotificationFailedEvent $event): void
    {
        $notification = $event->notification;
        $fallbackChannel = $this->getFallbackChannel($notification->channel ?? 'database');

        $this->logger->info('Using fallback notification channel', [
            'notification_id' => (string) $notification->id,
            'original_channel' => $notification->channel,
            'fallback_channel' => $fallbackChannel,
            'failure_reason' => $event->failureReason,
        ]);

        // In a real implementation, you would create a new notification with the fallback channel
        // dispatch(new SendNotificationJob($notification, $fallbackChannel));
    }

    /**
     * Send failure alert to monitoring systems.
     */
    private function sendFailureAlert(NotificationFailedEvent $event): void
    {
        $this->logger->warning('Notification delivery failed - alerting monitoring', [
            'notification_id' => (string) $event->notification->id,
            'type' => $event->notification->type,
            'channel' => $event->notification->channel,
            'failure_reason' => $event->failureReason,
            'priority' => $event->notification->priority,
        ]);

        // Integration with monitoring services like Slack, PagerDuty, etc.
        // This would be implemented based on your monitoring stack
    }

    /**
     * Log failure for record-keeping.
     */
    private function logFailure(NotificationFailedEvent $event): void
    {
        $this->logger->info('Notification delivery failed', [
            'notification_id' => (string) $event->notification->id,
            'type' => $event->notification->type,
            'channel' => $event->notification->channel,
            'failure_reason' => $event->failureReason,
            'failure_context' => $event->failureContext,
        ]);
    }

    /**
     * Update notification status after failure handling.
     */
    private function updateNotificationStatus(): void
    {
        // In a real implementation, you would update the notification in the repository
        // $this->notificationRepository->updateStatus($event->notification->id, 'failed');
    }

    /**
     * Send critical alert when failure handler itself fails.
     */
    private function sendCriticalAlert(NotificationFailedEvent $event, Throwable $exception): void
    {
        // Last resort logging - this should always work
        error_log("CRITICAL: Notification failure handler failed - ID: {$event->notification->id}, Error: {$exception->getMessage()}");
    }

    /**
     * Categorize error type for better handling.
     */
    private function categorizeError(string $failureReason, ?Throwable $exception): string
    {
        $lowerReason = strtolower($failureReason);

        if (str_contains($lowerReason, 'timeout') || str_contains($lowerReason, 'connection')) {
            return 'connection';
        }

        if (str_contains($lowerReason, 'invalid') || str_contains($lowerReason, 'malformed')) {
            return 'validation';
        }

        if (str_contains($lowerReason, 'rate limit') || str_contains($lowerReason, 'quota')) {
            return 'rate_limit';
        }

        if (str_contains($lowerReason, 'authentication') || str_contains($lowerReason, 'unauthorized')) {
            return 'authentication';
        }

        if ($exception instanceof Exception) {
            return 'exception';
        }

        return 'unknown';
    }

    /**
     * Check if error is temporary and worth retrying.
     */
    private function isTemporaryError(string $errorType): bool
    {
        return in_array($errorType, ['connection', 'rate_limit', 'timeout'], true);
    }

    /**
     * Check if there's a fallback channel available.
     */
    private function hasFallbackChannel(string $channel, string $errorType): bool
    {
        $fallbacks = [
            'email' => ['sms', 'push'],
            'sms' => ['email', 'push'],
            'push' => ['email', 'database'],
        ];

        return isset($fallbacks[$channel]) && $errorType !== 'authentication';
    }

    /**
     * Get fallback channel for the given channel.
     */
    private function getFallbackChannel(string $channel): string
    {
        $fallbacks = [
            'email' => 'database',
            'sms' => 'database',
            'push' => 'database',
        ];

        return $fallbacks[$channel] ?? 'database';
    }
}
