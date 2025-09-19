<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Read model for overall bookmark statistics and analytics.
 */
class BookmarkStatsReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        string $scope, // 'global', 'organization', 'user'
        array $data,
        ?string $version = null
    ) {
        parent::__construct($scope, $data, $version);
        $this->setCacheTtl(3600); // 1 hour for statistics
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'bookmark_stats',
            'bookmarks',
            'statistics',
        ]);
    }

    public function getScope(): string
    {
        return (string) $this->id;
    }

    /**
     * Get total bookmark count.
     */
    public function getTotalBookmarks(): int
    {
        return (int) $this->get('total_bookmarks', 0);
    }

    /**
     * Get active bookmarks count.
     */
    public function getActiveBookmarks(): int
    {
        return (int) $this->get('active_bookmarks', 0);
    }

    /**
     * Get unique users who have bookmarks.
     */
    public function getUniqueBookmarkers(): int
    {
        return (int) $this->get('unique_bookmarkers', 0);
    }

    /**
     * Get unique campaigns that are bookmarked.
     */
    public function getBookmarkedCampaigns(): int
    {
        return (int) $this->get('bookmarked_campaigns', 0);
    }

    /**
     * Get average bookmarks per user.
     */
    public function getAverageBookmarksPerUser(): float
    {
        return (float) $this->get('average_bookmarks_per_user', 0);
    }

    /**
     * Get average bookmarks per campaign.
     */
    public function getAverageBookmarksPerCampaign(): float
    {
        return (float) $this->get('average_bookmarks_per_campaign', 0);
    }

    /**
     * Get bookmark growth statistics.
     *
     * @return array<string, mixed>
     */
    public function getGrowthStats(): array
    {
        return $this->get('growth_stats', []);
    }

    /**
     * Get daily bookmark trend data.
     *
     * @return array<string, int>
     */
    public function getDailyTrends(): array
    {
        return $this->get('daily_trends', []);
    }

    /**
     * Get weekly bookmark trend data.
     *
     * @return array<string, int>
     */
    public function getWeeklyTrends(): array
    {
        return $this->get('weekly_trends', []);
    }

    /**
     * Get monthly bookmark trend data.
     *
     * @return array<string, int>
     */
    public function getMonthlyTrends(): array
    {
        return $this->get('monthly_trends', []);
    }

    /**
     * Get top bookmarked campaigns.
     *
     * @return array<string, mixed>
     */
    public function getTopBookmarkedCampaigns(): array
    {
        return $this->get('top_campaigns', []);
    }

    /**
     * Get most active bookmarkers.
     *
     * @return array<string, mixed>
     */
    public function getMostActiveBookmarkers(): array
    {
        return $this->get('most_active_bookmarkers', []);
    }

    /**
     * Get bookmark statistics by organization.
     *
     * @return array<string, mixed>
     */
    public function getStatsByOrganization(): array
    {
        return $this->get('stats_by_organization', []);
    }

    /**
     * Get bookmark statistics by category.
     *
     * @return array<string, mixed>
     */
    public function getStatsByCategory(): array
    {
        return $this->get('stats_by_category', []);
    }

    /**
     * Get bookmark conversion metrics.
     *
     * @return array<string, mixed>
     */
    public function getConversionMetrics(): array
    {
        return $this->get('conversion_metrics', []);
    }

    /**
     * Get engagement metrics.
     *
     * @return array<string, mixed>
     */
    public function getEngagementMetrics(): array
    {
        return $this->get('engagement_metrics', []);
    }

    /**
     * Get time-based insights.
     *
     * @return array<string, mixed>
     */
    public function getTimeInsights(): array
    {
        return $this->get('time_insights', []);
    }

    /**
     * Get comparative statistics (vs previous period).
     *
     * @return array<string, mixed>
     */
    public function getComparativeStats(): array
    {
        return $this->get('comparative_stats', []);
    }

    /**
     * Get bookmark-to-donation conversion rate.
     */
    public function getBookmarkToDonationRate(): float
    {
        return (float) $this->get('bookmark_donation_rate', 0);
    }

    /**
     * Get average time from bookmark to donation.
     */
    public function getAverageTimeToConversion(): ?int
    {
        return $this->get('average_time_to_conversion'); // in hours
    }
}
