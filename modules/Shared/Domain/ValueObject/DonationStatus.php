<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\ValueObject;

enum DonationStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'secondary',
            self::REFUNDED => 'primary',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PENDING => 'clock',
            self::PROCESSING => 'sync',
            self::COMPLETED => 'check-circle',
            self::FAILED => 'x-circle',
            self::CANCELLED => 'x',
            self::REFUNDED => 'arrow-left-circle',
        };
    }

    public function getTailwindBadgeClasses(): string
    {
        return match ($this) {
            self::PENDING => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
            self::PROCESSING => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
            self::COMPLETED => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
            self::FAILED => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
            self::CANCELLED => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
            self::REFUNDED => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
        };
    }

    public function getTailwindDotClasses(): string
    {
        return match ($this) {
            self::PENDING => 'bg-gray-500',
            self::PROCESSING => 'bg-yellow-500',
            self::COMPLETED => 'bg-green-500',
            self::FAILED => 'bg-red-500',
            self::CANCELLED => 'bg-gray-500',
            self::REFUNDED => 'bg-yellow-500',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING => 'Donation is waiting to be processed by the payment system',
            self::PROCESSING => 'Donation is currently being processed by the payment gateway',
            self::COMPLETED => 'Donation has been successfully completed and processed',
            self::FAILED => 'Donation processing failed due to payment issues',
            self::CANCELLED => 'Donation was cancelled by the donor or system',
            self::REFUNDED => 'Donation has been refunded back to the donor',
        };
    }

    public function canBeProcessed(): bool
    {
        return $this === self::PENDING;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::PROCESSING], true);
    }

    public function canBeRefunded(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFailed(): bool
    {
        return in_array($this, [self::FAILED, self::CANCELLED], true);
    }

    public function getProgressPercentage(): int
    {
        return match ($this) {
            self::PENDING => 10,
            self::PROCESSING => 50,
            self::COMPLETED => 100,
            self::FAILED, self::CANCELLED, self::REFUNDED => 0,
        };
    }

    public function showsProgress(): bool
    {
        return ! in_array($this, [self::FAILED, self::CANCELLED, self::REFUNDED], true);
    }

    /**
     * @param  self[]  $statuses
     */
    public function isOneOf(array $statuses): bool
    {
        return in_array($this, $statuses, true);
    }

    /**
     * @return self[]
     */
    public static function getActiveStatuses(): array
    {
        return [self::PENDING, self::PROCESSING];
    }

    /**
     * @return self[]
     */
    public static function getFinalStatuses(): array
    {
        return [self::COMPLETED, self::FAILED, self::CANCELLED, self::REFUNDED];
    }

    public function canChangeWithinTime(int $minutes): bool
    {
        return match ($this) {
            self::PENDING => $minutes <= 60, // Can change within 1 hour
            self::PROCESSING => $minutes <= 10, // Can change within 10 minutes
            default => false,
        };
    }

    public function requiresUserAction(): bool
    {
        return in_array($this, [self::PENDING, self::FAILED], true);
    }

    public function affectsCampaignTotal(): bool
    {
        return $this === self::COMPLETED;
    }

    public static function fromString(string $status): self
    {
        return self::from(strtolower(trim($status)));
    }

    public static function tryFromString(?string $status): ?self
    {
        if ($status === null || trim($status) === '') {
            return null;
        }

        return self::tryFrom(strtolower(trim($status)));
    }

    public function getTransitionErrorMessage(self $targetStatus): string
    {
        return "Cannot transition from {$this->getLabel()} to {$targetStatus->getLabel()} status";
    }

    public function validateTransition(self $targetStatus): bool
    {
        return $this->canTransitionTo($targetStatus);
    }

    /** @return array<array-key, mixed> */
    public static function getFailedStatuses(): array
    {
        return [self::FAILED, self::CANCELLED];
    }

    /** @return array<array-key, mixed> */
    public static function getSuccessfulStatuses(): array
    {
        return [self::COMPLETED];
    }

    /** @return array<array-key, mixed> */
    public static function getPendingStatuses(): array
    {
        return [self::PENDING, self::PROCESSING];
    }

    /**
     * Check if status is in a final state (no further processing possible).
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
            self::REFUNDED,
        ], true);
    }

    /**
     * Check if status allows for timeline tracking in UI.
     */
    public function hasTimeline(): bool
    {
        return ! in_array($this, [self::FAILED, self::CANCELLED], true);
    }

    /**
     * Get valid transition statuses from current status.
     */
    /** @return array<array-key, mixed> */
    public function getValidTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::PROCESSING, self::CANCELLED, self::FAILED],
            self::PROCESSING => [self::COMPLETED, self::FAILED, self::CANCELLED],
            self::COMPLETED => [self::REFUNDED],
            self::FAILED => [], // No valid transitions from failed
            self::CANCELLED => [], // No valid transitions from cancelled
            self::REFUNDED => [], // No valid transitions from refunded
        };
    }

    /**
     * Check if transition to another status is valid.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->getValidTransitions(), true);
    }

    /**
     * Get priority for sorting (higher number = higher priority).
     */
    public function getSortPriority(): int
    {
        return match ($this) {
            self::PROCESSING => 6,
            self::PENDING => 5,
            self::COMPLETED => 4,
            self::REFUNDED => 3,
            self::FAILED => 2,
            self::CANCELLED => 1,
        };
    }
}
