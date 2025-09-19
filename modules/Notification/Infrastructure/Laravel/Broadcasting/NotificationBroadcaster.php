<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Domain\Event\NotificationCreatedEvent;
use Modules\Notification\Domain\Event\NotificationSentEvent;
use Modules\Notification\Domain\Model\Notification;
use Throwable;

/**
 * Custom broadcaster for notification events.
 *
 * This broadcaster handles real-time notification broadcasting with enhanced
 * features like user presence, typing indicators, and notification queueing.
 */
final readonly class NotificationBroadcaster
{
    public function __construct(
        private PusherBroadcaster $pusher,
    ) {}

    /**
     * Broadcast notification creation in real-time.
     */
    public function broadcastNotificationCreated(NotificationCreatedEvent $event): void
    {
        try {
            $channels = $this->getChannelsForNotification($event->notification);

            foreach ($channels as $channel) {
                $payload = $this->formatNotificationPayload($event->notification, 'created');

                $this->pusher->broadcast(
                    [$channel],
                    'notification.created',
                    $payload,
                );
            }

            Log::debug('Notification created event broadcasted', [
                'notification_id' => $event->notification->id,
                'channels' => $channels,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to broadcast notification created event', [
                'notification_id' => $event->notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast notification sent in real-time.
     */
    public function broadcastNotificationSent(NotificationSentEvent $event): void
    {
        try {
            $channels = $this->getChannelsForNotification($event->notification);

            foreach ($channels as $channel) {
                $payload = $this->formatNotificationPayload($event->notification, 'sent');
                $payload['delivery_channel'] = $event->deliveryChannel;
                $payload['delivery_metadata'] = $event->deliveryMetadata;

                $this->pusher->broadcast(
                    [$channel],
                    'notification.sent',
                    $payload,
                );
            }

            // Send desktop notification if supported
            if ($event->notification->supportsDesktopNotification()) {
                $this->sendDesktopNotification($event->notification);
            }

            Log::debug('Notification sent event broadcasted', [
                'notification_id' => $event->notification->id,
                'channels' => $channels,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to broadcast notification sent event', [
                'notification_id' => $event->notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast notification read status update.
     */
    public function broadcastNotificationRead(Notification $notification): void
    {
        try {
            $channel = "user.{$notification->notifiable_id}.notifications";

            $payload = [
                'id' => $notification->id,
                'read_at' => $notification->read_at?->toISOString(),
                'status' => 'read',
            ];

            $this->pusher->broadcast(
                [$channel],
                'notification.read',
                $payload,
            );

            Log::debug('Notification read event broadcasted', [
                'notification_id' => $notification->id,
                'channel' => $channel,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to broadcast notification read event', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast unread notification count update.
     */
    public function broadcastUnreadCount(int $userId, int $unreadCount): void
    {
        try {
            $channel = "user.{$userId}.notifications";

            $payload = [
                'user_id' => $userId,
                'unread_count' => $unreadCount,
                'timestamp' => now()->toISOString(),
            ];

            $this->pusher->broadcast(
                [$channel],
                'notifications.unread_count',
                $payload,
            );

            Log::debug('Unread count broadcasted', [
                'user_id' => $userId,
                'unread_count' => $unreadCount,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to broadcast unread count', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast typing indicator for notification replies.
     */
    public function broadcastTypingIndicator(int $userId, int $notificationId, bool $typing): void
    {
        try {
            $channel = "notification.{$notificationId}.activity";

            $payload = [
                'user_id' => $userId,
                'notification_id' => $notificationId,
                'typing' => $typing,
                'timestamp' => now()->toISOString(),
            ];

            $this->pusher->broadcast(
                [$channel],
                'notification.typing',
                $payload,
            );
        } catch (Throwable $e) {
            Log::error('Failed to broadcast typing indicator', [
                'user_id' => $userId,
                'notification_id' => $notificationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get appropriate broadcast channels for a notification.
     *
     * @return array<int, string>
     */
    private function getChannelsForNotification(Notification $notification): array
    {
        $channels = [];

        // User's personal notification channel
        $channels[] = "user.{$notification->notifiable_id}.notifications";

        // Admin channels for high-priority notifications
        if ($notification->isHighPriority()) {
            $channels[] = 'admin.notifications';
        }

        // Organization-specific channels
        if ($notification->hasOrganizationContext()) {
            $organizationId = $notification->getOrganizationId();
            $channels[] = "organization.{$organizationId}.notifications";
        }

        // Campaign-specific channels
        if ($notification->hasCampaignContext()) {
            $campaignId = $notification->getCampaignId();
            $channels[] = "campaign.{$campaignId}.notifications";
        }

        return array_unique($channels);
    }

    /**
     * Format notification data for broadcasting.
     */
    /**
     * @return array<string, mixed>
     */
    private function formatNotificationPayload(Notification $notification, string $event): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'priority' => $notification->priority,
            'channel' => $notification->channel,
            'actions' => $notification->actions,
            'metadata' => $notification->metadata,
            'notifiable_id' => $notification->notifiable_id,
            'created_at' => $notification->created_at->toISOString(),
            'scheduled_for' => $notification->scheduled_for?->toISOString(),
            'expires_at' => $notification->expires_at?->toISOString(),
            'read_at' => $notification->read_at?->toISOString(),
            'event' => $event,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Send desktop notification for browsers that support it.
     */
    private function sendDesktopNotification(Notification $notification): void
    {
        try {
            $channel = "user.{$notification->notifiable_id}.desktop";

            $payload = [
                'title' => $notification->title,
                'body' => $notification->message,
                'icon' => asset('icons/notification.png'),
                'badge' => asset('icons/badge.png'),
                'tag' => "notification-{$notification->id}",
                'requireInteraction' => $notification->isHighPriority(),
                'actions' => array_map(fn (array $action): array => [
                    'action' => $action['action'],
                    'title' => $action['label'],
                    'icon' => null, // Icon is optional
                ], $notification->actions ?? []),
                'data' => [
                    'notification_id' => $notification->id,
                    'url' => $notification->getActionUrl(),
                ],
            ];

            $this->pusher->broadcast(
                [$channel],
                'desktop.notification',
                $payload,
            );
        } catch (Throwable $e) {
            Log::error('Failed to send desktop notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
