<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\Query;

use Exception;
use Modules\Dashboard\Application\ReadModel\UserDashboardDataReadModel;
use Modules\Dashboard\Application\Service\UserDashboardService;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;
use Modules\User\Infrastructure\Laravel\Models\User;
use RuntimeException;

final readonly class GetUserDashboardDataQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private UserDashboardService $dashboardService
    ) {}

    public function handle(QueryInterface $query): UserDashboardDataReadModel
    {
        assert($query instanceof GetUserDashboardDataQuery);

        try {
            $statistics = $this->dashboardService->getUserStatistics($query->userId, $query->useCache);
            $activityFeed = $this->dashboardService->getUserActivityFeed(
                $query->userId,
                $query->activityFeedLimit,
                $query->useCache
            );
            $impactMetrics = $this->dashboardService->getUserImpactMetrics($query->userId, $query->useCache);
            $ranking = $this->dashboardService->getUserOrganizationRanking($query->userId, $query->useCache);

            $leaderboard = [];
            $user = User::find($query->userId);
            if ($user && $user->organization_id) {
                $leaderboard = $this->dashboardService->getOrganizationLeaderboard(
                    $user->organization_id,
                    $query->leaderboardLimit,
                    $query->useCache
                );
            }

            return new UserDashboardDataReadModel(
                userId: $query->userId,
                statistics: $statistics,
                activityFeed: $activityFeed,
                impactMetrics: $impactMetrics,
                ranking: $ranking,
                leaderboard: $leaderboard,
                generatedAt: now()->toISOString() ?? '',
                fromCache: true
            );
        } catch (Exception $e) {
            throw new RuntimeException(
                'Dashboard data not available in cache. Please wait for cache warming to complete.',
                202,
                $e
            );
        }
    }
}
