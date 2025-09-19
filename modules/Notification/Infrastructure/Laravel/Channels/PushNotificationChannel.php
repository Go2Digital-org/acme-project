<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Channels;

use Exception;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Push notification channel
 *
 * Sends push notifications via Firebase Cloud Messaging (FCM)
 * and Apple Push Notification Service (APNs)
 */
final readonly class PushNotificationChannel
{
    public function __construct(
        private string $fcmServerKey,
    ) {}

    /**
     * Send the given notification
     *
     * @param object $notifiable
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Get push notification data
        if (! method_exists($notification, 'toPush')) {
            return;
        }

        $data = $notification->toPush($notifiable);

        if (empty($data)) {
            return;
        }

        // Get device tokens for the user
        $deviceTokens = $this->getDeviceTokens($notifiable);

        if ($deviceTokens === []) {
            Log::info('No device tokens found for user', [
                'user_id' => $notifiable->id ?? 'anonymous',
            ]);

            return;
        }

        // Send to each device token
        foreach ($deviceTokens as $token) {
            $this->sendPushNotification($token, $data);
        }
    }

    /**
     * Send push notification to a specific device token
     */
    /**
     * @param  array<string, mixed>  $deviceToken
     * @param  array<string, mixed>  $data
     */
    private function sendPushNotification(array $deviceToken, array $data): void
    {
        $platform = $deviceToken['platform'];
        $token = $deviceToken['token'];

        try {
            match ($platform) {
                'android' => $this->sendFcmNotification($token, $data),
                'ios' => $this->sendApnsNotification($token, $data),
                'web' => $this->sendWebPushNotification($token, $data),
                default => Log::warning("Unsupported push platform: {$platform}"),
            };

            Log::info('Push notification sent successfully', [
                'platform' => $platform,
                'token' => substr((string) $token, 0, 8) . '...',
            ]);

        } catch (Exception $e) {
            Log::error('Push notification failed', [
                'platform' => $platform,
                'token' => substr((string) $token, 0, 8) . '...',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send Firebase Cloud Messaging notification (Android)
     */
    /**
     * @param  array<string, mixed>  $data
     */
    private function sendFcmNotification(string $token, array $data): void
    {
        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $data['title'] ?? 'ACME CSR',
                'body' => $data['body'] ?? '',
                'icon' => $data['icon'] ?? '/icons/icon-192.png',
                'click_action' => $data['action_url'] ?? '/',
                'tag' => $data['tag'] ?? 'acme-notification',
            ],
            'data' => $data['data'] ?? [],
            'android' => [
                'notification' => [
                    'color' => '#FF6B6B',
                    'sound' => 'default',
                    'channel_id' => 'acme_notifications',
                ],
                'priority' => 'high',
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => "key={$this->fcmServerKey}",
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', $payload);

        if (! $response->successful()) {
            throw new Exception('FCM request failed: ' . $response->body());
        }

        $result = $response->json();
        if (($result['failure'] ?? 0) > 0) {
            $error = $result['results'][0]['error'] ?? 'Unknown error';
            throw new Exception("FCM delivery failed: {$error}");
        }
    }

    /**
     * Send Apple Push Notification (iOS)
     */
    /**
     * @param  array<string, mixed>  $data
     */
    private function sendApnsNotification(string $token, array $data): void
    {
        // In a real implementation, you would use a library like
        // pushok/pushok or edamov/pushok for APNs

        $payload = [
            'aps' => [
                'alert' => [
                    'title' => $data['title'] ?? 'ACME CSR',
                    'body' => $data['body'] ?? '',
                ],
                'sound' => 'default',
                'badge' => 1,
                'category' => 'acme_notification',
            ],
            'custom_data' => $data['data'] ?? [],
        ];

        // For demonstration, we'll log the payload
        Log::info('APNs notification payload prepared', [
            'token' => substr($token, 0, 8) . '...',
            'payload' => $payload,
        ]);

        // In production, you would send via APNs HTTP/2 API
        // using JWT authentication with your private key
    }

    /**
     * Send web push notification (browsers)
     */
    /**
     * @param  array<string, mixed>  $data
     */
    private function sendWebPushNotification(string $token, array $data): void
    {
        // Web push notifications using VAPID
        // In a real implementation, you would use a library like
        // minishlink/web-push

        $payload = [
            'title' => $data['title'] ?? 'ACME CSR',
            'body' => $data['body'] ?? '',
            'icon' => $data['icon'] ?? '/icons/icon-192.png',
            'badge' => '/icons/badge-72.png',
            'image' => $data['image'] ?? null,
            'vibrate' => [200, 100, 200],
            'tag' => $data['tag'] ?? 'acme-notification',
            'requireInteraction' => $data['requireInteraction'] ?? false,
            'actions' => $data['actions'] ?? [
                [
                    'action' => 'view',
                    'title' => 'View',
                    'icon' => '/icons/view.png',
                ],
                [
                    'action' => 'dismiss',
                    'title' => 'Dismiss',
                    'icon' => '/icons/dismiss.png',
                ],
            ],
            'data' => array_merge($data['data'] ?? [], [
                'url' => $data['action_url'] ?? '/',
            ]),
        ];

        Log::info('Web push notification prepared', [
            'endpoint' => substr($token, 0, 50) . '...',
            'payload' => $payload,
        ]);

        // In production, you would send using Web Push Protocol
    }

    /**
     * Get device tokens for a user
     *
     * @param object $notifiable
     * @return array<int, array<string, string>>
     */
    private function getDeviceTokens(object $notifiable): array
    {
        // In a real implementation, you would fetch from a device_tokens table
        // For now, return mock data
        if (method_exists($notifiable, 'deviceTokens')) {
            return $notifiable->deviceTokens();
        }

        // Mock device tokens for demonstration
        return [
            [
                'platform' => 'android',
                'token' => 'mock_android_token_' . ($notifiable->id ?? 'anonymous'),
            ],
            [
                'platform' => 'web',
                'token' => 'mock_web_endpoint_' . ($notifiable->id ?? 'anonymous'),
            ],
        ];
    }
}
