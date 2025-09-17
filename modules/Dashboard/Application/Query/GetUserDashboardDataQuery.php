<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetUserDashboardDataQuery implements QueryInterface
{
    public function __construct(
        public int $userId,
        public bool $useCache = true,
        public int $activityFeedLimit = 10,
        public int $leaderboardLimit = 5
    ) {}
}
