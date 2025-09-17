<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Modules\CacheWarming\Application\Query\GetCacheStatusQuery;
use Modules\CacheWarming\Application\Query\GetCacheStatusQueryHandler;
use Modules\CacheWarming\Infrastructure\ApiPlatform\Resource\CacheStatusResource;

/**
 * @implements ProviderInterface<CacheStatusResource>
 */
final readonly class CacheStatusProvider implements ProviderInterface
{
    public function __construct(
        private GetCacheStatusQueryHandler $queryHandler,
    ) {}

    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CacheStatusResource {
        // Extract query parameters from context
        $request = $context['request'] ?? null;
        $cacheType = $request?->query->get('cache_type');
        $includeRecommendations = (bool) ($request?->query->get('include_recommendations', false));

        // Create and execute the query
        $query = new GetCacheStatusQuery(
            cacheType: $cacheType,
            includeProgress: true,
            includeRecommendations: $includeRecommendations
        );

        $data = $this->queryHandler->handle($query);

        // Transform the data into the expected API response format
        return CacheStatusResource::fromData($data->toArray());
    }
}
