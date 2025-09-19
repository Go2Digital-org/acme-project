<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Service;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Modules\Organization\Domain\Model\Organization;
use Modules\Shared\Application\Service\SearchService;

/**
 * Organization search service with specialized filtering and caching.
 *
 * @extends SearchService<Organization>
 */
class OrganizationSearchService extends SearchService
{
    protected function getModelClass(): string
    {
        return Organization::class;
    }

    protected function getCachePrefix(): string
    {
        return 'org_search';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDefaultFilters(): array
    {
        return [
            'is_active' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getSearchableAttributesWeights(): array
    {
        return [
            'name' => 3,
            'name_en' => 3,
            'description' => 2,
            'mission' => 2,
            'category' => 2,
            'city' => 1,
            'country' => 1,
        ];
    }

    /**
     * Search organizations by location.
     *
     * @return LengthAwarePaginator<int, Organization>
     */
    public function searchByLocation(
        string $location,
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->search(
            query: $location,
            filters: ['is_active' => true, 'is_verified' => true],
            sortBy: 'verification_date',
            sortDirection: 'desc',
            perPage: $perPage,
            page: $page
        );
    }

    /**
     * Search organizations by category.
     *
     * @return LengthAwarePaginator<int, Organization>
     */
    public function searchByCategory(
        string $category,
        string $query = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->search(
            query: $query,
            filters: ['category' => $category, 'is_active' => true],
            sortBy: 'campaigns_count',
            sortDirection: 'desc',
            perPage: $perPage,
            page: $page
        );
    }

    /**
     * Get verified organizations only.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Organization>
     */
    public function searchVerified(
        string $query = '',
        array $filters = [],
        string $sortBy = 'verification_date',
        string $sortDirection = 'desc',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        $filters['is_verified'] = true;

        return $this->search($query, $filters, $sortBy, $sortDirection, $perPage, $page);
    }

    /**
     * Get organization name suggestions for autocomplete.
     *
     * @return SupportCollection<int, array{id: int, name: string, category: string|null, location: string, is_verified: bool, campaigns_count: int}>
     */
    public function getNameSuggestions(string $query, int $limit = 10): SupportCollection
    {
        if (strlen($query) < 2) {
            return new SupportCollection;
        }

        $cacheKey = $this->getCachePrefix() . ':name_suggestions:' . md5($query . $limit);

        return cache()->remember($cacheKey, self::CACHE_TTL, fn () => Organization::search($query)
            ->where('is_active', true)
            ->take($limit)
            ->get()
            ->map(fn (Organization $org): array => [
                'id' => $org->id,
                'name' => $org->getName(),
                'category' => $org->category,
                'location' => trim($org->city . ', ' . $org->country, ', '),
                'is_verified' => $org->is_verified,
                'campaigns_count' => $org->campaigns_count ?? 0,
            ]));
    }

    /**
     * Get category facets.
     *
     * @return array<string, mixed>
     */
    public function getCategoryFacets(string $query = ''): array
    {
        $cacheKey = $this->getCachePrefix() . ':category_facets:' . md5($query);

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($query): array {
            // This would use Meilisearch's faceting in production
            $builder = Organization::search($query)->where('is_active', true);

            // For now, get unique categories from the results
            $orgs = $builder->take(1000)->get();
            $facets = [];

            foreach ($orgs as $org) {
                $category = $org->category;
                if ($category) {
                    $facets[$category] = ($facets[$category] ?? 0) + 1;
                }
            }

            arsort($facets);

            return $facets;
        });
    }

    /**
     * Get location facets (countries and cities).
     *
     * @return array{countries: array<string, int>, cities: array<string, int>}
     */
    public function getLocationFacets(string $query = ''): array
    {
        $cacheKey = $this->getCachePrefix() . ':location_facets:' . md5($query);

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($query): array {
            $builder = Organization::search($query)->where('is_active', true);
            $orgs = $builder->take(1000)->get();

            $countries = [];
            $cities = [];

            foreach ($orgs as $org) {
                if ($org->country) {
                    $countries[$org->country] = ($countries[$org->country] ?? 0) + 1;
                }
                if ($org->city) {
                    $cities[$org->city] = ($cities[$org->city] ?? 0) + 1;
                }
            }

            arsort($countries);
            arsort($cities);

            return [
                'countries' => $countries,
                'cities' => $cities,
            ];
        });
    }

    /**
     * Get recently verified organizations.
     *
     * @return Collection<int, Organization>
     */
    public function getRecentlyVerified(int $limit = 10): Collection
    {
        $cacheKey = $this->getCachePrefix() . ':recently_verified:' . $limit;

        return cache()->remember($cacheKey, self::CACHE_TTL, fn () => Organization::search('')
            ->where('is_verified', true)
            ->orderBy('verification_date', 'desc')
            ->take($limit)
            ->get());
    }

    /**
     * Get organizations with most campaigns.
     *
     * @return Collection<int, Organization>
     */
    public function getMostActive(int $limit = 10): Collection
    {
        $cacheKey = $this->getCachePrefix() . ':most_active:' . $limit;

        return cache()->remember($cacheKey, self::CACHE_TTL, fn () => Organization::search('')
            ->where('is_active', true)
            ->where('is_verified', true)
            ->orderBy('campaigns_count', 'desc')
            ->take($limit)
            ->get());
    }
}
