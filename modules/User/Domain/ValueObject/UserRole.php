<?php

declare(strict_types=1);

namespace Modules\User\Domain\ValueObject;

/**
 * User Role Value Object.
 *
 * Represents the role/permission level of a user in the system.
 */
enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case EMPLOYEE = 'employee';
    case GUEST = 'guest';

    public function getLabel(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Administrator',
            self::ADMIN => 'Administrator',
            self::MANAGER => 'Manager',
            self::EMPLOYEE => 'Employee',
            self::GUEST => 'Guest',
        };
    }

    /** @return array<array-key, mixed> */
    public function getPermissions(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => [
                'manage_users',
                'manage_campaigns',
                'manage_donations',
                'view_analytics',
                'manage_system',
                'manage_settings',
                'view_audit_logs',
                'manage_organizations',
            ],
            self::ADMIN => [
                'manage_users',
                'manage_campaigns',
                'manage_donations',
                'view_analytics',
                'manage_organizations',
            ],
            self::MANAGER => [
                'manage_campaigns',
                'view_analytics',
                'manage_team_donations',
            ],
            self::EMPLOYEE => [
                'create_campaigns',
                'make_donations',
                'view_own_data',
            ],
            self::GUEST => [
                'view_public_campaigns',
            ],
        };
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions(), true);
    }

    public function canManageUsers(): bool
    {
        return $this->hasPermission('manage_users');
    }

    public function canManageCampaigns(): bool
    {
        return $this->hasPermission('manage_campaigns');
    }

    public function canCreateCampaigns(): bool
    {
        if ($this->hasPermission('create_campaigns')) {
            return true;
        }

        return $this->hasPermission('manage_campaigns');
    }

    public function canMakeDonations(): bool
    {
        if ($this->hasPermission('make_donations')) {
            return true;
        }

        return $this->hasPermission('manage_donations');
    }

    public function canViewAnalytics(): bool
    {
        return $this->hasPermission('view_analytics');
    }

    public function isAdmin(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::ADMIN], true);
    }

    public function isManager(): bool
    {
        return $this === self::MANAGER;
    }

    public function isEmployee(): bool
    {
        return $this === self::EMPLOYEE;
    }

    public function getPriority(): int
    {
        return match ($this) {
            self::SUPER_ADMIN => 100,
            self::ADMIN => 80,
            self::MANAGER => 60,
            self::EMPLOYEE => 40,
            self::GUEST => 20,
        };
    }

    public function isHigherThan(self $other): bool
    {
        return $this->getPriority() > $other->getPriority();
    }

    public function isLowerThan(self $other): bool
    {
        return $this->getPriority() < $other->getPriority();
    }

    /** @return array<array-key, mixed> */
    public static function getSelectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role): array => [$role->value => $role->getLabel()])
            ->toArray();
    }
}
