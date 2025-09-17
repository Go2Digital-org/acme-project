<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Service;

use Modules\Auth\Application\Command\DeleteAccountCommand;
use Modules\Auth\Application\Command\DeleteAccountCommandHandler;
use Modules\Auth\Application\Command\EnableTwoFactorCommand;
use Modules\Auth\Application\Command\EnableTwoFactorCommandHandler;
use Modules\Auth\Application\Command\UpdatePasswordCommand;
use Modules\Auth\Application\Command\UpdatePasswordCommandHandler;
use Modules\Auth\Application\Command\UpdateProfileCommand;
use Modules\Auth\Application\Command\UpdateProfileCommandHandler;
use Modules\Auth\Application\Query\GetUserProfileQuery;
use Modules\Auth\Application\Query\GetUserProfileQueryHandler;

final readonly class ProfileManagementService
{
    public function __construct(
        private UpdateProfileCommandHandler $updateProfileHandler,
        private UpdatePasswordCommandHandler $updatePasswordHandler,
        private EnableTwoFactorCommandHandler $enableTwoFactorHandler,
        private DeleteAccountCommandHandler $deleteAccountHandler,
        private GetUserProfileQueryHandler $getUserProfileHandler,
    ) {}

    /** @return array<array-key, mixed> */
    public function getUserProfile(int $userId): array
    {
        return $this->getUserProfileHandler->handle(
            new GetUserProfileQuery($userId),
        );
    }

    public function updateProfile(int $userId, string $name, string $email, ?string $profilePhoto = null): void
    {
        $this->updateProfileHandler->handle(
            new UpdateProfileCommand($userId, $name, $email, $profilePhoto),
        );
    }

    public function updatePassword(int $userId, string $currentPassword, string $newPassword): void
    {
        $this->updatePasswordHandler->handle(
            new UpdatePasswordCommand($userId, $currentPassword, $newPassword),
        );
    }

    public function enableTwoFactor(int $userId, bool $enable = true): void
    {
        $this->enableTwoFactorHandler->handle(
            new EnableTwoFactorCommand($userId, $enable),
        );
    }

    public function deleteAccount(int $userId, string $password): void
    {
        $this->deleteAccountHandler->handle(
            new DeleteAccountCommand($userId, $password),
        );
    }
}
