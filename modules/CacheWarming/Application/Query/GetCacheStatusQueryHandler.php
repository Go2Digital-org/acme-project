<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Application\Query;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\CacheWarming\Application\ReadModel\CacheStatusReadModel;
use Modules\CacheWarming\Domain\Service\CacheWarmingOrchestrator;
use Modules\CacheWarming\Domain\ValueObject\CacheKey;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetCacheStatusQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CacheWarmingOrchestrator $orchestrator
    ) {}

    public function handle(QueryInterface $query): CacheStatusReadModel
    {
        if (! $query instanceof GetCacheStatusQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        $keys = $this->getKeysForCacheType($query->cacheType);
        $status = $this->orchestrator->getCacheStatus($keys);

        Log::debug('GetCacheStatusQueryHandler processing', [
            'cache_type' => $query->cacheType,
            'total_keys' => count($keys),
            'warmed_count' => count($status['warmed']),
            'cold_count' => count($status['cold']),
            'tenant' => function_exists('tenant') && tenant() ? tenant()->getTenantKey() : 'central',
            'warmed_keys' => array_map(fn (CacheKey $key): string => $key->toString(), $status['warmed']),
        ]);

        $result = [
            'total_keys' => count($keys),
            'warmed_count' => count($status['warmed']),
            'cold_count' => count($status['cold']),
            'warmed_keys' => array_map(fn (CacheKey $key): string => $key->toString(), $status['warmed']),
            'cold_keys' => array_map(fn (CacheKey $key): string => $key->toString(), $status['cold']),
            'cache_type' => $query->cacheType ?? 'all',
        ];

        if ($query->includeRecommendations) {
            $recommendations = $this->orchestrator->getWarmingRecommendations();
            $result['recommendations'] = [
                'priority_keys' => array_map(fn (CacheKey $key): string => $key->toString(), $recommendations['priority_keys']),
                'optional_keys' => array_map(fn (CacheKey $key): string => $key->toString(), $recommendations['optional_keys']),
                'skip_keys' => array_map(fn (CacheKey $key): string => $key->toString(), $recommendations['skip_keys']),
            ];
        }

        return new CacheStatusReadModel(
            id: $query->cacheType ?? 'all',
            data: $result
        );
    }

    /**
     * @return CacheKey[]
     */
    private function getKeysForCacheType(?string $cacheType): array
    {
        if ($cacheType === null) {
            return array_map(
                fn (string $key): CacheKey => new CacheKey($key),
                CacheKey::getAllValidKeys()
            );
        }

        return match ($cacheType) {
            'widget' => array_map(
                fn (string $key): CacheKey => new CacheKey($key),
                CacheKey::getWidgetKeys()
            ),
            'system' => array_map(
                fn (string $key): CacheKey => new CacheKey($key),
                CacheKey::getSystemKeys()
            ),
            'all' => array_map(
                fn (string $key): CacheKey => new CacheKey($key),
                CacheKey::getAllValidKeys()
            ),
            default => throw new InvalidArgumentException("Unsupported cache type: {$cacheType}")
        };
    }
}
