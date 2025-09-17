<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Exception;

use Exception;

/**
 * Authentication Domain Exception.
 *
 * Exception for authentication-related domain errors.
 */
class AuthenticationException extends Exception
{
    public static function invalidCredentials(): self
    {
        return new self('The provided credentials are incorrect.');
    }

    public static function accountLocked(): self
    {
        return new self('Account has been locked due to too many failed login attempts.');
    }

    public static function accountSuspended(): self
    {
        return new self('Account has been suspended.');
    }

    public static function emailNotVerified(): self
    {
        return new self('Email address has not been verified.');
    }

    public static function tokenExpired(): self
    {
        return new self('Authentication token has expired.');
    }

    public static function tokenInvalid(): self
    {
        return new self('Authentication token is invalid.');
    }
}
