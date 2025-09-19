<?php

declare(strict_types=1);

namespace Modules\Team\Domain\Service;

use Illuminate\Database\Eloquent\Collection;
use Modules\Team\Domain\Exception\TeamException;
use Modules\Team\Domain\Model\Team;
use Modules\Team\Domain\Model\TeamMember;
use Modules\Team\Domain\ValueObject\Role;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Team membership management domain service
 */
class TeamMembershipService
{
    /**
     * Add user to team with specified role
     */
    public function addMember(Team $team, User $user, Role $role, User $addedBy): TeamMember
    {
        // Validate permissions
        if (! $this->canManageMembers($team, $addedBy)) {
            throw TeamException::insufficientPermissions($addedBy->id, 'manage_members');
        }

        // Validate role assignment permissions
        if (! $this->canAssignRole($team, $addedBy, $role)) {
            throw TeamException::cannotAssignRole($addedBy->id, $role->value);
        }

        return $team->addMember($user, $role);
    }

    /**
     * Remove user from team
     */
    public function removeMember(Team $team, User $user, User $removedBy): void
    {
        // Validate permissions
        if (! $this->canManageMembers($team, $removedBy)) {
            throw TeamException::insufficientPermissions($removedBy->id, 'manage_members');
        }

        // Cannot remove someone with equal or higher role (except self)
        $userRole = $team->getMemberRole($user->id);
        $removerRole = $team->getMemberRole($removedBy->id);

        if ($user->id !== $removedBy->id && $userRole && $removerRole && ! $removerRole->isHigherThan($userRole)) {
            throw TeamException::cannotRemoveHigherRole($removedBy->id, $user->id);
        }

        $team->removeMember($user->id);
    }

    /**
     * Change member role
     */
    public function changeMemberRole(Team $team, User $user, Role $newRole, User $changedBy): void
    {
        // Validate permissions
        if (! $this->canManageMembers($team, $changedBy)) {
            throw TeamException::insufficientPermissions($changedBy->id, 'manage_members');
        }

        // Validate role assignment permissions
        if (! $this->canAssignRole($team, $changedBy, $newRole)) {
            throw TeamException::cannotAssignRole($changedBy->id, $newRole->value);
        }

        // Cannot change role of someone with equal or higher role
        $currentRole = $team->getMemberRole($user->id);
        $changerRole = $team->getMemberRole($changedBy->id);

        if ($currentRole && $changerRole && ! $changerRole->isHigherThan($currentRole)) {
            throw TeamException::cannotChangeRoleOfHigherMember($changedBy->id, $user->id);
        }

        $team->changeMemberRole($user->id, $newRole);
    }

    /**
     * Transfer team ownership
     */
    public function transferOwnership(Team $team, User $newOwner, User $currentOwner): void
    {
        // Only current owner can transfer ownership
        if (! $team->isOwner($currentOwner->id)) {
            throw TeamException::onlyOwnerCanTransferOwnership($team->id, $currentOwner->id);
        }

        $team->transferOwnership($newOwner->id);
    }

    /**
     * Handle member leaving team voluntarily
     */
    public function leaveTeam(Team $team, User $user): void
    {
        if (! $team->hasMember($user->id)) {
            throw TeamException::userNotMember($team->id, $user->id);
        }

        // Owner cannot leave without transferring ownership
        if ($team->isOwner($user->id)) {
            throw TeamException::ownerCannotLeaveTeam($team->id);
        }

        $member = $team->getMember($user->id);
        if ($member instanceof TeamMember) {
            $member->leave();
        }
    }

    /**
     * Check if user can manage team members
     */
    public function canManageMembers(Team $team, User $user): bool
    {
        return $team->canUserManageMembers($user->id);
    }

    /**
     * Check if user can assign specific role
     */
    public function canAssignRole(Team $team, User $user, Role $roleToAssign): bool
    {
        $userRole = $team->getMemberRole($user->id);

        if (! $userRole instanceof Role) {
            return false;
        }

        // Can only assign roles lower than or equal to your own
        return $userRole->isEqualOrHigherThan($roleToAssign);
    }

    /**
     * Get member statistics for team
     */
    /**
     * @return array<string, mixed>
     */
    public function getMemberStatistics(Team $team): array
    {
        $members = $team->members;

        $roleDistribution = [];
        foreach (Role::cases() as $role) {
            $roleDistribution[$role->value] = $members->where('role', $role)->count();
        }

        return [
            'total_members' => $members->count(),
            'active_members' => $members->where('left_at', null)->count(),
            'role_distribution' => $roleDistribution,
            'average_tenure_days' => $this->calculateAverageTenure($members->where('left_at', null)),
        ];
    }

    /**
     * Calculate average tenure for active members
     *
     * @param  Collection<int, TeamMember>  $members
     */
    private function calculateAverageTenure(Collection $members): float
    {
        if ($members->isEmpty()) {
            return 0;
        }

        $totalDays = $members->sum(fn (TeamMember $member): int => $member->getDaysActive());

        return round($totalDays / $members->count(), 1);
    }
}
