<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\ValueObject;

enum CampaignStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case REJECTED = 'rejected';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canAcceptDonations(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isFinal(): bool
    {
        return match ($this) {
            self::COMPLETED, self::CANCELLED, self::EXPIRED => true,
            default => false,
        };
    }

    public function requiresApproval(): bool
    {
        return $this === self::PENDING_APPROVAL;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    public function canTransitionTo(self $targetStatus): bool
    {
        // Self-transitions are not allowed
        if ($this === $targetStatus) {
            return false;
        }

        // Final statuses cannot transition to any other status
        if ($this->isFinal()) {
            return false;
        }

        return match ($this) {
            self::DRAFT => in_array($targetStatus, [self::PENDING_APPROVAL, self::CANCELLED], true),
            self::PENDING_APPROVAL => in_array($targetStatus, [self::ACTIVE, self::REJECTED], true),
            self::REJECTED => in_array($targetStatus, [self::DRAFT, self::PENDING_APPROVAL, self::CANCELLED], true),
            self::ACTIVE => in_array($targetStatus, [self::PAUSED, self::COMPLETED, self::CANCELLED, self::EXPIRED], true),
            self::PAUSED => in_array($targetStatus, [self::ACTIVE, self::CANCELLED, self::EXPIRED], true),
            default => false,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING_APPROVAL => 'Pending Approval',
            self::REJECTED => 'Rejected',
            self::ACTIVE => 'Active',
            self::PAUSED => 'Paused',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::EXPIRED => 'Expired',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::PENDING_APPROVAL => 'info',
            self::REJECTED => 'danger',
            self::ACTIVE => 'success',
            self::PAUSED => 'warning',
            self::COMPLETED => 'primary',
            self::CANCELLED => 'danger',
            self::EXPIRED => 'warning',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::DRAFT => 'Campaign is not yet published and is not visible to donors',
            self::PENDING_APPROVAL => 'Campaign is awaiting approval from administrators',
            self::REJECTED => 'Campaign was rejected and needs revisions before resubmission',
            self::ACTIVE => 'Campaign is live and accepting donations from supporters',
            self::PAUSED => 'Campaign is temporarily paused and not accepting donations',
            self::COMPLETED => 'Campaign has successfully reached its goal',
            self::CANCELLED => 'Campaign has been cancelled by the organizer',
            self::EXPIRED => 'Campaign has expired and is no longer accepting donations',
        };
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
        return [self::ACTIVE];
    }

    /**
     * @return self[]
     */
    public static function getFinalStatuses(): array
    {
        return [self::COMPLETED, self::CANCELLED, self::EXPIRED];
    }

    /**
     * @return self[]
     */
    public static function getDonationAcceptingStatuses(): array
    {
        return [self::ACTIVE];
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

    /**
     * @return self[]
     */
    public function getValidTransitions(): array
    {
        if ($this->isFinal()) {
            return [];
        }

        return match ($this) {
            self::DRAFT => [self::PENDING_APPROVAL, self::CANCELLED],
            self::PENDING_APPROVAL => [self::ACTIVE, self::REJECTED],
            self::REJECTED => [self::DRAFT, self::PENDING_APPROVAL, self::CANCELLED],
            self::ACTIVE => [self::PAUSED, self::COMPLETED, self::CANCELLED, self::EXPIRED],
            self::PAUSED => [self::ACTIVE, self::CANCELLED, self::EXPIRED],
            default => [],
        };
    }

    public function getTransitionErrorMessage(self $targetStatus): string
    {
        return "Cannot transition from {$this->getLabel()} to {$targetStatus->getLabel()} status";
    }
}
