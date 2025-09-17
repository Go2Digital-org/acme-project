<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Enum;

/**
 * Enum representing notification priority levels.
 *
 * Defines the urgency and importance of notifications for processing and display.
 */
enum NotificationPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    /**
     * Get the string value of the enum case.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get human-readable label for the priority.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::LOW => 'Low Priority',
            self::NORMAL => 'Normal Priority',
            self::MEDIUM => 'Medium Priority',
            self::HIGH => 'High Priority',
            self::URGENT => 'Urgent',
        };
    }

    /**
     * Get color class for UI display.
     */
    public function getColorClass(): string
    {
        return match ($this) {
            self::LOW => 'gray',
            self::NORMAL => 'gray',
            self::MEDIUM => 'blue',
            self::HIGH => 'yellow',
            self::URGENT => 'red',
        };
    }

    /**
     * Get icon for priority level.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::LOW => 'heroicon-o-minus-circle',
            self::NORMAL => 'heroicon-o-information-circle',
            self::MEDIUM => 'heroicon-o-information-circle',
            self::HIGH => 'heroicon-o-exclamation-circle',
            self::URGENT => 'heroicon-o-exclamation-triangle',
        };
    }

    /**
     * Get numeric weight for priority (higher = more important).
     */
    public function getWeight(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::NORMAL => 2,
            self::MEDIUM => 3,
            self::HIGH => 4,
            self::URGENT => 5,
        };
    }

    /**
     * Check if priority requires immediate processing.
     */
    public function requiresImmediateProcessing(): bool
    {
        return $this === self::URGENT;
    }

    /**
     * Check if priority can be batched or delayed.
     */
    public function canBeBatched(): bool
    {
        return match ($this) {
            self::LOW,
            self::NORMAL,
            self::MEDIUM => true,
            default => false,
        };
    }

    /**
     * Check if priority should be persistent in UI.
     */
    public function shouldBePersistent(): bool
    {
        return $this === self::URGENT;
    }

    /**
     * Get notification sound based on priority.
     */
    public function getSound(): ?string
    {
        return match ($this) {
            self::URGENT => 'urgent-notification',
            self::HIGH => 'high-priority',
            default => null,
        };
    }

    /**
     * Get retry attempts for failed notifications based on priority.
     */
    public function getMaxRetryAttempts(): int
    {
        return match ($this) {
            self::URGENT => 3,
            self::HIGH => 2,
            self::MEDIUM => 2,
            self::NORMAL => 1,
            self::LOW => 1,
        };
    }

    /**
     * Get delay between retry attempts in minutes.
     */
    public function getRetryDelayMinutes(): int
    {
        return match ($this) {
            self::URGENT => 5,
            self::HIGH => 15,
            self::MEDIUM => 20,
            self::NORMAL => 30,
            self::LOW => 60,
        };
    }

    /**
     * Get priority from weight.
     */
    public static function fromWeight(int $weight): self
    {
        return match ($weight) {
            1 => self::LOW,
            2 => self::NORMAL,
            3 => self::MEDIUM,
            4 => self::HIGH,
            5 => self::URGENT,
            default => self::NORMAL,
        };
    }

    /**
     * Get priorities that require immediate attention.
     *
     * @return array<NotificationPriority>
     */
    public static function urgentPriorities(): array
    {
        return [self::URGENT];
    }

    /**
     * Get priorities that can be batched or delayed.
     *
     * @return array<NotificationPriority>
     */
    public static function batchablePriorities(): array
    {
        return array_filter(
            self::cases(),
            fn (self $case): bool => $case->canBeBatched()
        );
    }

    /**
     * Get all priorities ordered by weight (lowest to highest).
     *
     * @return array<NotificationPriority>
     */
    public static function orderedByWeight(): array
    {
        $priorities = self::cases();
        usort($priorities, fn (self $a, self $b): int => $a->getWeight() <=> $b->getWeight());

        return $priorities;
    }
}
