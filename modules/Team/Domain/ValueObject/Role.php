<?php

declare(strict_types=1);

namespace Modules\Team\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Team member role value object
 */
enum Role: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case MEMBER = 'member';
    case VIEWER = 'viewer';

    public static function fromString(string $role): self
    {
        return match (strtolower($role)) {
            'owner' => self::OWNER,
            'admin' => self::ADMIN,
            'manager' => self::MANAGER,
            'member' => self::MEMBER,
            'viewer' => self::VIEWER,
            default => throw new InvalidArgumentException("Invalid role: {$role}"),
        };
    }

    /**
     * @return array<int, string>
     */
    public function getPermissions(): array
    {
        return match ($this) {
            self::OWNER => [
                'teams.delete',
                'teams.manage_members',
                'teams.manage_campaigns',
                'teams.view_analytics',
                'teams.manage_settings',
            ],
            self::ADMIN => [
                'teams.manage_members',
                'teams.manage_campaigns',
                'teams.view_analytics',
                'teams.manage_settings',
            ],
            self::MANAGER => [
                'teams.manage_campaigns',
                'teams.view_analytics',
                'teams.view_members',
            ],
            self::MEMBER => [
                'teams.create_campaigns',
                'teams.view_campaigns',
                'teams.view_members',
            ],
            self::VIEWER => [
                'teams.view_campaigns',
                'teams.view_members',
            ],
        };
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions(), true);
    }

    public function canManageMembers(): bool
    {
        return $this->hasPermission('teams.manage_members');
    }

    public function canManageCampaigns(): bool
    {
        return $this->hasPermission('teams.manage_campaigns');
    }

    public function canViewAnalytics(): bool
    {
        return $this->hasPermission('teams.view_analytics');
    }

    public function getHierarchyLevel(): int
    {
        return match ($this) {
            self::OWNER => 5,
            self::ADMIN => 4,
            self::MANAGER => 3,
            self::MEMBER => 2,
            self::VIEWER => 1,
        };
    }

    public function isHigherThan(Role $other): bool
    {
        return $this->getHierarchyLevel() > $other->getHierarchyLevel();
    }

    public function isEqualOrHigherThan(Role $other): bool
    {
        return $this->getHierarchyLevel() >= $other->getHierarchyLevel();
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::OWNER => 'Team Owner',
            self::ADMIN => 'Administrator',
            self::MANAGER => 'Manager',
            self::MEMBER => 'Member',
            self::VIEWER => 'Viewer',
        };
    }
}
