<?php

declare(strict_types=1);

namespace Modules\User\Application\Query;

use InvalidArgumentException;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;
use Modules\User\Domain\Exception\UserException;
use Modules\User\Domain\Model\User;
use Modules\User\Domain\Repository\UserRepositoryInterface;

/**
 * Find User by ID Query Handler.
 */
final readonly class FindUserByIdQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function handle(QueryInterface $query): User
    {
        if (! $query instanceof FindUserByIdQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        $user = $this->userRepository->findById($query->userId);

        if (! $user instanceof User) {
            throw UserException::userNotFound($query->userId);
        }

        return $user;
    }
}
