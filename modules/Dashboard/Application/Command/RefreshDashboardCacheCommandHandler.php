<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\Command;

use Illuminate\Support\Facades\Cache;
use Modules\Dashboard\Domain\Repository\DashboardRepositoryInterface;

final readonly class RefreshDashboardCacheCommandHandler
{
    public function __construct(
        private DashboardRepositoryInterface $repository
    ) {}

    public function handle(RefreshDashboardCacheCommand $command): void
    {
        $cacheKeys = [
            "dashboard:user:{$command->userId}:statistics",
            "dashboard:user:{$command->userId}:activity",
            "dashboard:user:{$command->userId}:impact",
        ];

        if ($command->forceRefresh) {
            // Clear existing cache
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }
        }

        // Pre-warm cache with fresh data
        $this->repository->getUserStatistics($command->userId);
        $this->repository->getUserActivityFeed($command->userId);
        $this->repository->getUserImpactMetrics($command->userId);
    }
}
