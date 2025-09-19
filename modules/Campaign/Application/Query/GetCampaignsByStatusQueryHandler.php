<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Query;

use Modules\Campaign\Application\ReadModel\CampaignListReadModel;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;

final readonly class GetCampaignsByStatusQueryHandler
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository
    ) {}

    public function handle(GetCampaignsByStatusQuery $query): CampaignListReadModel
    {
        $filters = [
            'status' => $query->statuses,
            'organization_id' => $query->organizationId,
            'page' => $query->page,
            'per_page' => $query->perPage,
            'sort_by' => $query->sortBy,
            'sort_order' => $query->sortOrder,
        ];

        // Remove null filters
        $filters = array_filter($filters, fn (array|int|string|null $value): bool => $value !== null);

        $paginatedResults = $this->campaignRepository->paginate(
            page: $query->page,
            perPage: $query->perPage,
            filters: $filters,
            sortBy: $query->sortBy,
            sortOrder: $query->sortOrder
        );

        // Transform to array structure expected by ReadModel
        $campaigns = [];
        foreach ($paginatedResults->items() as $campaign) {
            $campaigns[] = [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'slug' => $campaign->slug ?? '',
                'description' => $campaign->description,
                'image_url' => $campaign->featured_image_url,
                'status' => $campaign->status,
                'target_amount' => (float) $campaign->goal_amount,
                'current_amount' => (float) $campaign->current_amount,
                'currency' => $campaign->currency ?? 'USD',
                'progress_percentage' => min(100, ($campaign->current_amount / max(1, $campaign->goal_amount)) * 100),
                'start_date' => $campaign->start_date?->toISOString(),
                'end_date' => $campaign->end_date?->toISOString(),
                'remaining_days' => $campaign->end_date ? max(0, $campaign->end_date->diffInDays(now())) : 0,
                'is_active' => $campaign->status === CampaignStatus::ACTIVE && $campaign->end_date?->isFuture() === true,
                'is_featured' => $campaign->is_featured ?? false,
                'priority' => $campaign->priority ?? 0,
                'organization_id' => $campaign->organization_id,
                'organization_name' => $campaign->organization->getName(),
                'category_id' => $campaign->category_id,
                'category_name' => $campaign->categoryModel?->getName(),
                'donation_count' => $campaign->donations_count ?? 0,
                'unique_donators_count' => $campaign->unique_donators_count ?? 0,
                'view_count' => $campaign->view_count ?? 0,
                'created_at' => $campaign->created_at?->toISOString(),
                'updated_at' => $campaign->updated_at?->toISOString(),
            ];
        }

        // Calculate basic statistics
        $totalAmountRaised = array_sum(array_column($campaigns, 'current_amount'));
        $averageProgress = count($campaigns) > 0
            ? array_sum(array_column($campaigns, 'progress_percentage')) / count($campaigns)
            : 0;

        $statistics = [
            'total_campaigns' => $paginatedResults->total(),
            'active_campaigns' => count(array_filter($campaigns, fn (array $c): bool => $c['status'] === CampaignStatus::ACTIVE)),
            'completed_campaigns' => count(array_filter($campaigns, fn (array $c): bool => $c['status'] === CampaignStatus::COMPLETED)),
            'total_amount_raised' => $totalAmountRaised,
            'average_progress_percentage' => $averageProgress,
        ];

        $data = [
            'current_page' => $paginatedResults->currentPage(),
            'per_page' => $paginatedResults->perPage(),
            'total' => $paginatedResults->total(),
            'last_page' => $paginatedResults->lastPage(),
            'from' => $paginatedResults->firstItem() ?? 0,
            'to' => $paginatedResults->lastItem() ?? 0,
            'campaigns' => $campaigns,
            'filters' => $filters,
            'statistics' => $statistics,
        ];

        return new CampaignListReadModel($data);
    }
}
