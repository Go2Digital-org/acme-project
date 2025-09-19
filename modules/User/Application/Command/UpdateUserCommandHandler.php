<?php

declare(strict_types=1);

namespace Modules\User\Application\Command;

use Modules\User\Domain\Model\User;
use Modules\User\Domain\Repository\UserRepositoryInterface;

class UpdateUserCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {}

    public function handle(UpdateUserCommand $command): ?User
    {
        $user = $this->repository->findById($command->id);

        if (! $user instanceof User) {
            return null;
        }

        /** @var array<string, mixed> $updateData */
        $updateData = [];

        if ($command->name !== null) {
            $updateData['name'] = $command->name;
        }

        if ($command->email !== null) {
            $updateData['email'] = $command->email;
        }

        if ($command->jobTitle !== null) {
            $updateData['job_title'] = $command->jobTitle;
        }

        if ($command->phone !== null) {
            $updateData['phone'] = $command->phone;
        }

        if ($command->address !== null) {
            $updateData['address'] = $command->address;
        }

        if ($command->preferredLanguage !== null) {
            $updateData['preferred_language'] = $command->preferredLanguage;
        }

        if ($command->timezone !== null) {
            $updateData['timezone'] = $command->timezone;
        }

        if ($updateData !== []) {
            $this->repository->updateById($command->id, $updateData);
        }

        return $this->repository->findById($command->id);
    }
}
