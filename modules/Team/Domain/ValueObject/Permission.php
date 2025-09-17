<?php

declare(strict_types=1);

namespace Modules\Team\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

/**
 * Team permission value object
 */
class Permission implements Stringable
{
    private const VALID_PERMISSIONS = [
        'teams.delete',
        'teams.manage_members',
        'teams.manage_campaigns',
        'teams.view_analytics',
        'teams.manage_settings',
        'teams.create_campaigns',
        'teams.view_campaigns',
        'teams.view_members',
        'teams.invite_members',
        'teams.remove_members',
        'teams.edit_team',
    ];

    public function __construct(
        public readonly string $value
    ) {
        if (! in_array($value, self::VALID_PERMISSIONS, true)) {
            throw new InvalidArgumentException("Invalid permission: {$value}");
        }
    }

    public static function fromString(string $permission): self
    {
        return new self($permission);
    }

    /**
     * @return array<int, string>
     */
    public static function getAllPermissions(): array
    {
        return self::VALID_PERMISSIONS;
    }

    public function getCategory(): string
    {
        $parts = explode('.', $this->value);

        return $parts[1] ?? 'general';
    }

    public function getDisplayName(): string
    {
        return match ($this->value) {
            'teams.delete' => 'Delete Team',
            'teams.manage_members' => 'Manage Team Members',
            'teams.manage_campaigns' => 'Manage All Campaigns',
            'teams.view_analytics' => 'View Team Analytics',
            'teams.manage_settings' => 'Manage Team Settings',
            'teams.create_campaigns' => 'Create Campaigns',
            'teams.view_campaigns' => 'View Campaigns',
            'teams.view_members' => 'View Team Members',
            'teams.invite_members' => 'Invite New Members',
            'teams.remove_members' => 'Remove Members',
            'teams.edit_team' => 'Edit Team Information',
            default => ucwords(str_replace(['teams.', '_'], ['', ' '], $this->value)),
        };
    }

    public function getDescription(): string
    {
        return match ($this->value) {
            'teams.delete' => 'Permanently delete the team and all its data',
            'teams.manage_members' => 'Add, remove, and change roles of team members',
            'teams.manage_campaigns' => 'Create, edit, delete, and approve team campaigns',
            'teams.view_analytics' => 'View team performance analytics and reports',
            'teams.manage_settings' => 'Change team settings, branding, and configuration',
            'teams.create_campaigns' => 'Create new fundraising campaigns for the team',
            'teams.view_campaigns' => 'View team campaigns and their details',
            'teams.view_members' => 'View list of team members and their roles',
            'teams.invite_members' => 'Send invitations to new team members',
            'teams.remove_members' => 'Remove members from the team',
            'teams.edit_team' => 'Edit team name, description, and basic information',
            default => 'Team permission',
        };
    }

    public function equals(Permission $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
