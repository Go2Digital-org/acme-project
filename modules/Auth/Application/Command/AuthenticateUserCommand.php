<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Command;

/**
 * Authenticate User Command.
 *
 * Command for authenticating a user with email and password.
 * Follows CQRS pattern for authentication operations.
 */
final readonly class AuthenticateUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
