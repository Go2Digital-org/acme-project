<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Domain\Service;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\CacheWarming\Domain\Model\CacheWarmingProgress;
use Modules\CacheWarming\Domain\Repository\CacheRepositoryInterface;
use Modules\CacheWarming\Domain\ValueObject\CacheKey;
use Throwable;

final readonly class CacheWarmingOrchestrator
{
    public function __construct(
        private CacheRepositoryInterface $cacheRepository
    ) {}

    /**
     * Warm all available cache keys
     */
    public function warmAllCaches(): CacheWarmingProgress
    {
        $allKeys = array_map(
            fn (string $key): CacheKey => new CacheKey($key),
            CacheKey::getAllValidKeys()
        );

        return $this->warmCaches($allKeys);
    }

    /**
     * Warm only widget cache keys
     */
    public function warmWidgetCaches(): CacheWarmingProgress
    {
        $widgetKeys = array_map(
            fn (string $key): CacheKey => new CacheKey($key),
            CacheKey::getWidgetKeys()
        );

        return $this->warmCaches($widgetKeys);
    }

    /**
     * Warm only system cache keys
     */
    public function warmSystemCaches(): CacheWarmingProgress
    {
        $systemKeys = array_map(
            fn (string $key): CacheKey => new CacheKey($key),
            CacheKey::getSystemKeys()
        );

        return $this->warmCaches($systemKeys);
    }

    /**
     * Warm specific cache keys
     *
     * @param  CacheKey[]  $keys
     */
    public function warmCaches(array $keys, bool $continueOnFailure = true): CacheWarmingProgress
    {
        if ($keys === []) {
            throw new InvalidArgumentException('At least one cache key must be provided');
        }

        $this->validateCacheKeys($keys);

        Log::info('Starting cache warming operation', [
            'total_keys' => count($keys),
            'keys' => array_map(fn ($key): string => $key->toString(), $keys),
            'continue_on_failure' => $continueOnFailure,
        ]);

        if (! $this->cacheRepository->isHealthy()) {
            Log::error('Cache repository is not healthy, aborting cache warming');

            return CacheWarmingProgress::create(count($keys))->withFailure();
        }

        $progress = CacheWarmingProgress::start(count($keys));
        $currentItem = 0;
        $failedKeys = [];
        $successfulKeys = [];

        try {
            foreach ($keys as $key) {
                Log::debug('Processing cache key', [
                    'key' => $key->toString(),
                    'current_item' => $currentItem + 1,
                    'total_items' => count($keys),
                    'progress_percentage' => round(($currentItem / count($keys)) * 100, 2),
                ]);

                $success = $this->warmSingleCache($key);

                if ($success) {
                    $successfulKeys[] = $key->toString();
                    Log::debug('Cache key warmed successfully', [
                        'key' => $key->toString(),
                        'current_item' => $currentItem + 1,
                    ]);
                }

                if (! $success) {
                    $failedKeys[] = $key->toString();
                    Log::error('Failed to warm cache key', [
                        'key' => $key->toString(),
                        'current_item' => $currentItem + 1,
                        'total_items' => count($keys),
                        'continue_on_failure' => $continueOnFailure,
                    ]);

                    if (! $continueOnFailure) {
                        Log::error('Cache warming stopped due to failure (continueOnFailure=false)', [
                            'failed_key' => $key->toString(),
                            'successful_keys' => count($successfulKeys),
                            'failed_keys' => count($failedKeys),
                        ]);

                        return $progress->withProgress($currentItem)->withFailure();
                    }
                }

                $currentItem++;
                $progress = $progress->withProgress($currentItem);
            }

            $totalProcessed = count($successfulKeys) + count($failedKeys);
            $successRate = $totalProcessed > 0 ? (count($successfulKeys) / $totalProcessed) * 100 : 0;

            Log::info('Cache warming operation completed', [
                'total_keys' => count($keys),
                'successful_keys' => count($successfulKeys),
                'failed_keys' => count($failedKeys),
                'success_rate' => round($successRate, 2) . '%',
                'successful_key_list' => $successfulKeys,
                'failed_key_list' => $failedKeys,
            ]);

            if (count($failedKeys) === 0) {
                return $progress;
            }

            if (count($successfulKeys) === 0) {
                return $progress->withFailure();
            }

            Log::warning('Cache warming completed with partial failures', [
                'total_keys' => count($keys),
                'successful_keys' => count($successfulKeys),
                'failed_keys' => count($failedKeys),
                'current_status' => $progress->status->value,
            ]);

            return $progress;

        } catch (Throwable $e) {
            Log::error('Cache warming operation failed with exception', [
                'error' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'current_item' => $currentItem,
                'total_items' => count($keys),
                'successful_keys' => count($successfulKeys),
                'failed_keys' => count($failedKeys),
                'trace' => $e->getTraceAsString(),
            ]);

            return $progress->withProgress($currentItem)->withFailure();
        }
    }

    /**
     * Warm a single cache key
     */
    public function warmSingleCache(CacheKey $key): bool
    {
        try {
            Log::debug('Attempting to warm single cache key', [
                'key' => $key->toString(),
                'key_type' => $key->getType(),
            ]);

            $result = $this->cacheRepository->warmCache($key);

            if ($result) {
                Log::debug('Successfully warmed cache key', [
                    'key' => $key->toString(),
                ]);

                return $result;
            }

            Log::warning('Cache warming returned false for key', [
                'key' => $key->toString(),
            ]);

            return $result;

        } catch (Throwable $e) {
            Log::error('Exception occurred while warming single cache key', [
                'key' => $key->toString(),
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Check which cache keys are currently warmed (exist in cache)
     *
     * @param  CacheKey[]  $keys
     * @return array{warmed: CacheKey[], cold: CacheKey[]}
     */
    public function getCacheStatus(array $keys): array
    {
        $this->validateCacheKeys($keys);

        $warmed = [];
        $cold = [];

        Log::debug('CacheWarmingOrchestrator checking cache status', [
            'total_keys' => count($keys),
            'tenant' => function_exists('tenant') && tenant() ? tenant()->getTenantKey() : 'central',
        ]);

        foreach ($keys as $key) {
            $isWarmed = $this->isCacheWarmed($key);

            Log::debug('Checking individual key warmth', [
                'key' => $key->toString(),
                'is_warmed' => $isWarmed,
            ]);

            if ($isWarmed) {
                $warmed[] = $key;

                continue;
            }

            $cold[] = $key;
        }

        Log::debug('Cache status check complete', [
            'warmed_count' => count($warmed),
            'cold_count' => count($cold),
        ]);

        return [
            'warmed' => $warmed,
            'cold' => $cold,
        ];
    }

    /**
     * Check if a specific cache key is warmed
     */
    public function isCacheWarmed(CacheKey $key): bool
    {
        try {
            return $this->cacheRepository->exists($key);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Get cache warming recommendations based on current state
     *
     * @return array{priority_keys: CacheKey[], optional_keys: CacheKey[], skip_keys: CacheKey[]}
     */
    public function getWarmingRecommendations(): array
    {
        $allKeys = array_map(
            fn (string $key): CacheKey => new CacheKey($key),
            CacheKey::getAllValidKeys()
        );

        $status = $this->getCacheStatus($allKeys);
        $coldKeys = $status['cold'];

        $priorityKeys = [];
        $optionalKeys = [];

        foreach ($coldKeys as $key) {
            if ($key->isSystemKey()) {
                $priorityKeys[] = $key;

                continue;
            }

            $optionalKeys[] = $key;
        }

        return [
            'priority_keys' => $priorityKeys,
            'optional_keys' => $optionalKeys,
            'skip_keys' => $status['warmed'],
        ];
    }

    /**
     * Create a warming strategy based on cache type
     *
     * @return array<CacheKey>
     */
    public function createWarmingStrategy(string $type): array
    {
        return match ($type) {
            'system' => array_map(
                fn (string $key): CacheKey => new CacheKey($key),
                CacheKey::getSystemKeys()
            ),
            'widget' => array_map(
                fn (string $key): CacheKey => new CacheKey($key),
                CacheKey::getWidgetKeys()
            ),
            'priority' => $this->getPriorityKeys(),
            'all' => array_map(
                fn (string $key): CacheKey => new CacheKey($key),
                CacheKey::getAllValidKeys()
            ),
            default => throw new InvalidArgumentException("Invalid warming strategy: {$type}")
        };
    }

    /**
     * Get priority cache keys (system keys first, then most important widgets)
     *
     * @return CacheKey[]
     */
    private function getPriorityKeys(): array
    {
        $systemKeys = array_map(
            fn (string $key): CacheKey => new CacheKey($key),
            CacheKey::getSystemKeys()
        );

        $priorityWidgetKeys = array_map(
            fn (string $key): CacheKey => new CacheKey($key),
            [
                'campaign_performance',
                'average_donation',
            ]
        );

        return array_merge($systemKeys, $priorityWidgetKeys);
    }

    /**
     * Validate that all provided keys are CacheKey instances
     *
     * @param  array<mixed>  $keys
     */
    private function validateCacheKeys(array $keys): void
    {
        foreach ($keys as $key) {
            if (! $key instanceof CacheKey) {
                throw new InvalidArgumentException('All keys must be CacheKey instances');
            }
        }
    }
}
