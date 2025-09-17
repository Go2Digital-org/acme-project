<?php

declare(strict_types=1);

namespace Modules\Shared\Application\ReadModel;

use Exception;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;

/**
 * Cache strategy for read models with warming, invalidation, and refresh logic.
 * Optimized for high-performance applications with 20,000+ users.
 */
final readonly class ReadModelCacheStrategy
{
    private const CACHE_VERSION_KEY = 'read_model_cache_version';

    private const WARMING_LOCK_PREFIX = 'warming_lock:';

    private const REFRESH_LOCK_PREFIX = 'refresh_lock:';

    private const LOCK_TTL = 300; // 5 minutes

    public function __construct(
        private CacheRepository $cache
    ) {}

    /**
     * Get read model from cache or build if not cached.
     *
     * @param  array<string>  $tags
     */
    public function remember(string $cacheKey, callable $builder, ?int $ttl = null, array $tags = []): ReadModelInterface
    {
        try {
            // Try to get from cache first
            $readModel = $this->get($cacheKey);

            if ($readModel instanceof ReadModelInterface) {
                // Check if cache is stale and needs refresh in background
                $this->scheduleRefreshIfStale($cacheKey, $readModel, $builder, $ttl, $tags);

                return $readModel;
            }

            // Build and cache the read model
            return $this->buildAndCache($cacheKey, $builder, $ttl, $tags);
        } catch (Exception $e) {
            Log::warning('ReadModelCacheStrategy::remember failed, falling back to builder', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);

            // Fallback to direct build if cache fails
            return $builder();
        }
    }

    /**
     * Get read model from cache.
     */
    public function get(string $cacheKey): ?ReadModelInterface
    {
        try {
            $cached = $this->cache->get($cacheKey);

            if ($cached && is_array($cached) && isset($cached['data'], $cached['class'])) {
                $class = $cached['class'];

                if (class_exists($class) && is_subclass_of($class, ReadModelInterface::class)) {
                    return $this->deserializeReadModel($cached);
                }
            }

            return null;
        } catch (Exception $e) {
            Log::warning('ReadModelCacheStrategy::get failed', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Put read model in cache.
     *
     * @param  array<string>  $tags
     */
    public function put(string $cacheKey, ReadModelInterface $readModel, ?int $ttl = null, array $tags = []): void
    {
        try {
            $ttl ??= $readModel->getCacheTtl();
            $serializedData = $this->serializeReadModel($readModel);

            if ($this->supportsTagging() && $tags !== []) {
                $this->cache->tags($tags)->put($cacheKey, $serializedData, $ttl);
            } else {
                $this->cache->put($cacheKey, $serializedData, $ttl);
            }

            Log::info('ReadModel cached successfully', [
                'cache_key' => $cacheKey,
                'ttl' => $ttl,
                'tags' => $tags,
            ]);
        } catch (Exception $e) {
            Log::error('ReadModelCacheStrategy::put failed', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate cache by key.
     */
    public function forget(string $cacheKey): void
    {
        try {
            $this->cache->forget($cacheKey);

            Log::info('ReadModel cache invalidated', [
                'cache_key' => $cacheKey,
            ]);
        } catch (Exception $e) {
            Log::error('ReadModelCacheStrategy::forget failed', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate cache by tags.
     *
     * @param  array<string>  $tags
     */
    public function forgetByTags(array $tags): void
    {
        try {
            if ($this->supportsTagging() && $tags !== []) {
                $this->cache->tags($tags)->flush();

                Log::info('ReadModel cache invalidated by tags', [
                    'tags' => $tags,
                ]);
            } else {
                Log::warning('Cache tagging not supported, cannot invalidate by tags', [
                    'tags' => $tags,
                ]);
            }
        } catch (Exception $e) {
            Log::error('ReadModelCacheStrategy::forgetByTags failed', [
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Warm cache with read model.
     *
     * @param  array<string>  $tags
     */
    public function warm(string $cacheKey, callable $builder, ?int $ttl = null, array $tags = []): void
    {
        $lockKey = self::WARMING_LOCK_PREFIX . $cacheKey;

        // Use distributed locking to prevent multiple warming processes
        if ($this->acquireLock($lockKey)) {
            try {
                // Check if still needs warming
                if (! $this->get($cacheKey) instanceof ReadModelInterface) {
                    $readModel = $builder();
                    $this->put($cacheKey, $readModel, $ttl, $tags);

                    Log::info('ReadModel cache warmed', [
                        'cache_key' => $cacheKey,
                    ]);
                }
            } finally {
                $this->releaseLock($lockKey);
            }
        } else {
            Log::info('ReadModel cache warming skipped (locked)', [
                'cache_key' => $cacheKey,
            ]);
        }
    }

    /**
     * Refresh stale cache in background.
     *
     * @param  array<string>  $tags
     */
    public function refresh(string $cacheKey, callable $builder, ?int $ttl = null, array $tags = []): void
    {
        $lockKey = self::REFRESH_LOCK_PREFIX . $cacheKey;

        // Use distributed locking to prevent multiple refresh processes
        if ($this->acquireLock($lockKey)) {
            try {
                $readModel = $builder();
                $this->put($cacheKey, $readModel, $ttl, $tags);

                Log::info('ReadModel cache refreshed', [
                    'cache_key' => $cacheKey,
                ]);
            } finally {
                $this->releaseLock($lockKey);
            }
        } else {
            Log::info('ReadModel cache refresh skipped (locked)', [
                'cache_key' => $cacheKey,
            ]);
        }
    }

    /**
     * Build and cache read model.
     *
     * @param  array<string>  $tags
     */
    private function buildAndCache(string $cacheKey, callable $builder, ?int $ttl = null, array $tags = []): ReadModelInterface
    {
        $readModel = $builder();
        $this->put($cacheKey, $readModel, $ttl, $tags);

        return $readModel;
    }

    /**
     * Schedule refresh if cache is stale.
     *
     * @param  array<string>  $tags
     */
    private function scheduleRefreshIfStale(string $cacheKey, ReadModelInterface $readModel, callable $builder, ?int $ttl = null, array $tags = []): void
    {
        // Check if read model is getting stale (80% of TTL passed)
        $maxAge = $readModel->getCacheTtl() * 0.8;
        $age = time() - (int) $readModel->getVersion();

        if ($age > $maxAge) {
            // Schedule background refresh (in a real implementation, this would use queues)
            Log::info('ReadModel cache is stale, scheduling refresh', [
                'cache_key' => $cacheKey,
                'age' => $age,
                'max_age' => $maxAge,
            ]);

            // For now, we'll do synchronous refresh
            // In production, this should be dispatched to a queue
            $this->refresh($cacheKey, $builder, $ttl, $tags);
        }
    }

    /**
     * Serialize read model for caching.
     *
     * @return array<string, mixed>
     */
    private function serializeReadModel(ReadModelInterface $readModel): array
    {
        return [
            'class' => $readModel::class,
            'data' => $readModel->toArray(),
            'version' => $readModel->getVersion(),
            'cached_at' => time(),
        ];
    }

    /**
     * Deserialize read model from cache.
     *
     * @param  array<string, mixed>  $cached
     */
    private function deserializeReadModel(array $cached): ReadModelInterface
    {
        $class = $cached['class'];
        $data = $cached['data'];
        $version = $cached['version'];

        // Remove metadata from data
        unset($data['id'], $data['version']);

        $readModel = new $class($data, $version);

        if (! $readModel instanceof ReadModelInterface) {
            throw new Exception('Deserialized object must implement ReadModelInterface');
        }

        return $readModel;
    }

    /**
     * Check if cache driver supports tagging.
     */
    private function supportsTagging(): bool
    {
        // Check if the cache store supports tagging
        $store = $this->cache->getStore();

        return method_exists($store, 'tags') ||
               (property_exists($store, 'tags') && is_callable([$store, 'tags']));
    }

    /**
     * Acquire distributed lock.
     */
    private function acquireLock(string $lockKey): bool
    {
        try {
            return $this->cache->add($lockKey, time(), self::LOCK_TTL);
        } catch (Exception $e) {
            Log::warning('Failed to acquire lock', [
                'lock_key' => $lockKey,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Release distributed lock.
     */
    private function releaseLock(string $lockKey): void
    {
        try {
            $this->cache->forget($lockKey);
        } catch (Exception $e) {
            Log::warning('Failed to release lock', [
                'lock_key' => $lockKey,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get cache statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        return [
            'cache_version' => $this->getCacheVersion(),
            'supports_tagging' => $this->supportsTagging(),
            'lock_ttl' => self::LOCK_TTL,
        ];
    }

    /**
     * Get current cache version.
     */
    public function getCacheVersion(): string
    {
        return $this->cache->get(self::CACHE_VERSION_KEY, '1.0.0');
    }

    /**
     * Bump cache version to invalidate all cached read models.
     */
    public function bumpCacheVersion(): void
    {
        $newVersion = (string) time();
        $this->cache->forever(self::CACHE_VERSION_KEY, $newVersion);

        Log::info('Cache version bumped', [
            'new_version' => $newVersion,
        ]);
    }
}
