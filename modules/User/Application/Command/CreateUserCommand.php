<?php

declare(strict_types=1);

namespace Modules\User\Application\Command;

/**
 * Create User Command.
 *
 * Command for creating a new user with validation.
 * Follows CQRS pattern for write operations.
 */
final readonly class CreateUserCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $role = null,
        public ?string $status = null,
    ) {}
}
