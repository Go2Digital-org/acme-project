<?php

declare(strict_types=1);

namespace Modules\Dashboard\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Support\Facades\Auth;
use Modules\Dashboard\Application\Query\GetUserDashboardDataQuery;
use Modules\Dashboard\Application\Query\GetUserDashboardDataQueryHandler;
use Modules\Dashboard\Domain\ValueObject\ActivityFeedItem;
use Modules\Dashboard\Domain\ValueObject\DashboardStatistics;
use Modules\Dashboard\Domain\ValueObject\ImpactMetrics;
use Modules\Dashboard\Domain\ValueObject\LeaderboardEntry;
use Modules\Dashboard\Infrastructure\ApiPlatform\Resource\DashboardResource;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @implements ProviderInterface<DashboardResource>
 */
final readonly class DashboardDataProvider implements ProviderInterface
{
    public function __construct(
        private GetUserDashboardDataQueryHandler $queryHandler
    ) {}

    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): object {
        $userId = Auth::id();

        if (! is_int($userId)) {
            throw new HttpException(
                Response::HTTP_UNAUTHORIZED,
                'User not authenticated'
            );
        }

        try {
            $query = new GetUserDashboardDataQuery(
                userId: $userId,
                useCache: true,
                activityFeedLimit: 10,
                leaderboardLimit: 5
            );

            $dashboardData = $this->queryHandler->handle($query);

            return DashboardResource::fromDashboardData(
                userId: $dashboardData->userId,
                statistics: $dashboardData->statistics instanceof DashboardStatistics
                    ? $dashboardData->statistics->toArray()
                    : $dashboardData->statistics,
                activityFeed: array_map(
                    fn ($item) => $item instanceof ActivityFeedItem
                        ? $item->toArray()
                        : $item,
                    $dashboardData->activityFeed
                ),
                impactMetrics: $dashboardData->impactMetrics instanceof ImpactMetrics
                    ? $dashboardData->impactMetrics->toArray()
                    : $dashboardData->impactMetrics,
                ranking: $dashboardData->ranking,
                leaderboard: array_map(
                    fn ($entry) => $entry instanceof LeaderboardEntry
                        ? $entry->toArray()
                        : $entry,
                    $dashboardData->leaderboard
                )
            );
        } catch (RuntimeException $e) {
            if ($e->getCode() === 202) {
                throw new HttpException(
                    Response::HTTP_ACCEPTED,
                    'Dashboard data not available in cache. Please wait for cache warming to complete.',
                    $e
                );
            }
            throw $e;
        }
    }
}
