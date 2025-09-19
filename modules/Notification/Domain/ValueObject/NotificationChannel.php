<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\ValueObject;

/**
 * Value object representing notification delivery channels.
 *
 * Defines how notifications can be delivered to recipients.
 */
class NotificationChannel
{
    public const DATABASE = 'database';

    public const EMAIL = 'email';

    public const SMS = 'sms';

    public const PUSH = 'push';

    public const SLACK = 'slack';

    public const WEBHOOK = 'webhook';

    public const REALTIME = 'realtime';

    public const BROADCAST = 'broadcast';

    public const IN_APP = 'in_app';

    /**
     * Get all notification channels.
     */
    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::DATABASE,
            self::EMAIL,
            self::SMS,
            self::PUSH,
            self::SLACK,
            self::WEBHOOK,
            self::REALTIME,
            self::BROADCAST,
            self::IN_APP,
        ];
    }

    /**
     * Check if a channel is valid.
     */
    public static function isValid(string $channel): bool
    {
        return in_array($channel, self::all(), true);
    }

    /**
     * Get channels that support real-time delivery.
     */
    /**
     * @return array<int, string>
     */
    public static function realTimeChannels(): array
    {
        return [
            self::REALTIME,
            self::BROADCAST,
            self::IN_APP,
            self::PUSH,
        ];
    }

    /**
     * Get channels that support persistent storage.
     */
    /**
     * @return array<int, string>
     */
    public static function persistentChannels(): array
    {
        return [
            self::DATABASE,
            self::EMAIL,
            self::SMS,
        ];
    }

    /**
     * Get channels that require external services.
     */
    /**
     * @return array<int, string>
     */
    public static function externalChannels(): array
    {
        return [
            self::EMAIL,
            self::SMS,
            self::SLACK,
            self::WEBHOOK,
            self::PUSH,
        ];
    }

    /**
     * Get human-readable label for channel.
     */
    public static function label(string $channel): string
    {
        return match ($channel) {
            self::DATABASE => 'Database',
            self::EMAIL => 'Email',
            self::SMS => 'SMS',
            self::PUSH => 'Push Notification',
            self::SLACK => 'Slack',
            self::WEBHOOK => 'Webhook',
            self::REALTIME => 'Real-time',
            self::BROADCAST => 'Broadcast',
            self::IN_APP => 'In-App',
            default => 'Unknown',
        };
    }

    /**
     * Get icon for channel.
     */
    public static function icon(string $channel): string
    {
        return match ($channel) {
            self::DATABASE => 'heroicon-o-circle-stack',
            self::EMAIL => 'heroicon-o-envelope',
            self::SMS => 'heroicon-o-device-phone-mobile',
            self::PUSH => 'heroicon-o-bell',
            self::SLACK => 'heroicon-o-chat-bubble-left-right',
            self::WEBHOOK => 'heroicon-o-link',
            self::REALTIME => 'heroicon-o-bolt',
            self::BROADCAST => 'heroicon-o-radio',
            self::IN_APP => 'heroicon-o-computer-desktop',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    /**
     * Check if channel supports immediate delivery.
     */
    public static function supportsImmediateDelivery(string $channel): bool
    {
        return in_array($channel, [
            self::REALTIME,
            self::BROADCAST,
            self::IN_APP,
            self::DATABASE,
        ], true);
    }

    /**
     * Check if channel supports scheduled delivery.
     */
    public static function supportsScheduledDelivery(string $channel): bool
    {
        return in_array($channel, [
            self::EMAIL,
            self::SMS,
            self::PUSH,
            self::SLACK,
            self::WEBHOOK,
        ], true);
    }

    /**
     * Get default channel for user role.
     */
    public static function defaultForRole(string $role): string
    {
        return match ($role) {
            'super_admin', 'csr_admin', 'finance_admin' => self::REALTIME,
            'hr_manager' => self::EMAIL,
            'employee' => self::DATABASE,
            default => self::DATABASE,
        };
    }
}
