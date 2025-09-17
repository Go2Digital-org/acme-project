<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Enum;

/**
 * Enum representing notification delivery channels.
 *
 * Defines how notifications can be delivered to recipients.
 */
enum NotificationChannel: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case IN_APP = 'in_app';
    case PUSH = 'push';
    case WEBHOOK = 'webhook';
    case DATABASE = 'database';
    case SLACK = 'slack';
    case REALTIME = 'realtime';
    case BROADCAST = 'broadcast';

    /**
     * Get the string value of the enum case.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get human-readable label for the channel.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::EMAIL => 'Email',
            self::SMS => 'SMS',
            self::IN_APP => 'In-App',
            self::PUSH => 'Push Notification',
            self::WEBHOOK => 'Webhook',
            self::DATABASE => 'Database',
            self::SLACK => 'Slack',
            self::REALTIME => 'Real-time',
            self::BROADCAST => 'Broadcast',
        };
    }

    /**
     * Get icon for the channel.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::EMAIL => 'heroicon-o-envelope',
            self::SMS => 'heroicon-o-device-phone-mobile',
            self::IN_APP => 'heroicon-o-computer-desktop',
            self::PUSH => 'heroicon-o-bell',
            self::WEBHOOK => 'heroicon-o-link',
            self::DATABASE => 'heroicon-o-circle-stack',
            self::SLACK => 'heroicon-o-chat-bubble-left-right',
            self::REALTIME => 'heroicon-o-bolt',
            self::BROADCAST => 'heroicon-o-radio',
        };
    }

    /**
     * Get all values as strings.
     *
     * @return array<string>
     */
    public static function getAllValues(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Check if channel supports real-time delivery.
     */
    public function supportsRealTime(): bool
    {
        return match ($this) {
            self::REALTIME,
            self::BROADCAST,
            self::IN_APP,
            self::PUSH => true,
            default => false,
        };
    }

    /**
     * Check if channel supports persistent storage.
     */
    public function isPersistent(): bool
    {
        return match ($this) {
            self::DATABASE,
            self::EMAIL,
            self::SMS => true,
            default => false,
        };
    }

    /**
     * Check if channel requires external services.
     */
    public function requiresExternalService(): bool
    {
        return match ($this) {
            self::EMAIL,
            self::SMS,
            self::SLACK,
            self::WEBHOOK,
            self::PUSH => true,
            default => false,
        };
    }

    /**
     * Check if channel supports immediate delivery.
     */
    public function supportsImmediateDelivery(): bool
    {
        return match ($this) {
            self::REALTIME,
            self::BROADCAST,
            self::IN_APP,
            self::DATABASE => true,
            default => false,
        };
    }

    /**
     * Check if channel supports scheduled delivery.
     */
    public function supportsScheduledDelivery(): bool
    {
        return match ($this) {
            self::EMAIL,
            self::SMS,
            self::PUSH,
            self::SLACK,
            self::WEBHOOK => true,
            default => false,
        };
    }

    /**
     * Get color class for UI display.
     */
    public function getColorClass(): string
    {
        return match ($this) {
            self::EMAIL => 'blue',
            self::SMS => 'green',
            self::IN_APP => 'purple',
            self::PUSH => 'orange',
            self::WEBHOOK => 'gray',
            self::DATABASE => 'slate',
            self::SLACK => 'indigo',
            self::REALTIME => 'red',
            self::BROADCAST => 'yellow',
        };
    }

    /**
     * Get default channel for user role.
     */
    public static function getDefaultForRole(string $role): self
    {
        return match ($role) {
            'super_admin', 'csr_admin', 'finance_admin' => self::REALTIME,
            'hr_manager' => self::EMAIL,
            'employee' => self::DATABASE,
            default => self::DATABASE,
        };
    }

    /**
     * Get channels that support real-time delivery.
     *
     * @return array<NotificationChannel>
     */
    public static function realTimeChannels(): array
    {
        return array_filter(
            self::cases(),
            fn (self $case): bool => $case->supportsRealTime()
        );
    }

    /**
     * Get channels that support persistent storage.
     *
     * @return array<NotificationChannel>
     */
    public static function persistentChannels(): array
    {
        return array_filter(
            self::cases(),
            fn (self $case): bool => $case->isPersistent()
        );
    }

    /**
     * Get channels that require external services.
     *
     * @return array<NotificationChannel>
     */
    public static function externalChannels(): array
    {
        return array_filter(
            self::cases(),
            fn (self $case): bool => $case->requiresExternalService()
        );
    }
}
