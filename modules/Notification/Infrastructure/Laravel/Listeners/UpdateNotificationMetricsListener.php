<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Notification\Domain\Event\NotificationReadEvent;
use Modules\Notification\Infrastructure\Laravel\Service\NotificationPerformanceMonitor;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener that updates notification metrics when notifications are read.
 *
 * This listener tracks read rates, engagement metrics, and user interaction
 * patterns to help optimize notification strategy and content.
 */
final class UpdateNotificationMetricsListener implements ShouldQueue
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
        private readonly NotificationPerformanceMonitor $performanceMonitor,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the notification read event.
     */
    public function handle(NotificationReadEvent $event): void
    {
        try {
            // Record the read event in performance metrics
            $this->performanceMonitor->recordRead(
                notificationId: (string) $event->notification->id,
                type: $event->notification->type,
                channel: $event->notification->channel ?? 'unknown'
            );

            // Update engagement metrics
            $this->updateEngagementMetrics($event);

            // Track user behavior patterns
            $this->trackUserBehavior($event);

            // Log the read event for audit trail
            $this->logger->info('Notification read metrics updated', [
                'notification_id' => (string) $event->notification->id,
                'notifiable_id' => $event->notification->notifiable_id,
                'type' => $event->notification->type,
                'channel' => $event->notification->channel,
                'read_by' => $event->readBy,
                'read_at' => $event->readAt->format('c'),
                'read_context' => $event->readContext,
            ]);

        } catch (Throwable $exception) {
            $this->logger->error('Failed to update notification read metrics', [
                'notification_id' => (string) $event->notification->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(NotificationReadEvent $event, Throwable $exception): void
    {
        $this->logger->error('Failed to process notification read event', [
            'notification_id' => (string) $event->notification->id,
            'notifiable_id' => $event->notification->notifiable_id ?? 'unknown',
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Update engagement metrics based on the notification read event.
     */
    private function updateEngagementMetrics(NotificationReadEvent $event): void
    {
        try {
            $notification = $event->notification;

            // Calculate time from creation to read
            $createdAt = $notification->created_at;
            $readAt = $event->readAt;
            $timeToRead = $readAt->getTimestamp() - $createdAt->getTimestamp();

            // Track engagement patterns
            $this->recordEngagementPattern([
                'notification_type' => $notification->type,
                'channel' => $notification->channel,
                'priority' => $notification->priority,
                'time_to_read_seconds' => $timeToRead,
                'read_context' => $event->readContext,
                'day_of_week' => $readAt->format('w'),
                'hour_of_day' => (int) $readAt->format('H'),
            ]);

            // Update type-specific metrics
            $this->updateTypeSpecificMetrics($notification->type, $timeToRead, $event->readContext);

        } catch (Throwable $exception) {
            $this->logger->warning('Failed to update engagement metrics', [
                'notification_id' => (string) $event->notification->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Track user behavior patterns for notification optimization.
     */
    private function trackUserBehavior(NotificationReadEvent $event): void
    {
        try {
            $patterns = [
                'user_id' => $event->notification->notifiable_id,
                'notification_type' => $event->notification->type,
                'channel_preference' => $event->notification->channel,
                'read_time' => $event->readAt->format('H:i'),
                'read_day' => $event->readAt->format('w'),
                'device_context' => $event->readContext['device'] ?? 'unknown',
                'location_context' => $event->readContext['location'] ?? 'unknown',
            ];

            // Store behavioral data for machine learning and personalization
            $this->storeBehavioralData($patterns);

        } catch (Throwable $exception) {
            $this->logger->debug('Failed to track user behavior', [
                'notification_id' => (string) $event->notification->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Record engagement pattern for analysis.
     *
     * @param  array<string, mixed>  $pattern
     */
    private function recordEngagementPattern(array $pattern): void
    {
        // In a real implementation, this would store data in a metrics system
        // like InfluxDB, CloudWatch, or a similar time-series database

        $this->logger->debug('Engagement pattern recorded', $pattern);

        // Example of how you might use Laravel's built-in cache for simple metrics
        $cacheKey = 'engagement:' . $pattern['notification_type'] . ':' . date('Y-m-d-H');
        $existingData = cache()->get($cacheKey, []);
        $existingData[] = $pattern;
        cache()->put($cacheKey, $existingData, now()->addHours(24));
    }

    /**
     * Update metrics specific to notification type.
     *
     * @param  array<string, mixed>  $context
     */
    private function updateTypeSpecificMetrics(string $type, int $timeToRead, array $context): void
    {
        match ($type) {
            'donation_confirmation' => $this->updateDonationConfirmationMetrics($timeToRead, $context),
            'campaign_update' => $this->updateCampaignUpdateMetrics($timeToRead, $context),
            'milestone_reached' => $this->updateMilestoneMetrics($timeToRead, $context),
            'system_alert' => $this->updateSystemAlertMetrics($timeToRead, $context),
            default => $this->updateGeneralMetrics($timeToRead, $context),
        };
    }

    /**
     * Store behavioral data for analysis and personalization.
     *
     * @param  array<string, mixed>  $patterns
     */
    private function storeBehavioralData(array $patterns): void
    {
        // This would typically integrate with a data pipeline or analytics service
        // For now, we'll log it for debugging and future implementation

        $this->logger->info('User behavior pattern', [
            'patterns' => $patterns,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function updateDonationConfirmationMetrics(int $timeToRead, array $context): void
    {
        // Track donation confirmation engagement
        $this->logger->debug('Donation confirmation read', [
            'time_to_read' => $timeToRead,
            'context' => $context,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function updateCampaignUpdateMetrics(int $timeToRead, array $context): void
    {
        // Track campaign update engagement
        $this->logger->debug('Campaign update read', [
            'time_to_read' => $timeToRead,
            'context' => $context,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function updateMilestoneMetrics(int $timeToRead, array $context): void
    {
        // Track milestone notification engagement
        $this->logger->debug('Milestone notification read', [
            'time_to_read' => $timeToRead,
            'context' => $context,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function updateSystemAlertMetrics(int $timeToRead, array $context): void
    {
        // Track system alert engagement
        $this->logger->debug('System alert read', [
            'time_to_read' => $timeToRead,
            'context' => $context,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function updateGeneralMetrics(int $timeToRead, array $context): void
    {
        // Track general notification engagement
        $this->logger->debug('General notification read', [
            'time_to_read' => $timeToRead,
            'context' => $context,
        ]);
    }
}
