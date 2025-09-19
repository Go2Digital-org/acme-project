<?php

declare(strict_types=1);

namespace Modules\Bookmark\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Modules\Bookmark\Domain\Model\Bookmark;
use Modules\Campaign\Domain\Model\Campaign;

/**
 * Interface for Bookmark Repository operations.
 */
interface BookmarkRepositoryInterface
{
    /**
     * Create a new bookmark.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Bookmark;

    /**
     * Find a bookmark by ID.
     */
    public function findById(int $id): ?Bookmark;

    /**
     * Find bookmark by user and campaign.
     */
    public function findByUserAndCampaign(int $userId, int $campaignId): ?Bookmark;

    /**
     * Get all bookmarks for a specific user.
     *
     * @return Collection<int, Bookmark>
     */
    public function findByUserId(int $userId): Collection;

    /**
     * Get all bookmarks for a specific campaign.
     *
     * @return Collection<int, Bookmark>
     */
    public function findByCampaignId(int $campaignId): Collection;

    /**
     * Get bookmarks with campaign details for a user.
     *
     * @return Collection<int, Campaign>
     */
    public function getUserBookmarkedCampaigns(int $userId): Collection;

    /**
     * Get user bookmarks with detailed information.
     *
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function getUserBookmarksWithDetails(int $userId): SupportCollection;

    /**
     * Check if user has bookmarked a campaign.
     */
    public function exists(int $userId, int $campaignId): bool;

    /**
     * Get bookmark count for a campaign.
     */
    public function countByCampaign(int $campaignId): int;

    /**
     * Get total bookmarks count for a user.
     */
    public function countByUser(int $userId): int;

    /**
     * Delete a bookmark by ID.
     */
    public function deleteById(int $id): bool;

    /**
     * Delete bookmark by user and campaign.
     */
    public function deleteByUserAndCampaign(int $userId, int $campaignId): bool;

    /**
     * Delete all bookmarks for a user.
     */
    public function deleteByUserId(int $userId): int;

    /**
     * Delete all bookmarks for a campaign.
     */
    public function deleteByCampaignId(int $campaignId): int;

    /**
     * Get recent bookmarks for a user.
     *
     * @return Collection<int, Bookmark>
     */
    public function getRecentByUser(int $userId, int $limit = 10): Collection;

    /**
     * Get popular campaigns (most bookmarked).
     *
     * @return Collection<int, Campaign>
     */
    public function getMostBookmarkedCampaigns(int $limit = 10): Collection;

    /**
     * Get bookmark statistics for organization.
     *
     * @return array<string, mixed>
     */
    public function getOrganizationBookmarkStats(int $organizationId): array;

    /**
     * Get all bookmarks for campaigns in an organization.
     *
     * @return Collection<int, Bookmark>
     */
    public function findByOrganizationId(int $organizationId): Collection;
}
