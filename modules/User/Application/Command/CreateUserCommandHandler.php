<?php

declare(strict_types=1);

namespace Modules\User\Application\Command;

use Modules\User\Domain\Exception\UserException;
use Modules\User\Domain\Factory\UserFactory;
use Modules\User\Domain\Model\User;
use Modules\User\Domain\Repository\UserRepositoryInterface;
use Modules\User\Domain\ValueObject\EmailAddress;
use Modules\User\Domain\ValueObject\UserRole;
use Modules\User\Domain\ValueObject\UserStatus;

/**
 * Create User Command Handler.
 *
 * Handles the creation of new users through the domain layer.
 * Ensures proper validation and business rules are enforced.
 */
final class CreateUserCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserFactory $userFactory,
    ) {}

    /**
     * Handle the creation of a new user.
     *
     * @throws UserException
     */
    public function handle(CreateUserCommand $command): User
    {
        $email = new EmailAddress($command->email);

        // Check if user already exists
        if ($this->userRepository->existsByEmail($email)) {
            throw UserException::emailAlreadyExists($command->email);
        }

        // Create domain user entity
        $user = $this->userFactory->create(
            name: $command->name,
            email: $command->email,
            role: $command->role ? UserRole::from($command->role) : UserRole::EMPLOYEE,
            status: $command->status ? UserStatus::from($command->status) : UserStatus::ACTIVE,
        );

        // Save through repository
        // Note: Password handling is delegated to the infrastructure layer
        return $this->userRepository->save($user);
    }
}
