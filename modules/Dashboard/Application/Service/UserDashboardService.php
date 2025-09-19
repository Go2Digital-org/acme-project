<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\Service;

use Modules\Dashboard\Domain\Repository\DashboardRepositoryInterface;
use Modules\Dashboard\Domain\ValueObject\DashboardStatistics;
use Modules\Dashboard\Domain\ValueObject\ImpactMetrics;

/**
 * Service that provides dashboard data with caching support.
 * This service acts as a facade over the repository and cache service.
 */
final readonly class UserDashboardService
{
    public function __construct(
        private DashboardRepositoryInterface $repository,
        private UserDashboardCacheService $cacheService
    ) {}

    public function getUserStatistics(int $userId, bool $useCache = true): DashboardStatistics
    {
        if ($useCache) {
            $cached = $this->cacheService->getUserStatisticsFromCache($userId);
            if ($cached !== null) {
                return $cached;
            }
        }

        $statistics = $this->repository->getUserStatistics($userId);

        if ($useCache) {
            $this->cacheService->putUserStatisticsInCache($userId, $statistics);
        }

        return $statistics;
    }

    /**
     * @return array<int, mixed>
     */
    public function getUserActivityFeed(int $userId, int $limit = 10, bool $useCache = true): array
    {
        if ($useCache) {
            $cached = $this->cacheService->getUserActivityFeedFromCache($userId);
            if ($cached !== null) {
                return $cached;
            }
        }

        $activityFeed = $this->repository->getUserActivityFeed($userId, $limit);

        if ($useCache) {
            $this->cacheService->putUserActivityFeedInCache($userId, $activityFeed);
        }

        return $activityFeed;
    }

    public function getUserImpactMetrics(int $userId, bool $useCache = true): ImpactMetrics
    {
        if ($useCache) {
            $cached = $this->cacheService->getUserImpactMetricsFromCache($userId);
            if ($cached !== null) {
                return $cached;
            }
        }

        $impactMetrics = $this->repository->getUserImpactMetrics($userId);

        if ($useCache) {
            $this->cacheService->putUserImpactMetricsInCache($userId, $impactMetrics);
        }

        return $impactMetrics;
    }

    public function getUserOrganizationRanking(int $userId, bool $useCache = true): int
    {
        if ($useCache) {
            $cached = $this->cacheService->getUserRankingFromCache($userId);
            if ($cached !== null) {
                return $cached;
            }
        }

        $ranking = $this->repository->getUserOrganizationRanking($userId);

        if ($useCache) {
            $this->cacheService->putUserRankingInCache($userId, $ranking);
        }

        return $ranking;
    }

    /**
     * @return array<int, mixed>
     */
    public function getOrganizationLeaderboard(int $organizationId, int $limit = 5, bool $useCache = true): array
    {
        // For organization leaderboard, we can cache per organization or per user
        // Here we'll use the user-specific cache approach
        $authId = auth()->id();
        $userId = is_int($authId) ? $authId : null;
        if ($userId && $useCache) {
            $cached = $this->cacheService->getUserLeaderboardFromCache($userId);
            if ($cached !== null) {
                return $cached;
            }
        }

        $leaderboard = $this->repository->getTopDonatorsLeaderboard($organizationId, $limit);

        if ($userId && $useCache) {
            $this->cacheService->putUserLeaderboardInCache($userId, $leaderboard);
        }

        return $leaderboard;
    }

    /**
     * Get complete dashboard data for a user.
     * This method efficiently retrieves all dashboard components.
     *
     * @return array<string, mixed>
     */
    public function getCompleteUserDashboard(int $userId): array
    {
        // Check cache status first
        $cacheStatus = $this->cacheService->checkUserCacheStatus($userId);

        // If cache is mostly missing or empty, trigger warming
        if ($cacheStatus['overall_status'] === 'miss' || empty($cacheStatus['hit'])) {
            $this->cacheService->warmUserCache($userId);
        }

        return [
            'statistics' => $this->getUserStatistics($userId),
            'activity_feed' => $this->getUserActivityFeed($userId, 10),
            'impact_metrics' => $this->getUserImpactMetrics($userId),
            'ranking' => $this->getUserOrganizationRanking($userId),
            'cache_status' => $cacheStatus,
        ];
    }

    /**
     * Refresh user dashboard cache.
     * This method invalidates existing cache and triggers warming.
     */
    public function refreshUserDashboard(int $userId): string
    {
        $this->cacheService->invalidateUserCache($userId);

        return $this->cacheService->warmUserCache($userId, true);
    }

    /**
     * Get cache warming progress for a user.
     *
     * @return array<string, mixed>|null
     */
    public function getCacheWarmingProgress(int $userId): ?array
    {
        return $this->cacheService->getCacheWarmingProgress($userId);
    }
}
