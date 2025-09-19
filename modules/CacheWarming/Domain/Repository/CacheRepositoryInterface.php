<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Domain\Repository;

use Modules\CacheWarming\Domain\ValueObject\CacheKey;

interface CacheRepositoryInterface
{
    /**
     * Warm a specific cache key by generating and storing its value
     */
    public function warmCache(CacheKey $key): bool;

    /**
     * Check if a cache key exists and has a value
     */
    public function exists(CacheKey $key): bool;

    /**
     * Get the value for a cache key without warming it
     */
    public function get(CacheKey $key): mixed;

    /**
     * Set a value for a cache key
     */
    public function set(CacheKey $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Remove a specific cache key
     */
    public function forget(CacheKey $key): bool;

    /**
     * Clear all cache keys
     */
    public function flush(): bool;

    /**
     * Get the TTL (time to live) for a cache key in seconds
     * Returns null if key doesn't exist or has no expiration
     */
    public function getTtl(CacheKey $key): ?int;

    /**
     * Check if the cache storage is available and healthy
     */
    public function isHealthy(): bool;

    /**
     * Get cache statistics (hits, misses, size, etc.)
     *
     * @return array<string, mixed>
     */
    public function getStats(): array;

    /**
     * Warm multiple cache keys in batch
     * Returns array of successfully warmed keys
     *
     * @param  array<int, CacheKey>  $keys
     * @return array<int, string>
     */
    public function warmBatch(array $keys): array;

    /**
     * Get information about a cache key (size, ttl, type, etc.)
     *
     * @return array<string, mixed>
     */
    public function getKeyInfo(CacheKey $key): array;
}
