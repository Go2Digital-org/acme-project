<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Modules\Search\Infrastructure\ApiPlatform\Handler\Provider\SearchSuggestionsProvider;

#[ApiResource(
    shortName: 'SearchSuggestions',
    description: 'Fast search suggestions for autocomplete',
    operations: [
        new GetCollection(
            uriTemplate: '/search/suggestions',
            paginationEnabled: false,
            provider: SearchSuggestionsProvider::class,
        ),
    ],
)]
final class SearchSuggestionsResource
{
    public string $id;

    public function __construct(
        public string $text = '',
        public mixed $itemId = null,
        public string $type = 'campaign',
    ) {
        $this->id = uniqid('suggestion_', true);
    }
}
