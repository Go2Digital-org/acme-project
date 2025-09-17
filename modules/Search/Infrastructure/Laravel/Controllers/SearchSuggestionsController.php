<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Search\Domain\Service\SearchEngineInterface;
use Modules\Shared\Infrastructure\Laravel\Controllers\BaseController;

class SearchSuggestionsController extends BaseController
{
    public function __construct(
        private readonly SearchEngineInterface $searchEngine,
    ) {}

    /**
     * Get search suggestions for autocomplete.
     */
    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
            'type' => 'string|in:campaign,donation,user,organization',
            'limit' => 'integer|min:1|max:20',
        ]);

        $query = $validated['q'];
        $type = $validated['type'] ?? 'campaign';
        $limit = (int) ($validated['limit'] ?? 10);

        // Map entity type to index name
        $indexName = $this->getIndexName($type);

        // Get suggestions from search engine
        $suggestions = $this->searchEngine->suggest($indexName, $query, $limit);

        return response()->json([
            'success' => true,
            'data' => [
                'query' => $query,
                'suggestions' => $suggestions,
            ],
        ]);
    }

    /**
     * Get index name for entity type.
     */
    private function getIndexName(string $entityType): string
    {
        $mapping = [
            'campaign' => 'acme_campaigns',
            'donation' => 'acme_donations',
            'user' => 'acme_users',
            'organization' => 'acme_organizations',
        ];

        return $mapping[$entityType] ?? 'acme_campaigns';
    }
}
