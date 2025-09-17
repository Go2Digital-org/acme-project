<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Query;

use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;

final readonly class ListOrganizationsQueryHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(ListOrganizationsQuery $query): array
    {
        $filters = [
            'status' => $query->statuses,
            'type' => $query->types,
            'category' => $query->category,
            'search' => $query->search,
            'verified' => $query->verified,
            'featured' => $query->featured,
            'page' => $query->page,
            'per_page' => $query->perPage,
            'sort_by' => $query->sortBy,
            'sort_order' => $query->sortOrder,
        ];

        // Remove null filters
        $filters = array_filter($filters, fn ($value) => $value !== null);

        $paginatedResults = $this->organizationRepository->paginate(
            page: $query->page,
            perPage: $query->perPage,
            filters: $filters,
            sortBy: $query->sortBy,
            sortOrder: $query->sortOrder
        );

        // Transform to array structure
        $organizations = [];
        $totalAmountRaised = 0;
        $totalCampaigns = 0;

        foreach ($paginatedResults->items() as $organization) {
            $organizationData = [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'description' => $organization->description,
                'logo_url' => $organization->logo_url,
                'type' => $organization->type,
                'category' => $organization->category,
                'status' => $organization->status,
                'is_verified' => $organization->is_verified ?? false,
                'is_featured' => $organization->is_featured ?? false,
                'website' => $organization->website,
                'city' => $organization->city,
                'state' => $organization->state,
                'country' => $organization->country,
                'total_campaigns' => $organization->campaigns_count ?? 0,
                'active_campaigns' => $organization->active_campaigns_count ?? 0,
                'total_amount_raised' => (float) ($organization->total_amount_raised ?? 0),
                'total_members' => $organization->members_count ?? 0,
                'founded_date' => $organization->founded_date?->toDateString(),
                'created_at' => $organization->created_at?->toISOString(),
                'updated_at' => $organization->updated_at?->toISOString(),
            ];

            $organizations[] = $organizationData;
            $totalAmountRaised += $organizationData['total_amount_raised'];
            $totalCampaigns += $organizationData['total_campaigns'];
        }

        // Calculate statistics
        $statistics = [
            'total_organizations' => $paginatedResults->total(),
            'active_organizations' => count(array_filter($organizations, fn ($o) => $o['status'] === 'active')),
            'verified_organizations' => count(array_filter($organizations, fn ($o) => $o['is_verified'])),
            'featured_organizations' => count(array_filter($organizations, fn ($o) => $o['is_featured'])),
            'total_campaigns' => $totalCampaigns,
            'total_amount_raised' => $totalAmountRaised,
            'average_amount_per_organization' => count($organizations) > 0 ? $totalAmountRaised / count($organizations) : 0,
        ];

        // Get type and category breakdowns
        $typeBreakdown = [];
        $categoryBreakdown = [];

        foreach ($organizations as $org) {
            $typeBreakdown[$org['type']] = ($typeBreakdown[$org['type']] ?? 0) + 1;
            if ($org['category']) {
                $categoryBreakdown[$org['category']] = ($categoryBreakdown[$org['category']] ?? 0) + 1;
            }
        }

        return [
            'pagination' => [
                'current_page' => $paginatedResults->currentPage(),
                'per_page' => $paginatedResults->perPage(),
                'total' => $paginatedResults->total(),
                'last_page' => $paginatedResults->lastPage(),
                'from' => $paginatedResults->firstItem() ?? 0,
                'to' => $paginatedResults->lastItem() ?? 0,
                'has_more_pages' => $paginatedResults->hasMorePages(),
            ],
            'organizations' => $organizations,
            'filters' => $filters,
            'statistics' => $statistics,
            'breakdowns' => [
                'types' => $typeBreakdown,
                'categories' => $categoryBreakdown,
            ],
        ];
    }
}
