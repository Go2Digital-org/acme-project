<?php

declare(strict_types=1);

namespace Modules\Search\Domain\Model;

use Illuminate\Support\Collection;

class SearchResult
{
    /**
     * @param  array<array<string, mixed>>  $hits
     * @param  array<string, array<string, int>>  $facets
     * @param  array<string, mixed>  $suggestions
     */
    public function __construct(
        public readonly array $hits,
        public readonly int $totalHits,
        public readonly float $processingTime,
        public readonly array $facets = [],
        public readonly string $query = '',
        public readonly int $limit = 20,
        public readonly int $offset = 0,
        public readonly ?int $estimatedTotalHits = null,
        public readonly array $suggestions = [],
        public readonly ?string $engine = null,
    ) {}

    /**
     * Get hits as a collection.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getHitsCollection(): Collection
    {
        return collect($this->hits);
    }

    /**
     * Check if there are any results.
     */
    public function hasResults(): bool
    {
        return count($this->hits) > 0;
    }

    /**
     * Get the current page number.
     */
    public function getCurrentPage(): int
    {
        if ($this->limit === 0) {
            return 1;
        }

        return (int) floor($this->offset / $this->limit) + 1;
    }

    /**
     * Get total number of pages.
     */
    public function getTotalPages(): int
    {
        if ($this->limit === 0) {
            return 1;
        }

        return (int) ceil($this->totalHits / $this->limit);
    }

    /**
     * Check if there are more pages.
     */
    public function hasMorePages(): bool
    {
        return $this->getCurrentPage() < $this->getTotalPages();
    }

    /**
     * Get facet distribution for a specific attribute.
     *
     * @return array<string, int>
     */
    public function getFacetDistribution(string $attribute): array
    {
        return $this->facets[$attribute] ?? [];
    }

    /**
     * Check if facets are available.
     */
    public function hasFacets(): bool
    {
        return count($this->facets) > 0;
    }

    /**
     * Get processing time in milliseconds.
     */
    public function getProcessingTimeMs(): float
    {
        return $this->processingTime;
    }

    /**
     * Get processing time formatted as string.
     */
    public function getFormattedProcessingTime(): string
    {
        if ($this->processingTime < 1) {
            return sprintf('%.2fms', $this->processingTime);
        }

        if ($this->processingTime < 1000) {
            return sprintf('%.0fms', $this->processingTime);
        }

        return sprintf('%.2fs', $this->processingTime / 1000);
    }

    /**
     * Get the search query.
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Get the search time (alias for processingTime).
     */
    public function getSearchTime(): float
    {
        return $this->processingTime;
    }

    /**
     * Get the total number of results.
     */
    public function getTotal(): int
    {
        return $this->totalHits;
    }

    /**
     * Get the search engine used.
     */
    public function getEngine(): ?string
    {
        return $this->engine;
    }

    /**
     * Get the search results (alias for hits).
     *
     * @return array<array<string, mixed>>
     */
    public function getResults(): array
    {
        return $this->hits;
    }

    /**
     * Get the facets.
     *
     * @return array<string, array<string, int>>
     */
    public function getFacets(): array
    {
        return $this->facets;
    }

    /**
     * Get search suggestions.
     *
     * @return array<string, mixed>
     */
    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    /**
     * Convert to array for API response.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'hits' => $this->hits,
            'totalHits' => $this->totalHits,
            'processingTime' => $this->processingTime,
            'facets' => $this->facets,
            'query' => $this->query,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'currentPage' => $this->getCurrentPage(),
            'totalPages' => $this->getTotalPages(),
            'hasMorePages' => $this->hasMorePages(),
        ];
    }
}
