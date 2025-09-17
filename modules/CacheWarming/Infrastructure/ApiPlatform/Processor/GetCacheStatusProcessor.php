<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Infrastructure\ApiPlatform\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Modules\CacheWarming\Application\Query\GetCacheStatusQuery;
use Modules\CacheWarming\Infrastructure\ApiPlatform\Resource\CacheStatusResource;
use Modules\Shared\Application\Query\QueryBusInterface;

/**
 * @implements ProcessorInterface<object, CacheStatusResource>
 *
 * Note: This processor is provided for completeness and potential future use.
 * For GET operations, CacheStatusDataProvider should be used instead.
 * This could be useful for POST operations that trigger cache warming with status return.
 */
final readonly class GetCacheStatusProcessor implements ProcessorInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CacheStatusResource {
        if (! is_object($data)) {
            throw new InvalidArgumentException('Data must be an object');
        }

        // Extract parameters from the data object
        $cacheType = property_exists($data, 'cache_type') ? $data->cache_type : null;
        $includeRecommendations = property_exists($data, 'include_recommendations') && (bool) $data->include_recommendations;

        // Create and execute the query
        $query = new GetCacheStatusQuery(
            cacheType: $cacheType,
            includeProgress: true,
            includeRecommendations: $includeRecommendations
        );

        $result = $this->queryBus->ask($query);

        // Transform the data into the expected API response format
        return CacheStatusResource::fromData($result);
    }
}
