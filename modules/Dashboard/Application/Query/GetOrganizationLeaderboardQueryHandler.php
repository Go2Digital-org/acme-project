<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\Query;

use Modules\Dashboard\Application\ReadModel\OrganizationLeaderboardReadModel;
use Modules\Dashboard\Domain\Repository\DashboardRepositoryInterface;

final readonly class GetOrganizationLeaderboardQueryHandler
{
    public function __construct(
        private DashboardRepositoryInterface $repository
    ) {}

    public function handle(GetOrganizationLeaderboardQuery $query): OrganizationLeaderboardReadModel
    {
        $leaderboard = $this->repository->getTopDonatorsLeaderboard(
            $query->organizationId,
            $query->limit
        );

        return new OrganizationLeaderboardReadModel(
            id: $query->organizationId,
            data: [
                'entries' => $leaderboard,
                'limit' => $query->limit,
                'total_entries' => count($leaderboard),
            ]
        );
    }
}
