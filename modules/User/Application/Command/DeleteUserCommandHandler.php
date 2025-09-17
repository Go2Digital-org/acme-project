<?php

declare(strict_types=1);

namespace Modules\User\Application\Command;

use Modules\User\Domain\Model\User;
use Modules\User\Domain\Repository\UserRepositoryInterface;

class DeleteUserCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {}

    public function handle(DeleteUserCommand $command): bool
    {
        $user = $this->repository->findById($command->id);

        if (! $user instanceof User) {
            return false;
        }

        return $this->repository->deleteById($command->id);
    }
}
