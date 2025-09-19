<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Service;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Notification analytics service
 *
 * Tracks delivery metrics, open rates, click rates, and performance analytics
 */
final class NotificationAnalyticsService
{
    /**
     * Track notification delivery attempt
     */
    public function trackDeliveryAttempt(Notification $notification, User $user): void
    {
        $this->incrementMetric('delivery_attempts_total');
        $this->incrementMetric('delivery_attempts_' . $this->getNotificationType($notification));

        Log::info('Notification delivery attempted', [
            'user_id' => $user->id,
            'notification_type' => $notification::class,
        ]);
    }

    /**
     * Track successful notification delivery
     */
    public function trackDeliverySuccess(Notification $notification, User $user): void
    {
        $this->incrementMetric('delivery_success_total');
        $this->incrementMetric('delivery_success_' . $this->getNotificationType($notification));

        Log::info('Notification delivered successfully', [
            'user_id' => $user->id,
            'notification_type' => $notification::class,
        ]);
    }

    /**
     * Track failed notification delivery
     */
    public function trackDeliveryFailure(Notification $notification, User $user, string $reason): void
    {
        $this->incrementMetric('delivery_failures_total');
        $this->incrementMetric('delivery_failures_' . $this->getNotificationType($notification));

        Log::error('Notification delivery failed', [
            'user_id' => $user->id,
            'notification_type' => $notification::class,
            'reason' => $reason,
        ]);
    }

    /**
     * Track email delivery
     */
    public function trackEmailDelivery(string $email, Notification $notification): void
    {
        $this->incrementMetric('email_deliveries_total');
        $this->incrementMetric('email_deliveries_' . $this->getNotificationType($notification));

        Log::info('Email notification delivered', [
            'email' => $email,
            'notification_type' => $notification::class,
        ]);
    }

    /**
     * Track SMS delivery
     */
    public function trackSmsDelivery(string $phoneNumber, string $message): void
    {
        $this->incrementMetric('sms_deliveries_total');

        Log::info('SMS notification delivered', [
            'phone' => $phoneNumber,
            'message_length' => strlen($message),
        ]);
    }

    /**
     * Track push notification delivery
     */
    /**
     * @param  array<string, mixed>  $payload
     */
    public function trackPushDelivery(string $deviceToken, array $payload): void
    {
        $this->incrementMetric('push_deliveries_total');

        Log::info('Push notification delivered', [
            'device_token' => substr($deviceToken, 0, 8) . '...',
            'payload_size' => count($payload),
        ]);
    }

    /**
     * Track notification open (when user views notification)
     */
    public function trackNotificationOpen(string $notificationId, User $user): void
    {
        $this->incrementMetric('notification_opens_total');

        Log::info('Notification opened', [
            'notification_id' => $notificationId,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Track notification click (when user clicks on notification action)
     */
    public function trackNotificationClick(string $notificationId, User $user, ?string $action = null): void
    {
        $this->incrementMetric('notification_clicks_total');

        if ($action) {
            $this->incrementMetric("notification_clicks_{$action}");
        }

        Log::info('Notification clicked', [
            'notification_id' => $notificationId,
            'user_id' => $user->id,
            'action' => $action,
        ]);
    }

    /**
     * Get delivery metrics
     */
    /**
     * @return array<string, mixed>
     */
    public function getDeliveryMetrics(): array
    {
        return [
            'delivery_rates' => [
                'email' => $this->getDeliveryRate('email'),
                'sms' => $this->getDeliveryRate('sms'),
                'push' => $this->getDeliveryRate('push'),
            ],
            'open_rates' => [
                'total' => $this->getOpenRate(),
            ],
            'click_rates' => [
                'total' => $this->getClickRate(),
            ],
            'volume_metrics' => [
                'total_sent' => $this->getMetric('delivery_success_total'),
                'total_failed' => $this->getMetric('delivery_failures_total'),
                'email_sent' => $this->getMetric('email_deliveries_total'),
                'sms_sent' => $this->getMetric('sms_deliveries_total'),
                'push_sent' => $this->getMetric('push_deliveries_total'),
            ],
            'performance_metrics' => [
                'avg_delivery_time' => $this->getAverageDeliveryTime(),
                'success_rate' => $this->getSuccessRate(),
            ],
        ];
    }

    /**
     * Get delivery rate for a specific channel
     */
    public function getDeliveryRate(string $channel): float
    {
        $attempted = $this->getMetric("{$channel}_deliveries_attempted") ?: 1;
        $successful = $this->getMetric("{$channel}_deliveries_total") ?: 0;

        return ($successful / $attempted) * 100;
    }

    /**
     * Get overall open rate
     */
    public function getOpenRate(): float
    {
        $sent = $this->getMetric('delivery_success_total') ?: 1;
        $opened = $this->getMetric('notification_opens_total') ?: 0;

        return ($opened / $sent) * 100;
    }

    /**
     * Get overall click rate
     */
    public function getClickRate(): float
    {
        $opened = $this->getMetric('notification_opens_total') ?: 1;
        $clicked = $this->getMetric('notification_clicks_total') ?: 0;

        return ($clicked / $opened) * 100;
    }

    /**
     * Get overall success rate
     */
    public function getSuccessRate(): float
    {
        $attempted = $this->getMetric('delivery_attempts_total') ?: 1;
        $successful = $this->getMetric('delivery_success_total') ?: 0;

        return ($successful / $attempted) * 100;
    }

    /**
     * Get average delivery time (placeholder)
     */
    public function getAverageDeliveryTime(): float
    {
        // In a real implementation, you would track actual delivery times
        return 1.2; // seconds
    }

    /**
     * Get best send times based on analytics
     */
    /**
     * @return array<string, mixed>
     */
    public function getBestSendTimes(): array
    {
        // In a real implementation, you would analyze historical data
        return [
            'email' => ['09:00', '13:00', '17:00'],
            'push' => ['12:00', '18:00', '20:00'],
            'sms' => ['10:00', '14:00', '16:00'],
        ];
    }

    /**
     * Increment a metric counter
     */
    private function incrementMetric(string $key): void
    {
        $cacheKey = "notification_metrics:{$key}";
        Cache::increment($cacheKey, 1);

        // Also increment daily counter
        $dailyKey = 'notification_metrics_daily:' . now()->format('Y-m-d') . ":{$key}";
        Cache::increment($dailyKey, 1);
    }

    /**
     * Get a metric value
     */
    private function getMetric(string $key): int
    {
        $cacheKey = "notification_metrics:{$key}";

        return (int) Cache::get($cacheKey, 0);
    }

    /**
     * Get notification type from notification class
     */
    private function getNotificationType(Notification $notification): string
    {
        $class = $notification::class;
        $parts = explode('\\', $class);

        return strtolower(str_replace('Notification', '', end($parts)));
    }

    /**
     * Reset metrics (for testing or maintenance)
     */
    public function resetMetrics(): void
    {
        $keys = [
            'delivery_attempts_total',
            'delivery_success_total',
            'delivery_failures_total',
            'email_deliveries_total',
            'sms_deliveries_total',
            'push_deliveries_total',
            'notification_opens_total',
            'notification_clicks_total',
        ];

        foreach ($keys as $key) {
            Cache::forget("notification_metrics:{$key}");
        }

        Log::info('Notification metrics reset');
    }
}
