<?php

declare(strict_types=1);

namespace Modules\Team\Domain\Specification;

use Modules\Team\Domain\Model\Team;
use Modules\Team\Domain\ValueObject\Role;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Team membership business rules specification
 */
class TeamMembershipSpecification
{
    /**
     * Check if user can join team
     */
    public function canJoinTeam(User $user, Team $team): bool
    {
        // User must belong to same organization
        if ($user->organization_id !== $team->organization_id) {
            return false;
        }

        // User cannot already be a member
        if ($team->hasMember($user->id)) {
            return false;
        }

        // Team must be active
        return (bool) $team->is_active;
    }

    /**
     * Check if user can be assigned specific role
     */
    public function canAssignRole(User $assigner, Team $team, Role $roleToAssign): bool
    {
        $assignerRole = $team->getMemberRole($assigner->id);

        if (! $assignerRole instanceof Role) {
            return false;
        }

        // Can only assign roles of equal or lower hierarchy
        return $assignerRole->isEqualOrHigherThan($roleToAssign);
    }

    /**
     * Check if user can manage another user in the team
     */
    public function canManageUser(User $manager, User $target, Team $team): bool
    {
        $managerRole = $team->getMemberRole($manager->id);
        $targetRole = $team->getMemberRole($target->id);

        if (! $managerRole || ! $targetRole) {
            return false;
        }

        // Users can manage themselves
        if ($manager->id === $target->id) {
            return true;
        }

        // Can only manage users with lower hierarchy
        return $managerRole->isHigherThan($targetRole);
    }

    /**
     * Check if role change is permitted
     */
    public function canChangeRole(User $changer, User $target, Team $team, Role $newRole): bool
    {
        // Must be able to manage the target user
        if (! $this->canManageUser($changer, $target, $team)) {
            return false;
        }

        // Must be able to assign the new role
        return $this->canAssignRole($changer, $team, $newRole);
    }

    /**
     * Check if team deletion is allowed
     */
    public function canDeleteTeam(User $user, Team $team): bool
    {
        // Only owner can delete team
        return $team->isOwner($user->id);
    }

    /**
     * Check if ownership transfer is valid
     */
    public function canTransferOwnership(User $currentOwner, User $newOwner, Team $team): bool
    {
        // Current user must be owner
        if (! $team->isOwner($currentOwner->id)) {
            return false;
        }

        // New owner must be team member
        if (! $team->hasMember($newOwner->id)) {
            return false;
        }

        // Cannot transfer to self
        return $currentOwner->id !== $newOwner->id;
    }

    /**
     * Check team size constraints
     */
    public function isWithinSizeLimit(Team $team, int $maxMembers = 100): bool
    {
        return $team->getMemberCount() <= $maxMembers;
    }

    /**
     * Check if team has minimum required members
     */
    public function hasMinimumMembers(Team $team, int $minMembers = 1): bool
    {
        return $team->getActiveMemberCount() >= $minMembers;
    }
}
