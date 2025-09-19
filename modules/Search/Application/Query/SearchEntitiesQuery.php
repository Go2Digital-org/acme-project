<?php

declare(strict_types=1);

namespace Modules\Search\Application\Query;

use Modules\Search\Domain\ValueObject\SearchFilters;
use Modules\Search\Domain\ValueObject\SearchSort;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class SearchEntitiesQuery implements QueryInterface
{
    public function __construct(
        public string $query,
        /** @var array<int, string> */
        public array $entityTypes = ['campaign', 'donation', 'user', 'organization'],
        public ?SearchFilters $filters = null,
        public ?SearchSort $sort = null,
        public int $limit = 20,
        public int $page = 1,
        public ?string $locale = null,
        public bool $enableHighlighting = true,
        public bool $enableFacets = false,
        public bool $enableCache = true,
    ) {}

    /**
     * Get the offset based on page and limit.
     */
    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }

    /**
     * Get filters or create empty filters.
     */
    public function getFilters(): SearchFilters
    {
        return $this->filters ?? new SearchFilters;
    }

    /**
     * Get sort or create default sort.
     */
    public function getSort(): SearchSort
    {
        return $this->sort ?? new SearchSort;
    }

    /**
     * Create from request data.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            query: $data['q'] ?? $data['query'] ?? '',
            entityTypes: $data['types'] ?? ['campaign', 'donation', 'user', 'organization'],
            filters: isset($data['filters']) ? SearchFilters::fromArray($data['filters']) : null,
            sort: isset($data['sort']) ? SearchSort::fromString($data['sort']) : null,
            limit: (int) ($data['limit'] ?? 20),
            page: (int) ($data['page'] ?? 1),
            locale: $data['locale'] ?? null,
            enableHighlighting: (bool) ($data['highlight'] ?? true),
            enableFacets: (bool) ($data['facets'] ?? false),
            enableCache: (bool) ($data['cache'] ?? true),
        );
    }
}
