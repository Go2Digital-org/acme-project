<?php

declare(strict_types=1);

namespace Modules\Search\Domain\Model;

use Modules\Search\Domain\ValueObject\SearchFilters;
use Modules\Search\Domain\ValueObject\SearchSort;

class SearchQuery
{
    public function __construct(
        public readonly string $query,
        /** @var array<int, string> */
        public readonly array $indexes,
        public readonly SearchFilters $filters,
        public readonly SearchSort $sort,
        public readonly int $limit = 20,
        public readonly int $offset = 0,
        public readonly ?string $locale = null,
        public readonly bool $enableHighlighting = true,
        public readonly bool $enableFacets = false,
        public readonly bool $enableTypoTolerance = true,
    ) {}

    /**
     * Convert to array for search engine.
     */
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'q' => $this->query,
            'indexes' => $this->indexes,
            'filters' => $this->filters->toArray(),
            'sort' => $this->sort->toArray(),
            'limit' => $this->limit,
            'offset' => $this->offset,
            'locale' => $this->locale,
            'highlight' => $this->enableHighlighting,
            'facets' => $this->enableFacets,
            'typoTolerance' => $this->enableTypoTolerance,
        ];
    }

    /**
     * Create a query for a single index.
     */
    public static function forIndex(
        string $index,
        string $query,
        ?SearchFilters $filters = null,
        ?SearchSort $sort = null,
        int $limit = 20,
        int $offset = 0,
    ): self {
        return new self(
            query: $query,
            indexes: [$index],
            filters: $filters ?? new SearchFilters,
            sort: $sort ?? new SearchSort,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * Create a query for multiple indexes.
     *
     * @param  array<int, string>  $indexes
     */
    public static function forIndexes(
        array $indexes,
        string $query,
        ?SearchFilters $filters = null,
        ?SearchSort $sort = null,
        int $limit = 20,
        int $offset = 0,
    ): self {
        return new self(
            query: $query,
            indexes: $indexes,
            filters: $filters ?? new SearchFilters,
            sort: $sort ?? new SearchSort,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * Calculate the page number from offset.
     */
    public function getPage(): int
    {
        if ($this->limit === 0) {
            return 1;
        }

        // Handle negative offsets by treating them as 0
        $safeOffset = max(0, $this->offset);

        return (int) floor($safeOffset / $this->limit) + 1;
    }

    /**
     * Check if this is an empty query.
     */
    public function isEmpty(): bool
    {
        return $this->query === '';
    }

    /**
     * Get a cache key for this query.
     */
    public function getCacheKey(): string
    {
        return 'search:' . md5(serialize($this->toArray()));
    }
}
