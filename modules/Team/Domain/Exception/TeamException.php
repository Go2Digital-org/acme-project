<?php

declare(strict_types=1);

namespace Modules\Team\Domain\Exception;

use Exception;

/**
 * Team domain exceptions
 */
class TeamException extends Exception
{
    public static function userAlreadyMember(int $teamId, int $userId): self
    {
        return new self("User {$userId} is already a member of team {$teamId}");
    }

    public static function userNotMember(int $teamId, int $userId): self
    {
        return new self("User {$userId} is not a member of team {$teamId}");
    }

    public static function userNotInOrganization(int $userId, int $organizationId): self
    {
        return new self("User {$userId} is not a member of organization {$organizationId}");
    }

    public static function cannotRemoveOwner(int $teamId): self
    {
        return new self("Cannot remove owner from team {$teamId}. Transfer ownership first.");
    }

    public static function cannotChangeOwnerRole(int $teamId): self
    {
        return new self("Cannot change owner role for team {$teamId}. Transfer ownership first.");
    }

    public static function insufficientPermissions(int $userId, string $permission): self
    {
        return new self("User {$userId} lacks permission: {$permission}");
    }

    public static function cannotAssignRole(int $userId, string $role): self
    {
        return new self("User {$userId} cannot assign role: {$role}");
    }

    public static function cannotRemoveHigherRole(int $removerId, int $targetId): self
    {
        return new self("User {$removerId} cannot remove user {$targetId} with equal or higher role");
    }

    public static function cannotChangeRoleOfHigherMember(int $changerId, int $targetId): self
    {
        return new self("User {$changerId} cannot change role of user {$targetId} with equal or higher role");
    }

    public static function onlyOwnerCanTransferOwnership(int $teamId, int $userId): self
    {
        return new self("Only the owner of team {$teamId} can transfer ownership. User {$userId} is not the owner.");
    }

    public static function ownerCannotLeaveTeam(int $teamId): self
    {
        return new self("Owner cannot leave team {$teamId} without transferring ownership first");
    }

    public static function teamNotFound(int $teamId): self
    {
        return new self("Team {$teamId} not found");
    }

    public static function invalidName(string $message): self
    {
        return new self("Invalid team name: {$message}");
    }

    public static function invalidSlug(string $message): self
    {
        return new self("Invalid team slug: {$message}");
    }

    public static function slugAlreadyExists(string $slug, int $organizationId): self
    {
        return new self("Team slug '{$slug}' already exists in organization {$organizationId}");
    }

    public static function teamInactive(int $teamId): self
    {
        return new self("Team {$teamId} is inactive and cannot perform this operation");
    }
}
