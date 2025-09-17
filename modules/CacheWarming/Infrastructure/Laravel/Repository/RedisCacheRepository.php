<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Infrastructure\Laravel\Repository;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\CacheWarming\Application\Service\PageStatsCalculator;
use Modules\CacheWarming\Application\Service\WidgetStatsCalculator;
use Modules\CacheWarming\Domain\Repository\CacheRepositoryInterface;
use Modules\CacheWarming\Domain\ValueObject\CacheKey;
use RedisException;

final readonly class RedisCacheRepository implements CacheRepositoryInterface
{
    private const CACHE_PREFIX = 'acme_cache:';

    private const FALLBACK_TTL = 604800;

    private const BATCH_SIZE = 10;

    public function __construct(
        private WidgetStatsCalculator $widgetCalculator,
        private PageStatsCalculator $pageCalculator
    ) {}

    public function warmCache(CacheKey $key): bool
    {
        Log::info('Starting cache warm operation', [
            'key' => $key->toString(),
            'key_type' => $key->getType(),
            'memory_usage_start' => memory_get_usage(true) / 1024 / 1024 . ' MB',
        ]);

        $startTime = microtime(true);

        try {
            // Check if key already exists and is still valid
            $exists = $this->exists($key);
            Log::debug('Cache key existence check', [
                'key' => $key->toString(),
                'exists' => $exists,
            ]);

            // Generate cache data with detailed logging
            Log::debug('Generating cache data', [
                'key' => $key->toString(),
                'key_type' => $key->getType(),
            ]);

            $data = $this->generateCacheData($key);

            if ($data === []) {
                throw new Exception('Generated cache data is empty');
            }

            Log::debug('Cache data generated successfully', [
                'key' => $key->toString(),
                'data_size' => strlen(json_encode($data) ?: '') . ' bytes',
                'data_keys' => array_keys($data),
            ]);

            // Set cache data with logging
            Log::debug('Setting cache data in Redis', [
                'key' => $key->toString(),
                'redis_key' => $this->getRedisKey($key),
            ]);

            $success = $this->set($key, $data);

            if (! $success) {
                throw new Exception('Failed to store data in Redis cache');
            }

            Log::debug('Cache data stored successfully in Redis', [
                'key' => $key->toString(),
                'redis_key' => $this->getRedisKey($key),
            ]);

            // Update metadata
            $this->updateMetadata($key, 'warmed');

            $duration = (microtime(true) - $startTime) * 1000;

            Log::info('Cache warm operation completed successfully', [
                'key' => $key->toString(),
                'duration_ms' => round($duration, 2),
                'memory_usage_end' => memory_get_usage(true) / 1024 / 1024 . ' MB',
                'data_size' => strlen(json_encode($data) ?: '') . ' bytes',
            ]);

            return true;

        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            Log::error('Failed to warm cache', [
                'key' => $key->toString(),
                'key_type' => $key->getType(),
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'duration_ms' => round($duration, 2),
                'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB',
                'trace' => $e->getTraceAsString(),
            ]);

            $this->updateMetadata($key, 'failed');

            // Re-throw the exception so the calling code can handle it appropriately
            throw $e;
        }
    }

    public function exists(CacheKey $key): bool
    {
        try {
            $redisKey = $this->getRedisKey($key);
            $exists = Redis::exists($redisKey);

            Log::debug('Cache key existence check', [
                'cache_key' => $key->toString(),
                'redis_key' => $redisKey,
                'exists_raw' => $exists,
                'exists_bool' => (bool) $exists,
                'tenant' => tenant() ? tenant()->getTenantKey() : 'central',
            ]);

            return (bool) $exists;
        } catch (RedisException $e) {
            Log::error('Redis error checking key existence', [
                'key' => $key->toString(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function get(CacheKey $key): mixed
    {
        try {
            $redisKey = $this->getRedisKey($key);
            $value = Redis::get($redisKey);

            if ($value === null || $value === false) {
                return null;
            }

            $decoded = json_decode($value, true);

            return $decoded ?? $value;

        } catch (RedisException|Exception $e) {
            Log::error('Error retrieving cache value', [
                'key' => $key->toString(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function set(CacheKey $key, mixed $value, ?int $ttl = null): bool
    {
        try {
            $redisKey = $this->getRedisKey($key);
            $serializedValue = json_encode($value);
            $ttl ??= config('cache-warming.ttl', self::FALLBACK_TTL);

            $result = Redis::setex($redisKey, $ttl, $serializedValue);

            if ($result) {
                $this->updateMetadata($key, 'cached');
            }

            return (bool) $result;

        } catch (RedisException|Exception $e) {
            Log::error('Error setting cache value', [
                'key' => $key->toString(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function forget(CacheKey $key): bool
    {
        try {
            $redisKey = $this->getRedisKey($key);
            $result = Redis::del($redisKey);

            if ($result) {
                $this->updateMetadata($key, 'deleted');
            }

            return is_int($result) && $result > 0;

        } catch (RedisException $e) {
            Log::error('Error deleting cache key', [
                'key' => $key->toString(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function flush(): bool
    {
        try {
            $pattern = self::CACHE_PREFIX . 'central:*';

            if (function_exists('tenant') && tenant()) {
                $tenantKey = tenant()->getTenantKey();
                $pattern = self::CACHE_PREFIX . "tenant_{$tenantKey}:*";
                Log::info('Flushing tenant-specific cache', ['tenant_key' => $tenantKey, 'pattern' => $pattern]);
            }

            if (! (function_exists('tenant') && tenant())) {
                Log::info('Flushing central cache', ['pattern' => $pattern]);
            }

            $keys = Redis::keys($pattern);

            if ($keys === []) {
                Log::debug('No cache keys found to flush', ['pattern' => $pattern]);

                return true;
            }

            $result = Redis::del($keys);

            Log::info('Cache flush completed', [
                'pattern' => $pattern,
                'keys_deleted' => $result,
                'keys_found' => count($keys),
            ]);

            if (is_int($result) && $result > 0) {
                DB::table('application_cache')
                    ->where('cache_key', 'like', 'acme_%')
                    ->update(['status' => 'flushed', 'updated_at' => now()]);
            }

            return is_int($result) && $result > 0;

        } catch (RedisException|Exception $e) {
            Log::error('Error flushing cache', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function getTtl(CacheKey $key): ?int
    {
        try {
            $redisKey = $this->getRedisKey($key);
            $ttl = Redis::ttl($redisKey);

            if (! is_int($ttl)) {
                return null;
            }

            return match (true) {
                $ttl < 0 => null, // Key doesn't exist or has no expiration
                default => $ttl,
            };

        } catch (RedisException $e) {
            Log::error('Error getting TTL for key', [
                'key' => $key->toString(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function isHealthy(): bool
    {
        try {
            $testKey = self::CACHE_PREFIX . 'health_check';
            $testValue = 'ping_' . time();

            Redis::setex($testKey, 10, $testValue);
            $retrieved = Redis::get($testKey);
            Redis::del($testKey);

            return $retrieved === $testValue;

        } catch (RedisException $e) {
            Log::error('Redis health check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        try {
            $info = Redis::info();
            $pattern = self::CACHE_PREFIX . '*';
            $keys = Redis::keys($pattern);

            return [
                'redis_version' => $info['redis_version'] ?? 'unknown',
                'connected_clients' => (int) ($info['connected_clients'] ?? 0),
                'used_memory' => $info['used_memory_human'] ?? '0B',
                'total_keys' => count($keys),
                'cache_keys' => $keys,
                'keyspace_hits' => (int) ($info['keyspace_hits'] ?? 0),
                'keyspace_misses' => (int) ($info['keyspace_misses'] ?? 0),
                'hit_rate' => $this->calculateHitRate($info),
            ];

        } catch (RedisException $e) {
            Log::error('Error getting Redis stats', ['error' => $e->getMessage()]);

            return [
                'error' => $e->getMessage(),
                'healthy' => false,
            ];
        }
    }

    /**
     * @param  array<CacheKey>  $keys
     * @return array<string>
     */
    public function warmBatch(array $keys): array
    {
        $warmedKeys = [];
        $chunks = array_chunk($keys, self::BATCH_SIZE);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $key) {
                if (! $key instanceof CacheKey) {
                    continue;
                }

                if ($this->warmCache($key)) {
                    $warmedKeys[] = $key->toString();
                }
            }
        }

        return $warmedKeys;
    }

    /**
     * @return array<string, mixed>
     */
    public function getKeyInfo(CacheKey $key): array
    {
        try {
            $redisKey = $this->getRedisKey($key);
            $exists = Redis::exists($redisKey);

            if (! $exists) {
                return [
                    'exists' => false,
                    'key' => $key->toString(),
                ];
            }

            $ttl = Redis::ttl($redisKey);
            $type = Redis::type($redisKey);
            $value = Redis::get($redisKey);
            $size = strlen($value ?: '');

            return [
                'exists' => true,
                'key' => $key->toString(),
                'type' => $type,
                'ttl' => $ttl,
                'size_bytes' => $size,
                'size_human' => $this->formatBytes($size),
                'cache_type' => $key->getType(),
                'expires_at' => (is_int($ttl) && $ttl > 0) ? now()->addSeconds($ttl)->toISOString() : null,
            ];

        } catch (RedisException|Exception $e) {
            Log::error('Error getting key info', [
                'key' => $key->toString(),
                'error' => $e->getMessage(),
            ]);

            return [
                'exists' => false,
                'key' => $key->toString(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function generateCacheData(CacheKey $key): array
    {
        if ($key->isWidgetKey()) {
            return $this->widgetCalculator->calculateWidgetStats($key->toString());
        }

        if ($key->isSystemKey()) {
            return $this->pageCalculator->calculatePageStats($key->toString());
        }

        throw new Exception("Unknown cache key type: {$key->toString()}");
    }

    private function getRedisKey(CacheKey $key): string
    {
        $baseKey = self::CACHE_PREFIX;

        if (function_exists('tenant') && tenant()) {
            $tenantKey = tenant()->getTenantKey();
            $baseKey .= "tenant_{$tenantKey}:";

            Log::debug('Using tenant-aware cache key', [
                'base_key' => $key->toString(),
                'tenant_key' => $tenantKey,
                'final_key' => $baseKey . $key->toString(),
            ]);

            return $baseKey . $key->toString();
        }

        $baseKey .= 'central:';

        Log::debug('Using central cache key', [
            'base_key' => $key->toString(),
            'final_key' => $baseKey . $key->toString(),
            'tenant_context' => 'none',
        ]);

        return $baseKey . $key->toString();
    }

    private function updateMetadata(CacheKey $key, string $status): void
    {
        try {
            // Only update the columns that exist in the table
            $startTime = microtime(true);

            DB::table('application_cache')->updateOrInsert(
                ['cache_key' => $key->toString()],
                [
                    'calculated_at' => now(),
                    'calculation_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
                    'updated_at' => now(),
                ]
            );
        } catch (Exception $e) {
            Log::error('Failed to update cache metadata', [
                'key' => $key->toString(),
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $info
     */
    private function calculateHitRate(array $info): float
    {
        $hits = (int) ($info['keyspace_hits'] ?? 0);
        $misses = (int) ($info['keyspace_misses'] ?? 0);
        $total = $hits + $misses;

        return $total > 0 ? round(($hits / $total) * 100, 2) : 0.0;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}
