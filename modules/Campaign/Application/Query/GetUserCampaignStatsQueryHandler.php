<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Query;

use Modules\Campaign\Domain\Service\UserCampaignManagementService;
use Modules\Campaign\Domain\ValueObject\UserCampaignStats;

class GetUserCampaignStatsQueryHandler
{
    public function __construct(
        private readonly UserCampaignManagementService $managementService,
    ) {}

    public function handle(GetUserCampaignStatsQuery $query): UserCampaignStats
    {
        return $this->managementService->getUserCampaignStats($query->userId);
    }
}
