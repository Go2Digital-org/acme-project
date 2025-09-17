<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetUserSessionsQuery implements QueryInterface
{
    public function __construct(
        public int $userId,
    ) {}
}
