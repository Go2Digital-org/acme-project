<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\Service;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\Dashboard\Infrastructure\Laravel\Jobs\WarmUserDashboardCacheJob;

class UserDashboardCacheService
{
    private const CACHE_TTL = 604800; // 7 days

    private const CACHE_KEYS = [
        'statistics' => 'user:{userId}:statistics',
        'activity_feed' => 'user:{userId}:activity_feed',
        'impact_metrics' => 'user:{userId}:impact_metrics',
        'ranking' => 'user:{userId}:ranking',
        'leaderboard' => 'user:{userId}:leaderboard',
    ];

    private const WARMING_STATUS_KEY = 'user:{userId}:cache_warming_status';

    private const WARMING_PROGRESS_KEY = 'user:{userId}:cache_warming_progress';

    /**
     * @return array<string, mixed>
     */
    public function checkUserCacheStatus(int $userId): array
    {
        $cacheKeys = $this->getUserCacheKeys($userId);
        $status = [
            'hit' => [],
            'miss' => [],
            'warming' => false,
            'overall_status' => 'unknown',
        ];

        // Check if warming is in progress
        $warmingStatus = Cache::get($this->getWarmingStatusKey($userId));
        if ($warmingStatus === 'warming') {
            $status['warming'] = true;
            $status['overall_status'] = 'warming';
        }

        // Check each cache key
        foreach ($cacheKeys as $type => $key) {
            if (Cache::has($key)) {
                $status['hit'][] = $type;

                continue;
            }

            $status['miss'][] = $type;
        }

        // Determine overall status if not warming
        if ($status['warming']) {
            return $status;
        }

        if (empty($status['miss'])) {
            $status['overall_status'] = 'hit';

            return $status;
        }

        if (empty($status['hit'])) {
            $status['overall_status'] = 'miss';

            return $status;
        }

        $status['overall_status'] = 'partial';

        return $status;
    }

    public function warmUserCache(int $userId, bool $force = false): string
    {
        $cacheStatus = $this->checkUserCacheStatus($userId);

        // Don't start warming if already warming and not forced
        if ($cacheStatus['warming'] && ! $force) {
            return 'already_warming';
        }

        // If cache is fully hit and not forced, no need to warm
        if ($cacheStatus['overall_status'] === 'hit' && ! $force) {
            return 'cache_hit';
        }

        // Mark as warming
        $warmingStatusKey = $this->getWarmingStatusKey($userId);
        Cache::put($warmingStatusKey, 'warming', self::CACHE_TTL);

        // Initialize progress
        $this->updateWarmingProgress($userId, 0, 'Initializing cache warming...');

        // Dispatch the job
        $job = new WarmUserDashboardCacheJob($userId);
        $jobId = dispatch($job);

        Log::info('User dashboard cache warming started', [
            'user_id' => $userId,
            'job_id' => $jobId,
            'force' => $force,
        ]);

        return 'warming_started';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCacheWarmingProgress(int $userId): ?array
    {
        $progressKey = $this->getWarmingProgressKey($userId);
        $progress = Cache::get($progressKey);

        if ($progress === null) {
            return null;
        }

        return is_array($progress) ? $progress : json_decode((string) $progress, true);
    }

    public function markWarmingCompleted(int $userId, bool $success = true): void
    {
        $warmingStatusKey = $this->getWarmingStatusKey($userId);
        $progressKey = $this->getWarmingProgressKey($userId);

        if (! $success) {
            Cache::put($warmingStatusKey, 'failed', 300);
            $this->updateWarmingProgress($userId, 0, 'Cache warming failed');
            Cache::forget($progressKey);

            return;
        }

        Cache::put($warmingStatusKey, 'completed', 300); // Keep for 5 minutes
        $this->updateWarmingProgress($userId, 100, 'Cache warming completed successfully');

        // Clean up progress after a delay
        Cache::forget($progressKey);
    }

    public function updateWarmingProgress(int $userId, int $percentage, string $message): void
    {
        $progressKey = $this->getWarmingProgressKey($userId);
        $progress = [
            'user_id' => $userId,
            'percentage' => max(0, min(100, $percentage)),
            'message' => $message,
            'updated_at' => now()->toISOString(),
        ];

        Cache::put($progressKey, $progress, self::CACHE_TTL);
    }

    public function invalidateUserCache(int $userId): void
    {
        $cacheKeys = $this->getUserCacheKeys($userId);

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Also clear warming status
        Cache::forget($this->getWarmingStatusKey($userId));
        Cache::forget($this->getWarmingProgressKey($userId));

        Log::info('User dashboard cache invalidated', [
            'user_id' => $userId,
            'keys_cleared' => array_keys($cacheKeys),
        ]);
    }

    public function getUserStatisticsFromCache(int $userId): mixed
    {
        return Cache::get($this->getUserCacheKey($userId, 'statistics'));
    }

    public function getUserActivityFeedFromCache(int $userId): mixed
    {
        return Cache::get($this->getUserCacheKey($userId, 'activity_feed'));
    }

    public function getUserImpactMetricsFromCache(int $userId): mixed
    {
        return Cache::get($this->getUserCacheKey($userId, 'impact_metrics'));
    }

    public function getUserRankingFromCache(int $userId): mixed
    {
        return Cache::get($this->getUserCacheKey($userId, 'ranking'));
    }

    public function getUserLeaderboardFromCache(int $userId): mixed
    {
        return Cache::get($this->getUserCacheKey($userId, 'leaderboard'));
    }

    public function putUserStatisticsInCache(int $userId, mixed $data): void
    {
        Cache::put($this->getUserCacheKey($userId, 'statistics'), $data, self::CACHE_TTL);
    }

    public function putUserActivityFeedInCache(int $userId, mixed $data): void
    {
        Cache::put($this->getUserCacheKey($userId, 'activity_feed'), $data, self::CACHE_TTL);
    }

    public function putUserImpactMetricsInCache(int $userId, mixed $data): void
    {
        Cache::put($this->getUserCacheKey($userId, 'impact_metrics'), $data, self::CACHE_TTL);
    }

    public function putUserRankingInCache(int $userId, mixed $data): void
    {
        Cache::put($this->getUserCacheKey($userId, 'ranking'), $data, self::CACHE_TTL);
    }

    public function putUserLeaderboardInCache(int $userId, mixed $data): void
    {
        Cache::put($this->getUserCacheKey($userId, 'leaderboard'), $data, self::CACHE_TTL);
    }

    /**
     * @return array<string, string>
     */
    private function getUserCacheKeys(int $userId): array
    {
        $keys = [];
        foreach (self::CACHE_KEYS as $type => $template) {
            $keys[$type] = str_replace('{userId}', (string) $userId, $template);
        }

        return $keys;
    }

    private function getUserCacheKey(int $userId, string $type): string
    {
        if (! isset(self::CACHE_KEYS[$type])) {
            throw new InvalidArgumentException("Unknown cache type: {$type}");
        }

        return str_replace('{userId}', (string) $userId, self::CACHE_KEYS[$type]);
    }

    private function getWarmingStatusKey(int $userId): string
    {
        return str_replace('{userId}', (string) $userId, self::WARMING_STATUS_KEY);
    }

    private function getWarmingProgressKey(int $userId): string
    {
        return str_replace('{userId}', (string) $userId, self::WARMING_PROGRESS_KEY);
    }
}
