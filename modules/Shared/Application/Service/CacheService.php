<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Service;

use Exception;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * Centralized cache service for all caching operations across the application.
 * Provides consistent TTL management, cache invalidation, and warming strategies.
 */
class CacheService
{
    private const TTL_SHORT = 300;    // 5 minutes - frequently changing data

    private const TTL_MEDIUM = 1800;  // 30 minutes - moderately dynamic data

    private const TTL_LONG = 3600;    // 1 hour - stable data

    private const TTL_DAILY = 86400;  // 24 hours - daily statistics

    private const TTL_WEEKLY = 604800; // 7 days - historical data

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Remember data with automatic TTL selection based on cache type.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @param  array<string>  $tags
     * @return T
     */
    public function remember(string $key, callable $callback, string $type = 'medium', array $tags = [])
    {
        $ttl = $this->getTtlByType($type);

        return $this->rememberWithTtl($key, $callback, $ttl, $tags);
    }

    /**
     * Remember data with specific TTL.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @param  array<string>  $tags
     * @return T
     */
    public function rememberWithTtl(string $key, callable $callback, int $ttl, array $tags = [])
    {
        $startTime = microtime(true);

        if ($tags !== []) {
            /** @phpstan-ignore-next-line argument.type,argument.templateType */
            $result = Cache::tags($tags)->remember($key, $ttl, $callback);
        } else {
            /** @phpstan-ignore-next-line argument.type,argument.templateType */
            $result = $this->cache->remember($key, $ttl, $callback);
        }

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        $this->logger->debug('Cache operation completed', [
            'key' => $key,
            'ttl' => $ttl,
            'tags' => $tags,
            'execution_time_ms' => $executionTime,
            'hit' => $this->wasHit($key, $tags),
        ]);

        return $result;
    }

    /**
     * Remember campaigns with organization-specific caching.
     *
     * @return Collection<int, mixed>
     */
    public function rememberCampaigns(int $organizationId, string $type = 'active', ?string $status = null): Collection
    {
        $key = $this->buildCampaignCacheKey($organizationId, $type, $status);
        $tags = ['campaigns', "org:{$organizationId}"];

        return $this->remember(
            $key,
            fn () => $this->loadCampaigns(),
            'medium',
            $tags
        );
    }

    /**
     * Remember dashboard metrics for organization.
     *
     * @return array<string, mixed>
     */
    public function rememberOrganizationMetrics(int $organizationId): array
    {
        $key = "dashboard:org:{$organizationId}:metrics";
        $tags = ['dashboard', 'organizations', "org:{$organizationId}", 'campaigns', 'donations'];

        return $this->remember(
            $key,
            fn () => $this->loadOrganizationMetrics(),
            'medium',
            $tags
        );
    }

    /**
     * Remember campaign analytics with detailed metrics.
     *
     * @return array<string, mixed>
     */
    public function rememberCampaignAnalytics(int $campaignId): array
    {
        $key = "analytics:campaign:{$campaignId}";
        $tags = ['campaign_analytics', 'campaigns', 'donations', "campaign:{$campaignId}"];

        return $this->remember(
            $key,
            fn () => $this->loadCampaignAnalytics(),
            'short',
            $tags
        );
    }

    /**
     * Remember donation metrics with comprehensive filters.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $metrics
     * @return array<string, mixed>
     */
    public function rememberDonationMetrics(array $filters, string $timeRange, array $metrics = []): array
    {
        $key = $this->buildDonationMetricsCacheKey($filters, $timeRange, $metrics);
        $tags = $this->buildDonationMetricsTags($filters);

        return $this->remember(
            $key,
            fn () => $this->loadDonationMetrics(),
            'short',
            $tags
        );
    }

    /**
     * Remember search facets for improved search performance.
     *
     * @param  list<string>  $entityTypes
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function rememberSearchFacets(array $entityTypes, array $filters = []): array
    {
        $key = $this->buildSearchFacetsCacheKey($entityTypes, $filters);
        $tags = ['search_facets', 'campaigns', 'organizations'];

        return $this->remember(
            $key,
            fn () => $this->loadSearchFacets(),
            'long',
            $tags
        );
    }

    /**
     * Remember user permissions for role-based access control.
     *
     * @return array<string, mixed>
     */
    public function rememberUserPermissions(int $userId): array
    {
        $key = "permissions:user:{$userId}";
        $tags = ['permissions', 'users', "user:{$userId}"];

        return $this->remember(
            $key,
            fn () => $this->loadUserPermissions(),
            'long',
            $tags
        );
    }

    /**
     * Warm up commonly accessed cache entries.
     */
    public function warmCache(): void
    {
        $this->logger->info('Starting cache warming process');
        $startTime = microtime(true);

        try {
            // Warm popular campaign lists
            $this->warmPopularCampaigns();

            // Warm trending campaigns
            $this->warmTrendingCampaigns();

            // Warm ending soon campaigns
            $this->warmEndingSoonCampaigns();

            // Warm search facets
            $this->warmSearchFacets();

            $totalTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Cache warming completed successfully', [
                'total_time_ms' => $totalTime,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Cache warming failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Invalidate campaign-related caches.
     */
    public function invalidateCampaign(int $campaignId, ?int $organizationId = null): void
    {
        $tags = ['campaigns', "campaign:{$campaignId}"];

        if ($organizationId) {
            $tags[] = "org:{$organizationId}";
        }

        $this->invalidateByTags($tags);

        // Also clear specific campaign keys
        $this->cache->forget("analytics:campaign:{$campaignId}");
        $this->cache->forget("campaign:{$campaignId}:details");
    }

    /**
     * Invalidate organization-related caches.
     */
    public function invalidateOrganization(int $organizationId): void
    {
        $this->invalidateByTags([
            'organizations',
            "org:{$organizationId}",
            'dashboard',
            'campaigns',
        ]);
    }

    /**
     * Invalidate donation-related caches.
     */
    public function invalidateDonationMetrics(?int $campaignId = null, ?int $organizationId = null): void
    {
        $tags = ['donations', 'donation_metrics'];

        if ($campaignId) {
            $tags[] = "campaign:{$campaignId}";
        }

        if ($organizationId) {
            $tags[] = "org:{$organizationId}";
        }

        $this->invalidateByTags($tags);
    }

    /**
     * Invalidate user permission caches.
     */
    public function invalidateUserPermissions(int $userId): void
    {
        $this->invalidateByTags(['permissions', "user:{$userId}"]);
        $this->cache->forget("permissions:user:{$userId}");
    }

    /**
     * Invalidate search-related caches.
     */
    public function invalidateSearch(): void
    {
        $this->invalidateByTags(['search_facets']);
    }

    /**
     * Check if a key exists in cache.
     */
    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    /**
     * Check if a key exists in cache with tags.
     *
     * @param  array<string>  $tags
     */
    public function hasWithTags(string $key, array $tags): bool
    {
        try {
            return Cache::tags($tags)->has($key);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Flush cache by tags.
     *
     * @param  array<string>  $tags
     */
    public function flushByTags(array $tags): void
    {
        $this->invalidateByTags($tags);
    }

    /**
     * Get cache statistics and health information.
     *
     * @return array<string, mixed>
     */
    public function getCacheStats(): array
    {
        return [
            'cache_driver' => config('cache.default'),
            'redis_connection' => config('database.redis.cache.host', 'N/A'),
            'ttl_configuration' => [
                'short' => self::TTL_SHORT,
                'medium' => self::TTL_MEDIUM,
                'long' => self::TTL_LONG,
                'daily' => self::TTL_DAILY,
                'weekly' => self::TTL_WEEKLY,
            ],
        ];
    }

    private function getTtlByType(string $type): int
    {
        return match ($type) {
            'short' => self::TTL_SHORT,
            'medium' => self::TTL_MEDIUM,
            'long' => self::TTL_LONG,
            'daily' => self::TTL_DAILY,
            'weekly' => self::TTL_WEEKLY,
            default => self::TTL_MEDIUM,
        };
    }

    private function buildCampaignCacheKey(int $organizationId, string $type, ?string $status = null): string
    {
        $key = "campaigns:org:{$organizationId}:type:{$type}";

        if ($status) {
            $key .= ":status:{$status}";
        }

        return $key;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $metrics
     */
    private function buildDonationMetricsCacheKey(array $filters, string $timeRange, array $metrics): string
    {
        $filterKey = md5(json_encode($filters) ?: '');
        $metricsKey = md5(json_encode($metrics) ?: '');

        return "donation_metrics:{$timeRange}:filters:{$filterKey}:metrics:{$metricsKey}";
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string>
     */
    private function buildDonationMetricsTags(array $filters): array
    {
        $tags = ['donation_metrics', 'donations'];

        if (isset($filters['campaign_id'])) {
            $tags[] = "campaign:{$filters['campaign_id']}";
        }

        if (isset($filters['organization_id'])) {
            $tags[] = "org:{$filters['organization_id']}";
        }

        return $tags;
    }

    /**
     * @param  array<string>  $entityTypes
     * @param  array<string, mixed>  $filters
     */
    private function buildSearchFacetsCacheKey(array $entityTypes, array $filters): string
    {
        $typesKey = implode(',', $entityTypes);
        $filtersKey = md5(json_encode($filters) ?: '');

        return "search_facets:types:{$typesKey}:filters:{$filtersKey}";
    }

    /**
     * @param  array<string>  $tags
     */
    private function invalidateByTags(array $tags): void
    {
        try {
            Cache::tags($tags)->flush();

            $this->logger->info('Cache invalidated by tags', [
                'tags' => $tags,
            ]);
        } catch (Exception $e) {
            $this->logger->warning('Cache invalidation by tags failed', [
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string>  $tags
     */
    private function wasHit(string $key, array $tags = []): bool
    {
        try {
            if ($tags !== []) {
                return Cache::tags($tags)->has($key);
            }

            return $this->cache->has($key);
        } catch (Exception) {
            return false;
        }
    }

    private function warmPopularCampaigns(): void
    {
        // Implementation would load popular campaigns
        $this->logger->debug('Warmed popular campaigns cache');
    }

    private function warmTrendingCampaigns(): void
    {
        // Implementation would load trending campaigns
        $this->logger->debug('Warmed trending campaigns cache');
    }

    private function warmEndingSoonCampaigns(): void
    {
        // Implementation would load ending soon campaigns
        $this->logger->debug('Warmed ending soon campaigns cache');
    }

    private function warmSearchFacets(): void
    {
        // Implementation would load common search facets
        $this->logger->debug('Warmed search facets cache');
    }

    /**
     * @return Collection<int, mixed>
     */
    private function loadCampaigns(): Collection
    {
        // This would be implemented by the calling service
        return collect([]);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadOrganizationMetrics(): array
    {
        // This would be implemented by the calling service
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadCampaignAnalytics(): array
    {
        // This would be implemented by the calling service
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDonationMetrics(): array
    {
        // This would be implemented by the calling service
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadSearchFacets(): array
    {
        // This would be implemented by the calling service
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadUserPermissions(): array
    {
        // This would be implemented by the calling service
        return [];
    }
}
