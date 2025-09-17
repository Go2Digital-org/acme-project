<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\Query;

use Modules\Dashboard\Application\ReadModel\DashboardCacheStatusReadModel;
use Modules\Dashboard\Application\Service\UserDashboardCacheService;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetDashboardCacheStatusQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private UserDashboardCacheService $cacheService
    ) {}

    public function handle(QueryInterface $query): DashboardCacheStatusReadModel
    {
        assert($query instanceof GetDashboardCacheStatusQuery);

        $cacheStatus = $this->cacheService->checkUserCacheStatus($query->userId);
        $progress = $this->cacheService->getCacheWarmingProgress($query->userId);

        $status = match ($cacheStatus['overall_status']) {
            'hit' => 'hit',
            'warming' => 'warming',
            default => 'miss'
        };

        $ready = $status === 'hit' && empty($cacheStatus['miss']);

        return new DashboardCacheStatusReadModel(
            status: $status,
            ready: $ready,
            progress: $progress ?? [
                'user_id' => $query->userId,
                'percentage' => $status === 'hit' ? 100 : 0,
                'message' => $status === 'hit' ? 'Cache ready' : 'Cache not available',
                'updated_at' => now()->toISOString(),
            ],
            cacheDetails: [
                'hit_components' => $cacheStatus['hit'],
                'miss_components' => $cacheStatus['miss'],
                'warming' => $cacheStatus['warming'],
            ]
        );
    }
}
