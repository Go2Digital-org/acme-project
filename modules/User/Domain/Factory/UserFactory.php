<?php

declare(strict_types=1);

namespace Modules\User\Domain\Factory;

use DateTimeImmutable;
use Modules\User\Domain\Model\User;
use Modules\User\Domain\ValueObject\EmailAddress;
use Modules\User\Domain\ValueObject\UserRole;
use Modules\User\Domain\ValueObject\UserStatus;

/**
 * User Factory.
 *
 * Domain factory for creating User entities with proper validation.
 * Encapsulates user creation logic and ensures business rules.
 */
final class UserFactory
{
    /**
     * Create a new User entity.
     */
    public function create(
        string $name,
        string $email,
        UserRole $role = UserRole::EMPLOYEE,
        UserStatus $status = UserStatus::ACTIVE,
    ): User {
        // Split name into first and last name
        $nameParts = explode(' ', trim($name), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        return new User(
            id: 0, // Temporary ID, will be set by repository
            firstName: $firstName,
            lastName: $lastName,
            email: new EmailAddress($email),
            status: $status,
            role: $role,
            createdAt: new DateTimeImmutable,
            emailVerifiedAt: null,
        );
    }

    /**
     * Create user from array data.
     *
     * @param  array<string, mixed>  $data
     */
    public function createFromArray(array $data): User
    {
        return $this->create(
            name: $data['name'],
            email: $data['email'],
            role: isset($data['role']) ? UserRole::from($data['role']) : UserRole::EMPLOYEE,
            status: isset($data['status']) ? UserStatus::from($data['status']) : UserStatus::ACTIVE,
        );
    }
}
