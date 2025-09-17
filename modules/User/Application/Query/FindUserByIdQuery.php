<?php

declare(strict_types=1);

namespace Modules\User\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

/**
 * Find User by ID Query.
 */
final readonly class FindUserByIdQuery implements QueryInterface
{
    public function __construct(
        public int $userId,
    ) {}
}
