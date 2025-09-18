<?php

declare(strict_types=1);

namespace Modules\Category\Application\Service;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Modules\Category\Domain\Model\Category;
use Modules\Shared\Application\Service\SearchService;

/**
 * Category search service with specialized filtering and caching.
 *
 * @extends SearchService<Category>
 */
class CategorySearchService extends SearchService
{
    protected function getModelClass(): string
    {
        return Category::class;
    }

    protected function getCachePrefix(): string
    {
        return 'category_search';
    }

    protected function getDefaultFilters(): array
    {
        return [
            'is_active' => true,
        ];
    }

    protected function getSearchableAttributesWeights(): array
    {
        return [
            'name' => 3,
            'name_en' => 3,
            'name_fr' => 3,
            'name_de' => 3,
            'description' => 2,
            'description_en' => 2,
            'description_fr' => 2,
            'description_de' => 2,
            'slug' => 1,
        ];
    }

    /**
     * Search categories with active campaigns.
     *
     * @return LengthAwarePaginator<int, Category>
     */
    public function searchWithActiveCampaigns(
        string $query = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->search(
            query: $query,
            filters: ['has_active_campaigns' => true, 'is_active' => true],
            sortBy: 'campaigns_count',
            sortDirection: 'desc',
            perPage: $perPage,
            page: $page
        );
    }

    /**
     * Search categories by status.
     */
    /**
     * @return LengthAwarePaginator<int, Category>
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
            sortBy: 'sort_order',
            sortDirection: 'asc',
            perPage: $perPage,
            page: $page
        );
    }

    /**
     * Get category name suggestions for autocomplete.
     *
     * @return SupportCollection<int, array{id: int, name: string, slug: string, icon: string|null, color: string|null, campaigns_count: int}>
     */
    public function getNameSuggestions(string $query, int $limit = 10): SupportCollection
    {
        if (strlen($query) < 2) {
            return new SupportCollection;
        }

        $cacheKey = $this->getCachePrefix() . ':name_suggestions:' . md5($query . $limit);

        return cache()->remember($cacheKey, self::CACHE_TTL, fn () => Category::search($query)
            ->where('is_active', true)
            ->take($limit)
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->getName(),
                'slug' => $category->slug,
                'icon' => $category->icon,
                'color' => $category->color,
                'campaigns_count' => $category->campaigns_count ?? 0,
            ]));
    }

    /**
     * Get all active categories ordered by sort_order.
     *
     * @return Collection<int, Category>
     */
    public function getActiveOrdered(): Collection
    {
        $cacheKey = $this->getCachePrefix() . ':active_ordered';

        return cache()->remember($cacheKey, self::CACHE_TTL, fn () => Category::search('')
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get());
    }

    /**
     * Get most popular categories by campaign count.
     *
     * @return Collection<int, Category>
     */
    public function getMostPopular(int $limit = 10): Collection
    {
        $cacheKey = $this->getCachePrefix() . ':most_popular:' . $limit;

        return cache()->remember($cacheKey, self::CACHE_TTL, fn () => Category::search('')
            ->where('is_active', true)
            ->where('has_active_campaigns', true)
            ->orderBy('campaigns_count', 'desc')
            ->take($limit)
            ->get());
    }

    /**
     * Get categories by color.
     */
    /**
     * @return LengthAwarePaginator<int, Category>
     */
    public function searchByColor(
        string $color,
        string $query = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->search(
            query: $query,
            filters: ['color' => $color, 'is_active' => true],
            sortBy: 'sort_order',
            sortDirection: 'asc',
            perPage: $perPage,
            page: $page
        );
    }

    /**
     * Get color facets.
     *
     * @return array<string, int>
     */
    public function getColorFacets(string $query = ''): array
    {
        $cacheKey = $this->getCachePrefix() . ':color_facets:' . md5($query);

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($query) {
            $builder = Category::search($query)->where('is_active', true);
            $categories = $builder->take(1000)->get();
            $facets = [];

            foreach ($categories as $category) {
                $color = $category->color;
                if ($color) {
                    $facets[$color] = ($facets[$color] ?? 0) + 1;
                }
            }

            arsort($facets);

            return $facets;
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
            $builder = Category::search($query);
            $categories = $builder->take(1000)->get();
            $facets = [];

            foreach ($categories as $category) {
                $status = $category->status->value;
                $facets[$status] = ($facets[$status] ?? 0) + 1;
            }

            arsort($facets);

            return $facets;
        });
    }

    /**
     * Get categories with no campaigns.
     *
     * @return Collection<int, Category>
     */
    public function getEmpty(int $limit = 20): Collection
    {
        $cacheKey = $this->getCachePrefix() . ':empty:' . $limit;

        return cache()->remember($cacheKey, self::CACHE_TTL, fn () => Category::search('')
            ->where('is_active', true)
            ->where('has_active_campaigns', false)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get());
    }

    /**
     * Search by multilingual name.
     */
    /**
     * @return LengthAwarePaginator<int, Category>
     */
    public function searchMultilingual(
        string $query,
        string $locale = 'en',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        // Search in specific language field first, then fall back to all languages
        $searchQuery = $query;

        return $this->search(
            query: $searchQuery,
            filters: ['is_active' => true],
            sortBy: 'sort_order',
            sortDirection: 'asc',
            perPage: $perPage,
            page: $page
        );
    }
}
