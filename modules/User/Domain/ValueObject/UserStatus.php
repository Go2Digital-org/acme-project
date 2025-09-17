<?php

declare(strict_types=1);

namespace Modules\User\Domain\ValueObject;

/**
 * User Status Value Object.
 *
 * Represents the current status/state of a user account.
 */
enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case PENDING_VERIFICATION = 'pending_verification';
    case BLOCKED = 'blocked';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::PENDING_VERIFICATION => 'Pending Verification',
            self::BLOCKED => 'Blocked',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ACTIVE => 'User account is active and can access all features.',
            self::INACTIVE => 'User account is temporarily inactive.',
            self::SUSPENDED => 'User account has been suspended due to policy violations.',
            self::PENDING_VERIFICATION => 'User account is awaiting email verification.',
            self::BLOCKED => 'User account has been permanently blocked.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'yellow',
            self::SUSPENDED => 'orange',
            self::PENDING_VERIFICATION => 'blue',
            self::BLOCKED => 'red',
        };
    }

    public function canLogin(): bool
    {
        return in_array($this, [self::ACTIVE, self::PENDING_VERIFICATION], true);
    }

    public function canCreateCampaigns(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canMakeDonations(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canAccessPlatform(): bool
    {
        return ! in_array($this, [self::BLOCKED, self::SUSPENDED], true);
    }

    public function requiresAction(): bool
    {
        return in_array($this, [self::PENDING_VERIFICATION, self::SUSPENDED, self::BLOCKED], true);
    }

    public function isTemporary(): bool
    {
        return in_array($this, [self::INACTIVE, self::SUSPENDED, self::PENDING_VERIFICATION], true);
    }

    public function isPermanent(): bool
    {
        return in_array($this, [self::ACTIVE, self::BLOCKED], true);
    }

    /** @return array<array-key, mixed> */
    public static function getSelectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->getLabel()])
            ->toArray();
    }

    /** @return array<array-key, mixed> */
    public static function getActiveStatuses(): array
    {
        return [self::ACTIVE];
    }

    /** @return array<array-key, mixed> */
    public static function getInactiveStatuses(): array
    {
        return [self::INACTIVE, self::SUSPENDED, self::BLOCKED];
    }

    /** @return array<array-key, mixed> */
    public static function getPendingStatuses(): array
    {
        return [self::PENDING_VERIFICATION];
    }
}
