<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Observer;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Shared\Infrastructure\Laravel\Traits\HasTenantAwareCache;

final class CampaignCacheObserver
{
    use HasTenantAwareCache;

    /**
     * Handle the Campaign "created" event.
     */
    public function created(): void
    {
        $this->clearCampaignCaches();
    }

    /**
     * Handle the Campaign "updated" event.
     */
    public function updated(): void
    {
        $this->clearCampaignCaches();
    }

    /**
     * Handle the Campaign "deleted" event.
     */
    public function deleted(): void
    {
        $this->clearCampaignCaches();
    }

    /**
     * Handle the Campaign "restored" event.
     */
    public function restored(): void
    {
        $this->clearCampaignCaches();
    }

    /**
     * Handle the Campaign "force deleted" event.
     */
    public function forceDeleted(): void
    {
        $this->clearCampaignCaches();
    }

    /**
     * Clear all campaign-related caches
     */
    private function clearCampaignCaches(): void
    {
        // Clear tenant-aware campaign list caches
        $this->clearCachePattern('campaigns:list:*');

        // Clear tenant-aware campaign page caches
        $this->clearRedisPattern($this->formatCacheKey('campaigns:page:*'));

        // Clear system campaigns list cache
        $systemKey = 'system:campaigns_list';
        Cache::forget($systemKey);
        Redis::del($this->formatCacheKey($systemKey));
    }

    /**
     * Clear cache keys matching a pattern using Laravel Cache
     */
    private function clearCachePattern(string $pattern): void
    {
        $cursor = 0;

        do {
            $result = Redis::scan($cursor, $pattern, 100);

            if ($result === false) {
                break;
            }

            [$cursor, $foundKeys] = $result;

            if (! empty($foundKeys) && is_array($foundKeys)) {
                /** @var array<int, string> $foundKeys */
                foreach ($foundKeys as $key) {
                    // Remove Redis prefix if present
                    $cacheKey = str_replace('laravel_database_', '', $key);
                    Cache::forget($cacheKey);
                }
            }
        } while ($cursor != 0);
    }

    /**
     * Clear Redis keys matching a pattern directly
     */
    private function clearRedisPattern(string $pattern): void
    {
        $keys = Redis::keys($pattern);

        if (! empty($keys)) {
            Redis::del($keys);
        }
    }
}
