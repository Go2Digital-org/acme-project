<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Read model for user bookmarks with campaign details and statistics.
 */
class UserBookmarksReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        int $userId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($userId, $data, $version);
        $this->setCacheTtl(1800); // 30 minutes for user bookmarks
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'user_bookmarks',
            'user:' . $this->id,
            'bookmarks',
        ]);
    }

    public function getUserId(): int
    {
        return (int) $this->id;
    }

    /**
     * Get total number of bookmarks for this user.
     */
    public function getTotalBookmarks(): int
    {
        return (int) $this->get('total_bookmarks', 0);
    }

    /**
     * Get active bookmarks count (campaigns still running).
     */
    public function getActiveBookmarks(): int
    {
        return (int) $this->get('active_bookmarks', 0);
    }

    /**
     * Get completed bookmarks count (campaigns finished).
     */
    public function getCompletedBookmarks(): int
    {
        return (int) $this->get('completed_bookmarks', 0);
    }

    /**
     * Get recent bookmarks (last 30 days).
     */
    public function getRecentBookmarks(): int
    {
        return (int) $this->get('recent_bookmarks', 0);
    }

    /**
     * Get all bookmarked campaigns with details.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBookmarkedCampaigns(): array
    {
        return $this->get('bookmarked_campaigns', []);
    }

    /**
     * Get recent bookmarked campaigns.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecentCampaigns(): array
    {
        return $this->get('recent_campaigns', []);
    }

    /**
     * Get bookmark statistics by organization.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBookmarksByOrganization(): array
    {
        return $this->get('bookmarks_by_organization', []);
    }

    /**
     * Get bookmark statistics by category.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBookmarksByCategory(): array
    {
        return $this->get('bookmarks_by_category', []);
    }

    /**
     * Get average campaign success rate for bookmarked campaigns.
     */
    public function getAverageSuccessRate(): float
    {
        return (float) $this->get('average_success_rate', 0);
    }

    /**
     * Get total amount raised by bookmarked campaigns.
     */
    public function getTotalAmountRaised(): float
    {
        return (float) $this->get('total_amount_raised', 0);
    }

    /**
     * Get most recent bookmark timestamp.
     */
    public function getLastBookmarkAt(): ?string
    {
        return $this->get('last_bookmark_at');
    }

    /**
     * Check if user has bookmarked a specific campaign.
     */
    public function hasBookmarkedCampaign(int $campaignId): bool
    {
        $campaigns = $this->getBookmarkedCampaigns();
        foreach ($campaigns as $campaign) {
            if ($campaign['id'] === $campaignId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get bookmark for specific campaign.
     *
     * @return array<string, mixed>|null
     */
    public function getBookmarkForCampaign(int $campaignId): ?array
    {
        $campaigns = $this->getBookmarkedCampaigns();
        foreach ($campaigns as $campaign) {
            if ($campaign['id'] === $campaignId) {
                return $campaign;
            }
        }

        return null;
    }

    /**
     * Get bookmarks grouped by status.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getBookmarksByStatus(): array
    {
        return $this->get('bookmarks_by_status', [
            'active' => [],
            'completed' => [],
            'cancelled' => [],
        ]);
    }
}
