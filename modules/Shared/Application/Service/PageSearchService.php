<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Service;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Modules\Shared\Domain\Model\Page;

/**
 * Page search service with specialized filtering and caching.
 *
 * @extends SearchService<Page>
 */
class PageSearchService extends SearchService
{
    protected function getModelClass(): string
    {
        return Page::class;
    }

    protected function getCachePrefix(): string
    {
        return 'page_search';
    }

    protected function getDefaultFilters(): array
    {
        return [
            'is_published' => true,
        ];
    }

    protected function getSearchableAttributesWeights(): array
    {
        return [
            'title' => 3,
            'title_en' => 3,
            'title_fr' => 3,
            'title_de' => 3,
            'content_plain' => 2,
            'content_plain_en' => 2,
            'content_plain_fr' => 2,
            'content_plain_de' => 2,
            'slug' => 1,
        ];
    }

    /**
     * Search published pages only.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Page>
     */
    public function searchPublished(
        string $query = '',
        array $filters = [],
        string $sortBy = 'order',
        string $sortDirection = 'asc',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        $filters['is_published'] = true;

        return $this->search($query, $filters, $sortBy, $sortDirection, $perPage, $page);
    }

    /**
     * Search draft pages only.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Page>
     */
    public function searchDrafts(
        string $query = '',
        array $filters = [],
        string $sortBy = 'updated_at',
        string $sortDirection = 'desc',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        $filters['is_draft'] = true;

        return $this->search($query, $filters, $sortBy, $sortDirection, $perPage, $page);
    }

    /**
     * Search pages by status.
     *
     * @return LengthAwarePaginator<int, Page>
     */
    public function searchByStatus(
        string $status,
        string $query = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->search(
            query: $query,
            filters: ['status' => $status],
            sortBy: 'order',
            sortDirection: 'asc',
            perPage: $perPage,
            page: $page
        );
    }

    /**
     * Get page title suggestions for autocomplete.
     *
     * @return SupportCollection<int, array{id: int, title: string, slug: string, url: string, status: string, order: int}>
     */
    public function getTitleSuggestions(string $query, int $limit = 10): SupportCollection
    {
        if (strlen($query) < 2) {
            return new SupportCollection;
        }

        $cacheKey = $this->getCachePrefix() . ':title_suggestions:' . md5($query . $limit);

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($query, $limit) {
            return Page::search($query)
                ->where('is_published', true)
                ->take($limit)
                ->get()
                ->map(function (Page $page) {
                    return [
                        'id' => $page->id,
                        'title' => $page->getTranslation('title') ?? 'Untitled Page',
                        'slug' => $page->slug,
                        'url' => $page->url,
                        'status' => $page->status,
                        'order' => $page->order,
                    ];
                });
        });
    }

    /**
     * Get all published pages ordered by display order.
     *
     * @return Collection<int, Page>
     */
    public function getPublishedOrdered(): Collection
    {
        $cacheKey = $this->getCachePrefix() . ':published_ordered';

        return cache()->remember($cacheKey, self::CACHE_TTL, function () {
            return Page::search('')
                ->where('is_published', true)
                ->orderBy('order', 'asc')
                ->get();
        });
    }

    /**
     * Get recently updated pages.
     *
     * @return Collection<int, Page>
     */
    public function getRecentlyUpdated(int $limit = 10): Collection
    {
        $cacheKey = $this->getCachePrefix() . ':recently_updated:' . $limit;

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($limit) {
            return Page::search('')
                ->orderBy('updated_at', 'desc')
                ->take($limit)
                ->get();
        });
    }

    /**
     * Get status facets.
     *
     * @return array<string, int>
     */
    public function getStatusFacets(string $query = ''): array
    {
        $cacheKey = $this->getCachePrefix() . ':status_facets:' . md5($query);

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($query) {
            $builder = Page::search($query);
            $pages = $builder->take(1000)->get();
            $facets = [];

            foreach ($pages as $page) {
                $status = $page->status;
                $facets[$status] = ($facets[$status] ?? 0) + 1;
            }

            arsort($facets);

            return $facets;
        });
    }

    /**
     * Search by content in specific language.
     *
     * @return LengthAwarePaginator<int, Page>
     */
    public function searchContent(
        string $query,
        string $locale = 'en',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->search(
            query: $query,
            filters: ['is_published' => true],
            sortBy: 'order',
            sortDirection: 'asc',
            perPage: $perPage,
            page: $page
        );
    }

    /**
     * Get pages with empty content.
     *
     * @return Collection<int, Page>
     */
    public function getEmptyContent(int $limit = 20): Collection
    {
        $cacheKey = $this->getCachePrefix() . ':empty_content:' . $limit;

        return cache()->remember($cacheKey, self::CACHE_TTL, function () {
            // This would need to be implemented with custom logic
            // since we can't easily filter by empty content in search
            return new Collection;
        });
    }

    /**
     * Search by slug pattern.
     *
     * @return LengthAwarePaginator<int, Page>
     */
    public function searchBySlug(
        string $slugPattern,
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->search(
            query: $slugPattern,
            filters: [],
            sortBy: 'order',
            sortDirection: 'asc',
            perPage: $perPage,
            page: $page
        );
    }

    /**
     * Search by multilingual title.
     *
     * @return LengthAwarePaginator<int, Page>
     */
    public function searchMultilingualTitle(
        string $query,
        string $locale = 'en',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->search(
            query: $query,
            filters: ['is_published' => true],
            sortBy: 'order',
            sortDirection: 'asc',
            perPage: $perPage,
            page: $page
        );
    }
}
