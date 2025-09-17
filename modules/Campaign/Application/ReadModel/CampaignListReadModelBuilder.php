<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\ReadModel;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;
use RuntimeException;

/**
 * Builder for creating optimized CampaignListReadModel instances.
 * Handles data aggregation, pagination, and performance optimizations.
 */
final readonly class CampaignListReadModelBuilder
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
        private OrganizationRepositoryInterface $organizationRepository
    ) {}

    /**
     * Build a CampaignListReadModel from paginated data.
     *
     * @param  array<string, mixed>  $filters
     */
    public function build(array $filters = [], string $locale = 'en'): CampaignListReadModel
    {
        $paginatedCampaigns = $this->getPaginatedCampaigns($filters);
        $statistics = $this->buildStatistics($filters);
        $categories = $this->getCategories();
        $organizations = $this->getOrganizations();

        $data = [
            'current_page' => $paginatedCampaigns->currentPage(),
            'per_page' => $paginatedCampaigns->perPage(),
            'total' => $paginatedCampaigns->total(),
            'last_page' => $paginatedCampaigns->lastPage(),
            'from' => $paginatedCampaigns->firstItem() ?? 0,
            'to' => $paginatedCampaigns->lastItem() ?? 0,
            'campaigns' => $this->transformCampaigns($paginatedCampaigns->items()),
            'filters' => $filters,
            'statistics' => $statistics,
            'categories' => $categories,
            'organizations' => $organizations,
        ];

        $version = $this->generateVersion($filters);

        return new CampaignListReadModel($data, $version, $locale);
    }

    /**
     * Build from existing collection without pagination.
     *
     * @param  Collection<int, Campaign>  $campaigns
     * @param  array<string, mixed>  $filters
     */
    public function buildFromCollection(Collection $campaigns, array $filters = [], string $locale = 'en'): CampaignListReadModel
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $page = (int) ($filters['page'] ?? 1);
        $total = $campaigns->count();

        $paginatedCampaigns = $campaigns->forPage($page, $perPage);

        $data = [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => (int) ceil($total / $perPage),
            'from' => (($page - 1) * $perPage) + 1,
            'to' => min($page * $perPage, $total),
            'campaigns' => $this->transformCampaigns($paginatedCampaigns),
            'filters' => $filters,
            'statistics' => $this->buildStatistics($filters),
            'categories' => $this->getCategories(),
            'organizations' => $this->getOrganizations(),
        ];

        $version = $this->generateVersion($filters);

        return new CampaignListReadModel($data, $version, $locale);
    }

    /**
     * Get paginated campaigns based on filters.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Campaign>
     */
    private function getPaginatedCampaigns(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $page = (int) ($filters['page'] ?? 1);

        return $this->campaignRepository->paginate($page, $perPage, $filters);
    }

    /**
     * Transform campaigns with optimized data structure.
     *
     * @param  iterable<Campaign>  $campaigns
     * @return array<int, array<string, mixed>>
     */
    private function transformCampaigns(iterable $campaigns): array
    {
        $transformed = [];

        foreach ($campaigns as $campaign) {
            $transformed[] = [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'description' => $campaign->description,
                'slug' => $campaign->slug,
                'status' => $campaign->status,
                'target_amount' => $campaign->target_amount,
                'current_amount' => $campaign->current_amount ?? 0,
                'donation_count' => $campaign->donations_count ?? 0,
                'is_featured' => $campaign->is_featured ?? false,
                'priority' => $campaign->priority ?? 0,
                'created_at' => $campaign->created_at?->toISOString(),
                'deadline' => $campaign->deadline?->toISOString(),
                'image_url' => $campaign->getImageUrl(),
                'shares_count' => $campaign->shares_count ?? 0,
                'views_count' => $campaign->views_count ?? 0,
                'category' => $campaign->categoryModel ? [
                    'id' => $campaign->categoryModel->id,
                    'name' => $campaign->categoryModel->getName(),
                    'slug' => $campaign->categoryModel->slug,
                ] : null,
                'organization' => $campaign->organization ? [
                    'id' => $campaign->organization->getId(),
                    'name' => $campaign->organization->getName(),
                    'slug' => $campaign->organization->getSlug(),
                    'logo_url' => $campaign->organization->getLogoUrl(),
                ] : null,
                'translations' => $this->getTranslations($campaign),
            ];
        }

        return $transformed;
    }

    /**
     * Get campaign translations for the given locale.
     *
     * @return array<string, array<string, string>>
     */
    private function getTranslations(Campaign $campaign): array
    {
        // Use the translatable fields directly from the model
        // since Campaign uses HasTranslations trait
        $translations = [];

        // Get available locales from config or default to common ones
        $locales = ['en', 'fr', 'de', 'es'];

        foreach ($locales as $locale) {
            $title = $campaign->getTranslation('title', $locale);
            $description = $campaign->getTranslation('description', $locale);

            if ($title || $description) {
                $translations[$locale] = [
                    'title' => $title ?? '',
                    'description' => $description ?? '',
                ];
            }
        }

        return $translations;
    }

    /**
     * Build statistics for the campaign list.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function buildStatistics(array $filters): array
    {
        return [
            'total_campaigns' => $this->campaignRepository->countFiltered($filters),
            'active_campaigns' => $this->campaignRepository->countByStatus(CampaignStatus::ACTIVE, $filters),
            'completed_campaigns' => $this->campaignRepository->countByStatus(CampaignStatus::COMPLETED, $filters),
            'total_amount_raised' => $this->campaignRepository->getTotalAmountRaised($filters),
            'average_progress_percentage' => $this->campaignRepository->getAverageProgress($filters),
        ];
    }

    /**
     * Get available categories for filtering.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getCategories(): array
    {
        // This would be injected via CategoryRepositoryInterface if it exists
        // For now, returning empty array as a placeholder
        return [];
    }

    /**
     * Get available organizations for filtering.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getOrganizations(): array
    {
        $organizations = $this->organizationRepository->findActiveOrganizations();

        return array_map(fn ($org) => [
            'id' => $org->getId(),
            'name' => $org->getName(),
            'slug' => $org->getSlug(),
            'campaign_count' => $org->campaigns_count ?? 0,
        ], $organizations);
    }

    /**
     * Generate version hash based on filters and current time.
     *
     * @param  array<string, mixed>  $filters
     */
    private function generateVersion(array $filters): string
    {
        $baseData = [
            'filters' => $filters,
            'timestamp' => time(),
        ];

        $json = json_encode($baseData);
        if ($json === false) {
            throw new RuntimeException('Failed to encode data for version generation');
        }

        return hash('sha256', $json);
    }

    /**
     * Create a quick build for featured campaigns.
     */
    public function buildFeaturedCampaigns(int $limit = 10, string $locale = 'en'): CampaignListReadModel
    {
        $filters = [
            'featured' => true,
            'status' => 'active',
            'per_page' => $limit,
            'sort_by' => 'priority',
            'sort_order' => 'desc',
        ];

        return $this->build($filters, $locale);
    }

    /**
     * Create a quick build for urgent campaigns.
     */
    public function buildUrgentCampaigns(int $limit = 10, string $locale = 'en'): CampaignListReadModel
    {
        $filters = [
            'urgent' => true,
            'status' => 'active',
            'per_page' => $limit,
            'sort_by' => 'deadline',
            'sort_order' => 'asc',
        ];

        return $this->build($filters, $locale);
    }

    /**
     * Create a quick build for campaigns by organization.
     *
     * @param  array<string, mixed>  $additionalFilters
     */
    public function buildByOrganization(int $organizationId, array $additionalFilters = [], string $locale = 'en'): CampaignListReadModel
    {
        $filters = array_merge($additionalFilters, [
            'organization_id' => $organizationId,
        ]);

        return $this->build($filters, $locale);
    }

    /**
     * Create a quick build for recently created campaigns.
     */
    public function buildRecentCampaigns(int $limit = 10, string $locale = 'en'): CampaignListReadModel
    {
        $filters = [
            'status' => 'active',
            'per_page' => $limit,
            'sort_by' => 'created_at',
            'sort_order' => 'desc',
        ];

        return $this->build($filters, $locale);
    }
}
