<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Application\Command;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\CacheWarming\Domain\ValueObject\CacheKey;

final readonly class InvalidateCacheCommandHandler
{
    public function handle(InvalidateCacheCommand $command): void
    {
        if ($command->specificKeys !== null) {
            $this->invalidateSpecificKeys($command->specificKeys, $command->forceInvalidation);

            return;
        }

        if ($command->cacheType !== null) {
            $this->invalidateCacheType($command->cacheType, $command->forceInvalidation);

            return;
        }

        // Invalidate all cache if no specific type or keys provided
        $this->invalidateAllCache($command->forceInvalidation);
    }

    /** @param array<string> $keys */
    private function invalidateSpecificKeys(array $keys, bool $force): void
    {
        foreach ($keys as $keyString) {
            try {
                $cacheKey = new CacheKey($keyString);

                if ($force || $this->shouldInvalidateKey($cacheKey)) {
                    Cache::forget($cacheKey->toString());
                    Log::info('Cache key invalidated', ['key' => $keyString]);
                }
            } catch (Exception $e) {
                Log::warning('Failed to invalidate cache key', [
                    'key' => $keyString,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function invalidateCacheType(string $cacheType, bool $force): void
    {
        $keys = match ($cacheType) {
            'widget' => CacheKey::getWidgetKeys(),
            'system' => CacheKey::getSystemKeys(),
            'all' => CacheKey::getAllValidKeys(),
            default => throw new InvalidArgumentException("Unknown cache type: {$cacheType}")
        };

        foreach ($keys as $keyString) {
            try {
                $cacheKey = new CacheKey($keyString);

                if ($force || $this->shouldInvalidateKey($cacheKey)) {
                    Cache::forget($cacheKey->toString());
                }
            } catch (Exception $e) {
                Log::warning('Failed to invalidate cache key', [
                    'key' => $keyString,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Cache type invalidated', [
            'cache_type' => $cacheType,
            'keys_processed' => count($keys),
        ]);
    }

    private function invalidateAllCache(bool $force): void
    {
        if ($force) {
            Cache::flush();
            Log::warning('All cache flushed forcefully');

            return;
        }

        $allKeys = CacheKey::getAllValidKeys();
        foreach ($allKeys as $keyString) {
            try {
                $cacheKey = new CacheKey($keyString);
                if ($this->shouldInvalidateKey($cacheKey)) {
                    Cache::forget($cacheKey->toString());
                }
            } catch (Exception $e) {
                Log::warning('Failed to invalidate cache key', [
                    'key' => $keyString,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('All application cache invalidated', [
            'keys_processed' => count($allKeys),
        ]);
    }

    private function shouldInvalidateKey(CacheKey $key): bool
    {
        // Add business logic to determine if a key should be invalidated
        // For example, check if it's stale, has expired, or meets certain criteria
        return Cache::has($key->toString());
    }
}
