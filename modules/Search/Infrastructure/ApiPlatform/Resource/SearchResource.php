<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Modules\Search\Infrastructure\ApiPlatform\Handler\Processor\SearchProcessor;
use Modules\Search\Infrastructure\ApiPlatform\Handler\Provider\SearchProvider;
use Modules\Search\Infrastructure\ApiPlatform\Handler\Provider\SearchSuggestionsProvider;

#[ApiResource(
    shortName: 'Search',
    description: 'Ultra-fast search with Meilisearch',
    operations: [
        new GetCollection(
            uriTemplate: '/search/suggestions',
            paginationEnabled: false,
            provider: SearchSuggestionsProvider::class,
        ),
        new Post(
            uriTemplate: '/search',
            processor: SearchProcessor::class,
        ),
        new GetCollection(
            uriTemplate: '/search/facets',
            paginationEnabled: false,
            provider: SearchProvider::class,
        ),
    ],
)]
final class SearchResource
{
    public string $id;

    /**
     * @param  array<int, array<string, mixed>>  $results
     * @param  array<string, array<string, int>>  $facets
     * @param  array<int, string>  $suggestions
     * @param  array<string, array<string, mixed>>  $highlights
     */
    public function __construct(
        public string $query = '',
        public array $results = [],
        public array $facets = [],
        public array $suggestions = [],
        public int $totalResults = 0,
        public float $processingTime = 0,
        public array $highlights = [],
    ) {
        $this->id = uniqid('search_', true);
    }
}
