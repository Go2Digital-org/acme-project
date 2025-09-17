<?php

declare(strict_types=1);

namespace Modules\Search\Domain\Repository;

use Modules\Search\Domain\Model\SearchQuery;
use Modules\Search\Domain\Model\SearchResult;

interface SearchRepositoryInterface
{
    /**
     * Perform a search query.
     */
    public function search(SearchQuery $query): SearchResult;

    /**
     * Get search suggestions for autocomplete.
     *
     * @return array<int, array{text: string, id: mixed, type: string}>
     */
    public function getSuggestions(string $query, string $index, int $limit = 10): array;

    /**
     * Get popular search terms.
     *
     * @return array<array{term: string, count: int}>
     */
    public function getPopularSearches(int $limit = 10): array;

    /**
     * Get recent searches for a user.
     *
     * @return array<array{query: string, timestamp: int}>
     */
    public function getRecentSearches(?int $userId, int $limit = 10): array;

    /**
     * Save a search query for analytics.
     */
    public function saveSearch(string $query, ?int $userId, int $resultCount): void;

    /**
     * Clear search history for a user.
     */
    public function clearSearchHistory(?int $userId): void;

    /**
     * Get search statistics.
     *
     * @return array{total: int, today: int, unique_users: int, avg_results: float}
     */
    public function getSearchStats(): array;
}
