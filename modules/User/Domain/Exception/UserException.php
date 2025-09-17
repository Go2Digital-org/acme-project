<?php

declare(strict_types=1);

namespace Modules\User\Domain\Exception;

use Exception;

/**
 * User Domain Exception.
 *
 * Base exception for all user-related domain errors.
 */
class UserException extends Exception
{
    public static function userNotFound(int $id): self
    {
        return new self("User with ID {$id} not found");
    }

    public static function userNotFoundByEmail(string $email): self
    {
        return new self("User with email {$email} not found");
    }

    public static function emailAlreadyExists(string $email): self
    {
        return new self("User with email {$email} already exists");
    }

    public static function cannotDeactivateAdmin(): self
    {
        return new self('Cannot deactivate administrator accounts');
    }

    public static function cannotDeleteActiveUser(): self
    {
        return new self('Cannot delete active user account');
    }

    public static function insufficientPermissions(string $action): self
    {
        return new self("Insufficient permissions to perform action: {$action}");
    }

    public static function accountSuspended(): self
    {
        return new self('User account has been suspended');
    }

    public static function accountBlocked(): self
    {
        return new self('User account has been permanently blocked');
    }

    public static function accountNotVerified(): self
    {
        return new self('User account email has not been verified');
    }

    public static function twoFactorNotEnabled(): self
    {
        return new self('Two-factor authentication is not enabled for this user');
    }

    public static function twoFactorAlreadyEnabled(): self
    {
        return new self('Two-factor authentication is already enabled for this user');
    }

    public static function invalidTwoFactorCode(): self
    {
        return new self('Invalid two-factor authentication code');
    }

    public static function passwordTooWeak(): self
    {
        return new self('Password does not meet security requirements');
    }

    public static function cannotPromoteToHigherRole(): self
    {
        return new self('Cannot promote user to a role higher than your own');
    }

    public static function cannotModifyOwnRole(): self
    {
        return new self('Cannot modify your own role');
    }

    public static function invalidStatusTransition(string $from, string $to): self
    {
        return new self("Invalid status transition from {$from} to {$to}");
    }

    public static function profileUpdateFailed(string $reason): self
    {
        return new self("Profile update failed: {$reason}");
    }

    public static function invalidJobTitle(string $jobTitle): self
    {
        return new self("Invalid job title: {$jobTitle}");
    }

    public static function phoneNumberAlreadyExists(string $phoneNumber): self
    {
        return new self("Phone number {$phoneNumber} is already in use");
    }

    public static function maxUsersReached(): self
    {
        return new self('Maximum number of users has been reached');
    }

    public static function bulkOperationFailed(string $operation, int $failed, int $total): self
    {
        return new self("Bulk {$operation} operation failed: {$failed} of {$total} operations failed");
    }
}
