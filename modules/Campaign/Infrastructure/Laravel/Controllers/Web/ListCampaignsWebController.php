<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Campaign\Application\ViewPresenter\CampaignCardPresenter;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Category\Domain\Model\Category;
use Modules\Category\Domain\Repository\CategoryRepositoryInterface;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;

class ListCampaignsWebController
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
    ) {}

    public function __invoke(Request $request): View
    {
        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'category_id' => $request->get('category_id'), // Changed from 'category' to 'category_id'
            'organization_id' => $request->get('organization'),
            'filter' => $request->get('filter'), // Add filter parameter
        ];

        // Remove null values from filters
        $filters = array_filter($filters, fn ($value): bool => $value !== null);

        $page = max(1, (int) $request->get('page', 1));
        $perPage = max(1, min(50, (int) $request->get('per_page', 12)));

        // Handle sort parameters from the dropdown
        $sortByParam = $request->get('sort_by', 'featured');
        [$sortBy, $sortOrder] = $this->mapSortOption($sortByParam);

        // Override sort for specific filters
        if (isset($filters['filter'])) {
            [$sortBy, $sortOrder] = $this->getSortForFilter($filters['filter'], $sortBy, $sortOrder);
        }

        $campaignsPaginated = $this->campaignRepository->searchWithTranslations(
            page: $page,
            perPage: $perPage,
            filters: $filters,
            sortBy: $sortBy,
            sortOrder: $sortOrder,
        );

        // Get active categories from the database
        $categories = $this->categoryRepository->findActive()->map(fn (Category $category): object => (object) [
            'id' => $category->id,
            'slug' => $category->slug,
            'name' => $category->getName(),
            'description' => $category->getDescription(),
            'icon' => $category->icon,
            'color' => $category->color,
            'campaigns_count' => $campaignsPaginated->getCollection()->filter(fn (Campaign $campaign): bool => $campaign->category_id === $category->id)->count(),
        ]);

        // Get organizations for filtering
        // In tenant context, this will only return the current organization
        // In central context, this will return all active organizations
        $organizations = $this->organizationRepository->findActiveOrganizations();
        $organizationOptions = collect($organizations)
            ->filter() // Remove any null values
            ->mapWithKeys(fn (Organization $org): array => [$org->id => $org->getName()])
            ->toArray();

        // Prepare campaign presentation data separately
        $searchTerm = $request->get('search');
        $campaignPresentationData = $campaignsPaginated->getCollection()->mapWithKeys(function (Campaign $campaign) use ($searchTerm): array {
            $presenter = new CampaignCardPresenter($campaign, $searchTerm);

            return [$campaign->id => $presenter->present()];
        });

        return view('campaigns.index', [
            'campaigns' => $campaignsPaginated,
            'campaignPresentationData' => $campaignPresentationData,
            'categories' => $categories,
            'organizations' => $organizationOptions,
            'filters' => array_merge([
                'search' => '',
                'status' => '',
                'category_id' => '',
                'organization' => '',
                'filter' => '',
                'sort_by' => 'created_at',
                'sort_order' => 'desc',
                'per_page' => 12,
            ], $filters, $request->only(['sort_by', 'sort_order', 'per_page'])),
            'currentFilter' => $request->get('filter'),
            'searchTerm' => $request->get('search'),
            'currentSort' => $request->get('sort_by', 'featured'),
        ]);
    }

    /**
     * Map frontend sort options to backend sort parameters.
     *
     * @return array{string, string}
     */
    private function mapSortOption(string $sortOption): array
    {
        return match ($sortOption) {
            'featured' => ['is_featured', 'desc'], // Featured campaigns first, then by creation date
            'newest' => ['created_at', 'desc'],
            'ending-soon' => ['end_date', 'asc'],
            'most-funded' => ['current_amount', 'desc'],
            'least-funded' => ['current_amount', 'asc'],
            'alphabetical' => ['title', 'asc'],
            default => ['created_at', 'desc'], // Default fallback
        };
    }

    /**
     * Get sort parameters for specific filters.
     *
     * @return array{string, string}
     */
    private function getSortForFilter(string $filter, string $defaultSortBy, string $defaultSortOrder): array
    {
        return match ($filter) {
            'active-only' => ['created_at', 'desc'],
            'popular' => ['donations_count', 'desc'],
            'recent' => ['created_at', 'desc'],
            'ending-soon' => ['end_date', 'asc'],
            'favorites' => ['created_at', 'desc'],
            default => [$defaultSortBy, $defaultSortOrder],
        };
    }
}
