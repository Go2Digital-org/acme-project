<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Modules\Search\Infrastructure\ApiPlatform\Handler\Processor\SearchProcessor;
use Modules\Search\Infrastructure\ApiPlatform\Handler\Provider\SearchProvider;

#[ApiResource(
    shortName: 'Search',
    description: 'Ultra-fast search with Meilisearch',
    operations: [
        new Post(
            uriTemplate: '/search',
            inputFormats: [
                'json' => ['application/json'],
                'form' => ['application/x-www-form-urlencoded', 'multipart/form-data'],
            ],
            outputFormats: [
                'json' => ['application/json'],
                'jsonld' => ['application/ld+json'],
            ],
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
     * @param  array<string, mixed>  $results
     * @param  array<string, mixed>  $facets
     * @param  array<string, mixed>  $highlights
     */
    public function __construct(
        public string $query = '',
        /** @var array<string, mixed> */
        public array $results = [],
        /** @var array<string, mixed> */
        public array $facets = [],
        /** @var array<string, mixed> */
        public array $suggestions = [],
        public int $totalResults = 0,
        public float $processingTime = 0,
        /** @var array<string, mixed> */
        public array $highlights = [],
        // Input properties for POST requests
        public ?string $q = null,
        public ?int $limit = null,
        public ?int $page = null,
        public ?string $status = null,
    ) {
        $this->id = uniqid('search_', true);
    }
}
