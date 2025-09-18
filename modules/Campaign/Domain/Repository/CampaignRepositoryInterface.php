<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Repository;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;

interface CampaignRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Campaign;

    public function findById(int $id): ?Campaign;

    public function findByIdWithTrashed(int $id): ?Campaign;

    /**
     * @return array<Campaign>
     */
    public function findActiveByOrganization(int $organizationId): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateById(int $id, array $data): bool;

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Campaign>
     */
    public function paginate(
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'id',
        string $sortOrder = 'desc',
    ): LengthAwarePaginator;

    public function delete(int $id): bool;

    /**
     * Include soft-deleted campaigns in query results.
     */
    public function withTrashed(): self;

    /**
     * Get only soft-deleted campaigns.
     */
    public function onlyTrashed(): self;

    /**
     * Permanently delete a campaign (admin only).
     */
    public function forceDelete(int $id): bool;

    /**
     * Restore a soft-deleted campaign.
     */
    public function restore(int $id): bool;

    /**
     * @return array<Campaign>
     */
    public function findActiveCampaigns(): array;

    /**
     * @return array<Campaign>
     */
    public function findExpiredCampaigns(): array;

    /**
     * Search campaigns with translation support.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Campaign>
     */
    public function searchWithTranslations(
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'id',
        string $sortOrder = 'desc',
    ): LengthAwarePaginator;

    /**
     * Find campaigns with complete translations for specific locale.
     *
     * @return array<Campaign>
     */
    public function findWithCompleteTranslations(string $locale): array;

    /**
     * Find campaigns missing translations for specific locale.
     *
     * @return array<Campaign>
     */
    public function findMissingTranslations(string $locale): array;

    /**
     * Get campaign by ID with specific locale context.
     */
    public function findByIdWithLocale(int $id, string $locale): ?Campaign;

    /**
     * Search campaigns in multiple translations.
     *
     * @param  array<string>  $locales
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Campaign>
     */
    public function searchInTranslations(
        string $searchTerm,
        array $locales = ['en', 'nl', 'fr'],
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'id',
        string $sortOrder = 'desc',
    ): LengthAwarePaginator;

    /**
     * Find campaigns created by a specific user.
     *
     * @return array<Campaign>
     */
    public function findByUserId(int $userId): array;

    /**
     * Get paginated campaigns for a specific user with filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Campaign>
     */
    public function paginateUserCampaigns(
        int $userId,
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
    ): LengthAwarePaginator;

    /**
     * Find campaigns by status with optional limit.
     *
     * @param  CampaignStatus  $status  Campaign status filter
     * @param  int|null  $limit  Maximum number of campaigns to return
     * @return array<Campaign>
     */
    public function findByStatus(CampaignStatus $status, ?int $limit = null): array;

    /**
     * Find campaigns by user and status.
     *
     * @return array<Campaign>
     */
    public function findByUserAndStatus(int $userId, CampaignStatus $status): array;

    /**
     * Find campaigns by employee and status.
     *
     * @return array<Campaign>
     */
    public function findByEmployeeAndStatus(int $userId, CampaignStatus $status): array;

    /**
     * Get featured campaigns for homepage display.
     *
     * @param  int  $limit  Number of featured campaigns to return
     * @return array<Campaign>
     */
    public function getFeaturedCampaigns(int $limit = 3): array;

    /**
     * Find all campaigns.
     *
     * @return array<Campaign>
     */
    public function findAll(): array;

    /**
     * Get total count of campaigns.
     */
    public function getTotalCampaignsCount(): int;

    /**
     * Get total amount raised across all campaigns.
     */
    public function getTotalRaisedAmount(): float;

    /**
     * Get count of active campaigns.
     */
    public function getActiveCampaignsCount(): int;

    /**
     * Get total amount raised from active campaigns only.
     */
    public function getActiveRaisedAmount(): float;

    /**
     * Get count of campaigns by status.
     *
     * @param  array<string, mixed>  $filters
     */
    public function countByStatus(CampaignStatus $status, array $filters = []): int;

    /**
     * Get total count of all campaigns.
     */
    public function count(): int;

    /**
     * Find campaigns by multiple IDs.
     *
     * @param  array<int>  $ids
     * @return array<Campaign>
     */
    public function findByIds(array $ids): array;

    /**
     * Find campaigns by organization ID.
     *
     * @return array<Campaign>
     */
    public function findByOrganizationId(int $organizationId): array;

    /**
     * Update campaign status.
     */
    public function updateStatus(int $campaignId, CampaignStatus $status): bool;

    /**
     * Find campaigns for indexing with offset and limit.
     *
     * @return array<Campaign>
     */
    public function findForIndexing(int $offset, int $limit): array;

    /**
     * Get paginated campaigns with filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Campaign>
     */
    public function getPaginatedFiltered(
        array $filters = [],
        int $perPage = 15,
        int $page = 1
    ): LengthAwarePaginator;

    /**
     * Count campaigns with filtering.
     *
     * @param  array<string, mixed>  $filters
     */
    public function countFiltered(array $filters = []): int;

    /**
     * Get total amount raised with filtering.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getTotalAmountRaised(array $filters = []): float;

    /**
     * Get average campaign progress percentage.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getAverageProgress(array $filters = []): float;
}
