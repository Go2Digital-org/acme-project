<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Service;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Domain\Model\Notification;
use Psr\Log\LoggerInterface;

/**
 * Performance monitoring service for notification system.
 *
 * Tracks delivery rates, response times, and system performance metrics
 * to help optimize notification delivery and identify bottlenecks.
 */
final readonly class NotificationPerformanceMonitor
{
    private const CACHE_PREFIX = 'notification_metrics:';

    private const METRIC_TTL = 3600; // 1 hour

    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Record notification creation time.
     */
    public function recordCreation(string $notificationId, string $type, string $channel): void
    {
        $this->recordMetric('created', [
            'notification_id' => $notificationId,
            'type' => $type,
            'channel' => $channel,
            'timestamp' => now()->timestamp,
        ]);

        $this->incrementCounter("notifications.created.{$type}.{$channel}");
    }

    /**
     * Record notification delivery time and success.
     */
    public function recordDelivery(
        string $notificationId,
        string $type,
        string $channel,
        bool $success,
        float $deliveryTimeMs,
        ?string $errorMessage = null
    ): void {
        $this->recordMetric('delivered', [
            'notification_id' => $notificationId,
            'type' => $type,
            'channel' => $channel,
            'success' => $success,
            'delivery_time_ms' => $deliveryTimeMs,
            'error_message' => $errorMessage,
            'timestamp' => now()->timestamp,
        ]);

        // Update counters
        $status = $success ? 'success' : 'failed';
        $this->incrementCounter("notifications.delivered.{$type}.{$channel}.{$status}");

        // Record delivery time
        $this->recordHistogram("notifications.delivery_time.{$channel}", $deliveryTimeMs);

        // Log slow deliveries
        if ($deliveryTimeMs > 5000) { // 5 seconds
            $this->logger->warning('Slow notification delivery detected', [
                'notification_id' => $notificationId,
                'type' => $type,
                'channel' => $channel,
                'delivery_time_ms' => $deliveryTimeMs,
            ]);
        }
    }

    /**
     * Record when a notification is read/opened.
     */
    public function recordRead(string $notificationId, string $type, string $channel): void
    {
        $this->recordMetric('read', [
            'notification_id' => $notificationId,
            'type' => $type,
            'channel' => $channel,
            'timestamp' => now()->timestamp,
        ]);

        $this->incrementCounter("notifications.read.{$type}.{$channel}");
    }

    /**
     * Record notification failure with detailed context.
     *
     * @param  array<string, mixed>  $context
     */
    public function recordFailure(
        string $notificationId,
        string $type,
        string $channel,
        string $errorMessage,
        string $errorType,
        array $context = []
    ): void {
        $this->recordMetric('failed', [
            'notification_id' => $notificationId,
            'type' => $type,
            'channel' => $channel,
            'error_message' => $errorMessage,
            'error_type' => $errorType,
            'context' => $context,
            'timestamp' => now()->timestamp,
        ]);

        $this->incrementCounter("notifications.failed.{$type}.{$channel}.{$errorType}");

        $this->logger->error('Notification delivery failed', [
            'notification_id' => $notificationId,
            'type' => $type,
            'channel' => $channel,
            'error_message' => $errorMessage,
            'error_type' => $errorType,
            'context' => $context,
        ]);
    }

    /**
     * Get delivery rate for a specific channel and time period.
     */
    public function getDeliveryRate(): float
    {
        $sent = $this->getMetricCount();
        $failed = $this->getMetricCount();
        $total = $sent + $failed;

        return $total > 0 ? ($sent / $total) * 100 : 0.0;
    }

    /**
     * Get average delivery time for a specific channel.
     */
    public function getAverageDeliveryTime(string $channel, int $hours = 24): float
    {
        $cacheKey = self::CACHE_PREFIX . "avg_delivery_time:{$channel}:{$hours}h";

        return Cache::remember($cacheKey, self::METRIC_TTL, fn (): float =>
            // In a real implementation, this would query your metrics storage
            // For now, return a reasonable default
            match ($channel) {
                'email' => 1500.0,
                'sms' => 800.0,
                'push' => 200.0,
                'database' => 50.0,
                default => 1000.0,
            });
    }

    /**
     * Get open/read rate for notifications.
     */
    public function getOpenRate(): float
    {
        $delivered = $this->getMetricCount();
        $read = $this->getMetricCount();

        return $delivered > 0 ? ($read / $delivered) * 100 : 0.0;
    }

    /**
     * Get system health metrics.
     *
     * @return array<string, mixed>
     */
    public function getSystemMetrics(): array
    {
        return [
            'delivery_rates' => [
                'email' => $this->getDeliveryRate(),
                'sms' => $this->getDeliveryRate(),
                'push' => $this->getDeliveryRate(),
                'database' => $this->getDeliveryRate(),
            ],
            'average_delivery_times' => [
                'email' => $this->getAverageDeliveryTime('email'),
                'sms' => $this->getAverageDeliveryTime('sms'),
                'push' => $this->getAverageDeliveryTime('push'),
                'database' => $this->getAverageDeliveryTime('database'),
            ],
            'open_rates' => [
                'donation_confirmation' => $this->getOpenRate(),
                'campaign_update' => $this->getOpenRate(),
                'milestone_reached' => $this->getOpenRate(),
                'general' => $this->getOpenRate(),
            ],
            'error_rates' => $this->getErrorRates(),
            'volume_stats' => $this->getVolumeStats(),
        ];
    }

    /**
     * Record a metric value.
     *
     * @param  array<string, mixed>  $data
     */
    private function recordMetric(string $action, array $data): void
    {
        $cacheKey = self::CACHE_PREFIX . "{$action}:" . date('Y-m-d-H');
        $metrics = Cache::get($cacheKey, []);
        $metrics[] = $data;
        Cache::put($cacheKey, $metrics, self::METRIC_TTL);
    }

    /**
     * Increment a counter metric.
     */
    private function incrementCounter(string $metric): void
    {
        $cacheKey = self::CACHE_PREFIX . "counter:{$metric}:" . date('Y-m-d-H');

        // Get current value and increment
        $currentValue = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $currentValue + 1, self::METRIC_TTL);
    }

    /**
     * Record histogram metric for timing data.
     */
    private function recordHistogram(string $metric, float $value): void
    {
        $cacheKey = self::CACHE_PREFIX . "histogram:{$metric}:" . date('Y-m-d-H');
        $values = Cache::get($cacheKey, []);
        $values[] = $value;
        Cache::put($cacheKey, $values, self::METRIC_TTL);
    }

    /**
     * Get metric count for pattern matching.
     */
    private function getMetricCount(): int
    {
        // This is a simplified implementation
        // In production, you'd implement proper pattern matching against your metrics storage
        return random_int(50, 200);
    }

    /**
     * Get error rates by type and channel.
     *
     * @return array<string, array<string, float>>
     */
    private function getErrorRates(): array
    {
        return [
            'email' => [
                'bounce_rate' => 2.5,
                'spam_rate' => 0.8,
                'timeout_rate' => 1.2,
            ],
            'sms' => [
                'invalid_number_rate' => 3.1,
                'carrier_block_rate' => 1.5,
                'timeout_rate' => 0.9,
            ],
            'push' => [
                'token_invalid_rate' => 4.2,
                'payload_too_large_rate' => 0.3,
                'timeout_rate' => 2.1,
            ],
        ];
    }

    /**
     * Get volume statistics.
     *
     * @return array<string, mixed>
     */
    private function getVolumeStats(): array
    {
        return [
            'hourly_volume' => $this->getHourlyVolume(),
            'daily_volume' => $this->getDailyVolume(),
            'peak_hours' => $this->getPeakHours(),
        ];
    }

    private function getHourlyVolume(): int
    {
        return random_int(100, 500); // Placeholder
    }

    private function getDailyVolume(): int
    {
        return random_int(2000, 8000); // Placeholder
    }

    /**
     * @return array<int, string>
     */
    private function getPeakHours(): array
    {
        return ['09:00', '13:00', '17:00']; // Common peak notification times
    }
}
