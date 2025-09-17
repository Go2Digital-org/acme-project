<?php

declare(strict_types=1);

namespace Modules\Shared\Application\ReadModel;

use Exception;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;

/**
 * Service for invalidating read model caches based on domain events.
 */
class ReadModelCacheInvalidator
{
    public function __construct(
        private readonly CacheRepository $cache
    ) {}

    /**
     * Invalidate cache by tags.
     *
     * @param  array<int, string>  $tags
     */
    public function invalidateByTags(array $tags): void
    {
        try {
            if (! method_exists($this->cache, 'tags')) { // @phpstan-ignore-line
                // Fallback for cache drivers that don't support tags
                Log::warning('Cache tags not supported, cache not invalidated', ['tags' => $tags]);

                return;
            }

            $this->cache->tags($tags)->flush();
            Log::info('Read model cache invalidated', ['tags' => $tags]);
        } catch (Exception $e) {
            Log::error('Failed to invalidate read model cache', [
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate campaign-related caches.
     */
    public function invalidateCampaignCaches(int $campaignId, int $organizationId): void
    {
        $tags = [
            'campaign_analytics',
            'campaign:' . $campaignId,
            'organization_dashboard',
            'organization:' . $organizationId,
            'campaigns',
        ];

        $this->invalidateByTags($tags);
    }

    /**
     * Invalidate donation-related caches.
     */
    public function invalidateDonationCaches(?int $campaignId = null, ?int $organizationId = null): void
    {
        $tags = [
            'donation_reports',
            'donations',
        ];

        if ($campaignId) {
            $tags[] = 'campaign:' . $campaignId;
            $tags[] = 'campaign_analytics';
        }

        if ($organizationId) {
            $tags[] = 'organization:' . $organizationId;
            $tags[] = 'organization_dashboard';
        }

        $this->invalidateByTags($tags);
    }

    /**
     * Invalidate user-related caches.
     */
    public function invalidateUserCaches(int $userId, ?int $organizationId = null): void
    {
        $tags = [
            'user_profile',
            'user:' . $userId,
        ];

        if ($organizationId) {
            $tags[] = 'organization:' . $organizationId;
            $tags[] = 'organization_dashboard';
        }

        $this->invalidateByTags($tags);
    }

    /**
     * Invalidate organization-related caches.
     */
    public function invalidateOrganizationCaches(int $organizationId): void
    {
        $tags = [
            'organization_dashboard',
            'organization:' . $organizationId,
            'campaigns',
            'donations',
        ];

        $this->invalidateByTags($tags);
    }

    /**
     * Invalidate all read model caches.
     */
    public function invalidateAll(): void
    {
        $tags = [
            'campaign_analytics',
            'donation_reports',
            'organization_dashboard',
            'user_profile',
            'campaigns',
            'donations',
            'organizations',
            'users',
        ];

        $this->invalidateByTags($tags);
    }

    /**
     * Invalidate specific cache key.
     */
    public function invalidateKey(string $key): void
    {
        try {
            $this->cache->forget($key);
            Log::info('Read model cache key invalidated', ['key' => $key]);
        } catch (Exception $e) {
            Log::error('Failed to invalidate cache key', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate cache keys matching pattern.
     * Note: This is a simplified implementation. For production, consider using Redis SCAN.
     */
    public function invalidatePattern(string $pattern): void
    {
        try {
            // For now, log the pattern. In production you'd implement pattern matching
            Log::info('Cache pattern invalidation requested', ['pattern' => $pattern]);

            // If using Redis, you could implement pattern matching here
            // For simplicity, we'll just log it for now
        } catch (Exception $e) {
            Log::error('Failed to invalidate cache pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
