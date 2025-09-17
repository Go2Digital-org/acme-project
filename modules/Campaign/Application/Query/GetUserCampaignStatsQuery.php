<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Query;

final readonly class GetUserCampaignStatsQuery
{
    public function __construct(
        public int $userId,
    ) {}
}
