<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetDashboardCacheStatusQuery implements QueryInterface
{
    public function __construct(
        public int $userId
    ) {}
}
