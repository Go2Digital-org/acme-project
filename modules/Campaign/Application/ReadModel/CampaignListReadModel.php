<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\ReadModel;

use DateTime;
use JsonSerializable;
use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Campaign list read model optimized for listing and filtering campaigns.
 * Performance optimized for 20,000+ users with pre-calculated fields and caching.
 */
final class CampaignListReadModel extends AbstractReadModel implements JsonSerializable
{
    private readonly string $locale;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        array $data,
        ?string $version = null,
        ?string $locale = null
    ) {
        parent::__construct(0, $data, $version); // List doesn't have single ID
        $this->locale = $locale ?? app()->getLocale();
        $this->setCacheTtl(600); // 10 minutes for campaign lists
    }

    /**
     * @return array<string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'campaign_list',
            'campaigns',
            'locale:' . $this->locale,
            'campaign_list_' . md5(json_encode($this->getFilters()) ?: '{}'),
        ]);
    }

    /**
     * Get current locale for translations.
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Get pre-calculated campaign data optimized for performance.
     * Includes progress percentage, days remaining, donation count.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOptimizedCampaigns(): array
    {
        $campaigns = $this->getCampaigns();

        return array_map(fn (array $campaign): array => [
            'id' => $campaign['id'],
            'title' => $this->getTranslatedTitle($campaign),
            'description' => $this->getTranslatedDescription($campaign),
            'slug' => $campaign['slug'],
            'status' => $campaign['status'],
            'target_amount' => (float) $campaign['target_amount'],
            'current_amount' => (float) $campaign['current_amount'],
            'progress_percentage' => $this->calculateProgressPercentage($campaign),
            'days_remaining' => $this->calculateDaysRemaining($campaign),
            'donation_count' => (int) ($campaign['donation_count'] ?? 0),
            'is_featured' => (bool) ($campaign['is_featured'] ?? false),
            'is_urgent' => $this->isUrgent($campaign),
            'category' => $campaign['category'] ?? null,
            'organization' => $campaign['organization'] ?? null,
            'created_at' => $campaign['created_at'],
            'deadline' => $campaign['deadline'] ?? null,
            'image_url' => $campaign['image_url'] ?? null,
            'formatted_target_amount' => $this->formatCurrency($campaign['target_amount']),
            'formatted_current_amount' => $this->formatCurrency($campaign['current_amount']),
            'shares_count' => (int) ($campaign['shares_count'] ?? 0),
            'views_count' => (int) ($campaign['views_count'] ?? 0),
        ], $campaigns);
    }

    // Pagination Information
    public function getCurrentPage(): int
    {
        return (int) $this->get('current_page', 1);
    }

    public function getPerPage(): int
    {
        return (int) $this->get('per_page', 15);
    }

    public function getTotal(): int
    {
        return (int) $this->get('total', 0);
    }

    public function getLastPage(): int
    {
        return (int) $this->get('last_page', 1);
    }

    public function getFrom(): int
    {
        return (int) $this->get('from', 0);
    }

    public function getTo(): int
    {
        return (int) $this->get('to', 0);
    }

    public function hasMorePages(): bool
    {
        return $this->getCurrentPage() < $this->getLastPage();
    }

    public function hasPreviousPage(): bool
    {
        return $this->getCurrentPage() > 1;
    }

    // Campaign Data
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCampaigns(): array
    {
        return $this->get('campaigns', []);
    }

    public function getCampaignCount(): int
    {
        return count($this->getCampaigns());
    }

    public function isEmpty(): bool
    {
        return $this->getCampaignCount() === 0;
    }

    // Filters Information
    /**
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return $this->get('filters', []);
    }

    public function getSearchQuery(): ?string
    {
        $filters = $this->getFilters();

        return $filters['search'] ?? null;
    }

    public function getStatusFilter(): ?string
    {
        $filters = $this->getFilters();

        return $filters['status'] ?? null;
    }

    public function getCategoryFilter(): ?int
    {
        $filters = $this->getFilters();
        $categoryId = $filters['category_id'] ?? null;

        return $categoryId ? (int) $categoryId : null;
    }

    public function getOrganizationFilter(): ?int
    {
        $filters = $this->getFilters();
        $orgId = $filters['organization_id'] ?? null;

        return $orgId ? (int) $orgId : null;
    }

    public function getMinAmountFilter(): ?float
    {
        $filters = $this->getFilters();
        $minAmount = $filters['min_amount'] ?? null;

        return $minAmount ? (float) $minAmount : null;
    }

    public function getMaxAmountFilter(): ?float
    {
        $filters = $this->getFilters();
        $maxAmount = $filters['max_amount'] ?? null;

        return $maxAmount ? (float) $maxAmount : null;
    }

    public function getDateFromFilter(): ?string
    {
        $filters = $this->getFilters();

        return $filters['date_from'] ?? null;
    }

    public function getDateToFilter(): ?string
    {
        $filters = $this->getFilters();

        return $filters['date_to'] ?? null;
    }

    public function isFeaturedFilter(): ?bool
    {
        $filters = $this->getFilters();
        $featured = $filters['featured'] ?? null;

        return $featured !== null ? (bool) $featured : null;
    }

    public function hasActiveFilters(): bool
    {
        $filters = $this->getFilters();
        unset($filters['page'], $filters['per_page'], $filters['sort_by'], $filters['sort_order']);

        return $filters !== [];
    }

    // Sorting Information
    public function getSortBy(): string
    {
        $filters = $this->getFilters();

        return $filters['sort_by'] ?? 'created_at';
    }

    public function getSortOrder(): string
    {
        $filters = $this->getFilters();

        return $filters['sort_order'] ?? 'desc';
    }

    public function isSortedBy(string $column): bool
    {
        return $this->getSortBy() === $column;
    }

    public function isSortedAsc(): bool
    {
        return $this->getSortOrder() === 'asc';
    }

    public function isSortedDesc(): bool
    {
        return $this->getSortOrder() === 'desc';
    }

    // Statistics
    /**
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        return $this->get('statistics', []);
    }

    public function getTotalCampaigns(): int
    {
        $stats = $this->getStatistics();

        return (int) ($stats['total_campaigns'] ?? 0);
    }

    public function getActiveCampaigns(): int
    {
        $stats = $this->getStatistics();

        return (int) ($stats['active_campaigns'] ?? 0);
    }

    public function getCompletedCampaigns(): int
    {
        $stats = $this->getStatistics();

        return (int) ($stats['completed_campaigns'] ?? 0);
    }

    public function getTotalAmountRaised(): float
    {
        $stats = $this->getStatistics();

        return (float) ($stats['total_amount_raised'] ?? 0);
    }

    public function getAverageProgressPercentage(): float
    {
        $stats = $this->getStatistics();

        return (float) ($stats['average_progress_percentage'] ?? 0);
    }

    // Categories
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCategories(): array
    {
        return $this->get('categories', []);
    }

    public function hasCategories(): bool
    {
        return $this->getCategories() !== [];
    }

    // Organizations
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getOrganizations(): array
    {
        return $this->get('organizations', []);
    }

    public function hasOrganizations(): bool
    {
        return $this->getOrganizations() !== [];
    }

    // Featured Campaigns
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFeaturedCampaigns(): array
    {
        return array_filter($this->getCampaigns(), fn (array $campaign): mixed => $campaign['is_featured'] ?? false);
    }

    public function hasFeaturedCampaigns(): bool
    {
        return $this->getFeaturedCampaigns() !== [];
    }

    // Urgent Campaigns
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUrgentCampaigns(): array
    {
        return array_filter($this->getCampaigns(), fn (array $campaign): bool => ($campaign['priority'] ?? 0) >= 5);
    }

    public function hasUrgentCampaigns(): bool
    {
        return $this->getUrgentCampaigns() !== [];
    }

    // Campaigns by Status
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCampaignsByStatus(string $status): array
    {
        return array_filter($this->getCampaigns(), fn (array $campaign): bool => ($campaign['status'] ?? '') === $status);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getActiveCampaignsList(): array
    {
        return $this->getCampaignsByStatus('active');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDraftCampaignsList(): array
    {
        return $this->getCampaignsByStatus('draft');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPendingCampaignsList(): array
    {
        return $this->getCampaignsByStatus('pending_approval');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCompletedCampaignsList(): array
    {
        return $this->getCampaignsByStatus('completed');
    }

    // Campaigns by Progress
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCampaignsNearingGoal(float $threshold = 80.0): array
    {
        return array_filter($this->getCampaigns(), fn (array $campaign): bool => ($campaign['progress_percentage'] ?? 0) >= $threshold);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCampaignsNearingDeadline(int $daysThreshold = 7): array
    {
        return array_filter($this->getCampaigns(), fn (array $campaign): bool => ($campaign['remaining_days'] ?? 0) <= $daysThreshold && ($campaign['remaining_days'] ?? 0) > 0);
    }

    // URL Generation
    public function getNextPageUrl(): ?string
    {
        if (! $this->hasMorePages()) {
            return null;
        }

        $filters = $this->getFilters();
        $filters['page'] = $this->getCurrentPage() + 1;

        return $this->buildUrl($filters);
    }

    public function getPreviousPageUrl(): ?string
    {
        if (! $this->hasPreviousPage()) {
            return null;
        }

        $filters = $this->getFilters();
        $filters['page'] = $this->getCurrentPage() - 1;

        return $this->buildUrl($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildUrl(array $filters): string
    {
        $queryString = http_build_query($filters);

        return url('/campaigns?' . $queryString);
    }

    // Performance Optimization Helpers

    /**
     * Get translated title for current locale.
     */
    /**
     * @param  array<string, mixed>  $campaign
     */
    private function getTranslatedTitle(array $campaign): string
    {
        $translations = $campaign['translations'] ?? [];

        return $translations[$this->locale]['title'] ?? $campaign['title'] ?? '';
    }

    /**
     * Get translated description for current locale.
     */
    /**
     * @param  array<string, mixed>  $campaign
     */
    private function getTranslatedDescription(array $campaign): string
    {
        $translations = $campaign['translations'] ?? [];

        return $translations[$this->locale]['description'] ?? $campaign['description'] ?? '';
    }

    /**
     * Calculate progress percentage with precision.
     */
    /**
     * @param  array<string, mixed>  $campaign
     */
    private function calculateProgressPercentage(array $campaign): float
    {
        $target = (float) ($campaign['target_amount'] ?? 0);
        $current = (float) ($campaign['current_amount'] ?? 0);

        if ($target <= 0) {
            return 0.0;
        }

        return round(($current / $target) * 100, 2);
    }

    /**
     * Calculate days remaining until deadline.
     */
    /**
     * @param  array<string, mixed>  $campaign
     */
    private function calculateDaysRemaining(array $campaign): int
    {
        if (! isset($campaign['deadline'])) {
            return -1; // No deadline
        }

        $deadline = new DateTime($campaign['deadline']);
        $now = new DateTime;

        if ($deadline < $now) {
            return 0; // Expired
        }

        return (int) $now->diff($deadline)->days;
    }

    /**
     * Determine if campaign is urgent (less than 7 days remaining or high priority).
     */
    /**
     * @param  array<string, mixed>  $campaign
     */
    private function isUrgent(array $campaign): bool
    {
        $daysRemaining = $this->calculateDaysRemaining($campaign);
        $priority = (int) ($campaign['priority'] ?? 0);

        return ($daysRemaining > 0 && $daysRemaining <= 7) || $priority >= 5;
    }

    /**
     * Format currency amount for display.
     */
    private function formatCurrency(float $amount): string
    {
        return number_format($amount, 2);
    }

    /**
     * JsonSerializable implementation for optimized API responses.
     */
    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toApiResponse();
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pagination' => [
                'current_page' => $this->getCurrentPage(),
                'per_page' => $this->getPerPage(),
                'total' => $this->getTotal(),
                'last_page' => $this->getLastPage(),
                'from' => $this->getFrom(),
                'to' => $this->getTo(),
                'has_more_pages' => $this->hasMorePages(),
                'has_previous_page' => $this->hasPreviousPage(),
                'next_page_url' => $this->getNextPageUrl(),
                'previous_page_url' => $this->getPreviousPageUrl(),
            ],
            'campaigns' => $this->getCampaigns(),
            'filters' => $this->getFilters(),
            'sorting' => [
                'sort_by' => $this->getSortBy(),
                'sort_order' => $this->getSortOrder(),
                'is_sorted_asc' => $this->isSortedAsc(),
                'is_sorted_desc' => $this->isSortedDesc(),
            ],
            'statistics' => $this->getStatistics(),
            'categories' => $this->getCategories(),
            'organizations' => $this->getOrganizations(),
            'meta' => [
                'campaign_count' => $this->getCampaignCount(),
                'is_empty' => $this->isEmpty(),
                'has_active_filters' => $this->hasActiveFilters(),
                'has_featured_campaigns' => $this->hasFeaturedCampaigns(),
                'has_urgent_campaigns' => $this->hasUrgentCampaigns(),
            ],
        ];
    }

    /**
     * Get data optimized for API responses with pre-calculated fields.
     */
    /**
     * @return array<string, mixed>
     */
    public function toApiResponse(): array
    {
        return [
            'data' => $this->getOptimizedCampaigns(),
            'meta' => [
                'pagination' => [
                    'current_page' => $this->getCurrentPage(),
                    'per_page' => $this->getPerPage(),
                    'total' => $this->getTotal(),
                    'last_page' => $this->getLastPage(),
                    'from' => $this->getFrom(),
                    'to' => $this->getTo(),
                ],
                'filters' => $this->getFilters(),
                'statistics' => $this->getStatistics(),
                'locale' => $this->getLocale(),
                'generated_at' => $this->getGeneratedAt(),
                'cache_ttl' => $this->getCacheTtl(),
            ],
            'links' => [
                'next' => $this->getNextPageUrl(),
                'prev' => $this->getPreviousPageUrl(),
            ],
        ];
    }
}
