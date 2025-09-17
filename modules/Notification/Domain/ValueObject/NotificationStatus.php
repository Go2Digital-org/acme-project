<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\ValueObject;

/**
 * Value object representing notification status.
 *
 * Encapsulates the various states a notification can be in during its lifecycle.
 */
class NotificationStatus
{
    public const PENDING = 'pending';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';

    public const DELIVERED = 'delivered';

    /**
     * All valid notification statuses.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::PENDING,
            self::SENT,
            self::FAILED,
            self::CANCELLED,
            self::DELIVERED,
        ];
    }

    /**
     * Check if a status is valid.
     */
    public static function isValid(string $status): bool
    {
        return in_array($status, self::all(), true);
    }

    /**
     * Get statuses that represent successful delivery.
     *
     * @return array<int, string>
     */
    public static function successStates(): array
    {
        return [self::SENT, self::DELIVERED];
    }

    /**
     * Get statuses that represent failure or problems.
     *
     * @return array<int, string>
     */
    public static function failureStates(): array
    {
        return [self::FAILED, self::CANCELLED];
    }

    /**
     * Get human-readable label for status.
     */
    public static function label(string $status): string
    {
        return match ($status) {
            self::PENDING => 'Pending',
            self::SENT => 'Sent',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
            self::DELIVERED => 'Delivered',
            default => 'Unknown',
        };
    }

    /**
     * Get color class for UI display.
     */
    public static function colorClass(string $status): string
    {
        return match ($status) {
            self::PENDING => 'warning',
            self::SENT, self::DELIVERED => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Check if status represents a final state.
     */
    public static function isFinalState(string $status): bool
    {
        return in_array($status, [
            self::SENT,
            self::DELIVERED,
            self::FAILED,
            self::CANCELLED,
        ], true);
    }
}
