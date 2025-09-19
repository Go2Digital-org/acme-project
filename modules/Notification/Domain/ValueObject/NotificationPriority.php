<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\ValueObject;

/**
 * Value object representing notification priority levels.
 *
 * Defines the urgency and importance of notifications for processing and display.
 */
class NotificationPriority
{
    public const LOW = 'low';

    public const NORMAL = 'normal';

    public const MEDIUM = 'medium';

    public const HIGH = 'high';

    public const URGENT = 'urgent';

    public const CRITICAL = 'critical';

    /**
     * Get all notification priorities.
     */
    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::LOW,
            self::NORMAL,
            self::MEDIUM,
            self::HIGH,
            self::URGENT,
            self::CRITICAL,
        ];
    }

    /**
     * Check if a priority is valid.
     */
    public static function isValid(string $priority): bool
    {
        return in_array($priority, self::all(), true);
    }

    /**
     * Get priorities that require immediate attention.
     */
    /**
     * @return array<int, string>
     */
    public static function urgentPriorities(): array
    {
        return [self::URGENT, self::CRITICAL];
    }

    /**
     * Get priorities that can be batched or delayed.
     */
    /**
     * @return array<int, string>
     */
    public static function batchablePriorities(): array
    {
        return [self::LOW, self::NORMAL, self::MEDIUM];
    }

    /**
     * Get human-readable label for priority.
     */
    public static function label(string $priority): string
    {
        return match ($priority) {
            self::LOW => 'Low Priority',
            self::NORMAL => 'Normal Priority',
            self::MEDIUM => 'Medium Priority',
            self::HIGH => 'High Priority',
            self::URGENT => 'Urgent',
            self::CRITICAL => 'Critical',
            default => 'Unknown',
        };
    }

    /**
     * Get color class for UI display.
     */
    public static function colorClass(string $priority): string
    {
        return match ($priority) {
            self::LOW => 'gray',
            self::NORMAL => 'blue',
            self::MEDIUM => 'green',
            self::HIGH => 'yellow',
            self::URGENT => 'orange',
            self::CRITICAL => 'red',
            default => 'gray',
        };
    }

    /**
     * Get icon for priority level.
     */
    public static function icon(string $priority): string
    {
        return match ($priority) {
            self::LOW => 'heroicon-o-minus-circle',
            self::NORMAL => 'heroicon-o-bell',
            self::MEDIUM => 'heroicon-o-information-circle',
            self::HIGH => 'heroicon-o-exclamation-circle',
            self::URGENT => 'heroicon-o-exclamation-triangle',
            self::CRITICAL => 'heroicon-o-shield-exclamation',
            default => 'heroicon-o-bell',
        };
    }

    /**
     * Get numeric weight for priority (higher = more important).
     */
    public static function weight(string $priority): int
    {
        return match ($priority) {
            self::LOW => 1,
            self::NORMAL => 2,
            self::MEDIUM => 3,
            self::HIGH => 4,
            self::URGENT => 5,
            self::CRITICAL => 6,
            default => 2,
        };
    }

    /**
     * Get priority from weight.
     */
    public static function fromWeight(int $weight): string
    {
        return match ($weight) {
            1 => self::LOW,
            2 => self::NORMAL,
            3 => self::MEDIUM,
            4 => self::HIGH,
            5 => self::URGENT,
            6 => self::CRITICAL,
            default => self::NORMAL,
        };
    }

    /**
     * Check if priority requires immediate processing.
     */
    public static function requiresImmediateProcessing(string $priority): bool
    {
        return in_array($priority, self::urgentPriorities(), true);
    }

    /**
     * Check if priority should be persistent in UI.
     */
    public static function shouldBePersistent(string $priority): bool
    {
        return $priority === self::CRITICAL;
    }

    /**
     * Get notification sound based on priority.
     */
    public static function sound(string $priority): ?string
    {
        return match ($priority) {
            self::CRITICAL => 'critical-alert',
            self::URGENT => 'urgent-notification',
            self::HIGH => 'high-priority',
            default => null,
        };
    }

    /**
     * Get retry attempts for failed notifications based on priority.
     */
    public static function maxRetryAttempts(string $priority): int
    {
        return match ($priority) {
            self::CRITICAL => 5,
            self::URGENT => 3,
            self::HIGH => 2,
            self::MEDIUM => 2,
            self::NORMAL => 1,
            self::LOW => 1,
            default => 1,
        };
    }

    /**
     * Get delay between retry attempts in minutes.
     */
    public static function retryDelayMinutes(string $priority): int
    {
        return match ($priority) {
            self::CRITICAL => 1,
            self::URGENT => 5,
            self::HIGH => 15,
            self::MEDIUM => 20,
            self::NORMAL => 30,
            self::LOW => 60,
            default => 30,
        };
    }
}
