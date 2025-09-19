<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Read model for campaign bookmark statistics and user lists.
 */
class CampaignBookmarksReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        int $campaignId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($campaignId, $data, $version);
        $this->setCacheTtl(900); // 15 minutes for campaign bookmarks
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'campaign_bookmarks',
            'campaign:' . $this->id,
            'bookmarks',
        ]);
    }

    public function getCampaignId(): int
    {
        return (int) $this->id;
    }

    /**
     * Get total bookmark count for this campaign.
     */
    public function getTotalBookmarks(): int
    {
        return (int) $this->get('total_bookmarks', 0);
    }

    /**
     * Get recent bookmarks count (last 7 days).
     */
    public function getRecentBookmarks(): int
    {
        return (int) $this->get('recent_bookmarks', 0);
    }

    /**
     * Get bookmark growth rate (percentage change from previous period).
     */
    public function getBookmarkGrowthRate(): float
    {
        return (float) $this->get('bookmark_growth_rate', 0);
    }

    /**
     * Get daily bookmark statistics.
     *
     * @return array<string, int>
     */
    public function getDailyBookmarkStats(): array
    {
        return $this->get('daily_stats', []);
    }

    /**
     * Get users who bookmarked this campaign.
     *
     * @return array<string, mixed>
     */
    public function getBookmarkUsers(): array
    {
        return $this->get('bookmark_users', []);
    }

    /**
     * Get bookmark statistics by organization.
     *
     * @return array<string, mixed>
     */
    public function getBookmarksByOrganization(): array
    {
        return $this->get('bookmarks_by_organization', []);
    }

    /**
     * Get most active bookmarking organizations.
     *
     * @return array<string, mixed>
     */
    public function getTopBookmarkingOrganizations(): array
    {
        return $this->get('top_organizations', []);
    }

    /**
     * Get first bookmark timestamp.
     */
    public function getFirstBookmarkAt(): ?string
    {
        return $this->get('first_bookmark_at');
    }

    /**
     * Get most recent bookmark timestamp.
     */
    public function getLastBookmarkAt(): ?string
    {
        return $this->get('last_bookmark_at');
    }

    /**
     * Get bookmark ranking compared to other campaigns.
     */
    public function getBookmarkRanking(): ?int
    {
        return $this->get('bookmark_ranking');
    }

    /**
     * Get bookmark percentile (what percentage of campaigns have fewer bookmarks).
     */
    public function getBookmarkPercentile(): float
    {
        return (float) $this->get('bookmark_percentile', 0);
    }

    /**
     * Get average bookmarks per day since campaign start.
     */
    public function getAverageBookmarksPerDay(): float
    {
        return (float) $this->get('average_bookmarks_per_day', 0);
    }

    /**
     * Check if campaign is trending (above average recent bookmark activity).
     */
    public function isTrending(): bool
    {
        return (bool) $this->get('is_trending', false);
    }

    /**
     * Get bookmark conversion rate (bookmarks to donations ratio).
     */
    public function getBookmarkConversionRate(): float
    {
        return (float) $this->get('bookmark_conversion_rate', 0);
    }
}
