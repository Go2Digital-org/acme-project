<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Application\Command;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\CacheWarming\Domain\ValueObject\CacheKey;
use Modules\CacheWarming\Infrastructure\Laravel\Jobs\WarmCacheJob;

final readonly class ScheduleCacheWarmingCommandHandler
{
    public function handle(ScheduleCacheWarmingCommand $command): void
    {
        $keys = $this->resolveKeys($command);

        if ($keys === []) {
            Log::warning('No cache keys to warm for scheduling');

            return;
        }

        $cacheKeys = array_map(
            fn (string $keyString): CacheKey => new CacheKey($keyString),
            $keys
        );

        if ($command->delayInSeconds > 0) {
            $this->scheduleDelayedWarming($cacheKeys, $command->delayInSeconds, $command->highPriority);

            return;
        }

        $this->scheduleImmediateWarming($cacheKeys, $command->highPriority);

        Log::info('Cache warming scheduled', [
            'keys_count' => count($keys),
            'delay_seconds' => $command->delayInSeconds,
            'high_priority' => $command->highPriority,
            'cache_type' => $command->cacheType,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function resolveKeys(ScheduleCacheWarmingCommand $command): array
    {
        if ($command->specificKeys !== null) {
            return $command->specificKeys;
        }

        if ($command->cacheType !== null) {
            return match ($command->cacheType) {
                'widget' => CacheKey::getWidgetKeys(),
                'system' => CacheKey::getSystemKeys(),
                'all' => CacheKey::getAllValidKeys(),
                default => throw new InvalidArgumentException("Unknown cache type: {$command->cacheType}")
            };
        }

        return CacheKey::getAllValidKeys();
    }

    /**
     * @param  array<int, CacheKey>  $cacheKeys
     */
    private function scheduleImmediateWarming(array $cacheKeys, bool $highPriority): void
    {
        foreach ($cacheKeys as $cacheKey) {
            if ($highPriority) {
                dispatch(new WarmCacheJob($cacheKey->toString()))
                    ->onQueue('high');

                continue;
            }

            dispatch(new WarmCacheJob($cacheKey->toString()));
        }
    }

    /**
     * @param  array<int, CacheKey>  $cacheKeys
     */
    private function scheduleDelayedWarming(array $cacheKeys, int $delayInSeconds, bool $highPriority): void
    {
        foreach ($cacheKeys as $cacheKey) {
            $job = new WarmCacheJob($cacheKey->toString());
            $job->delay($delayInSeconds);

            if ($highPriority) {
                dispatch($job)->onQueue('high');

                continue;
            }

            dispatch($job);
        }
    }
}
