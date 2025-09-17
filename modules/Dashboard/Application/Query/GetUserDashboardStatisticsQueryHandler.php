<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\Query;

use Modules\Dashboard\Application\ReadModel\UserDashboardReadModel;
use Modules\Dashboard\Domain\Repository\DashboardRepositoryInterface;

final readonly class GetUserDashboardStatisticsQueryHandler
{
    public function __construct(
        private DashboardRepositoryInterface $repository
    ) {}

    public function handle(GetUserDashboardStatisticsQuery $query): UserDashboardReadModel
    {
        $statistics = $this->repository->getUserStatistics($query->userId);
        $activityFeed = $this->repository->getUserActivityFeed($query->userId);
        $impactMetrics = $this->repository->getUserImpactMetrics($query->userId);

        return new UserDashboardReadModel(
            id: $query->userId,
            data: [
                'statistics' => $statistics,
                'activity_feed' => $activityFeed,
                'impact_metrics' => $impactMetrics,
            ]
        );
    }
}
