<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Query;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;

class ListUserCampaignsQueryHandler
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Campaign>
     */
    public function handle(ListUserCampaignsQuery $query): LengthAwarePaginator
    {
        return $this->campaignRepository->paginateUserCampaigns(
            userId: $query->userId,
            page: $query->page,
            perPage: $query->perPage,
            filters: $query->filters,
            sortBy: $query->sortBy,
            sortOrder: $query->sortOrder,
        );
    }
}
