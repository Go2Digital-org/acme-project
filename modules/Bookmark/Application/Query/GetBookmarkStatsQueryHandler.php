<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Query;

use Modules\Bookmark\Application\ReadModel\BookmarkStatsReadModel;
use Modules\Bookmark\Domain\Repository\BookmarkRepositoryInterface;

/**
 * Handler for getting bookmark statistics.
 */
final readonly class GetBookmarkStatsQueryHandler
{
    public function __construct(
        private BookmarkRepositoryInterface $repository
    ) {}

    public function handle(GetBookmarkStatsQuery $query): BookmarkStatsReadModel
    {
        $scope = $this->determineScope($query);

        $data = match ($query->scope) {
            'overview' => $this->buildOverviewStats($query),
            'detailed' => $this->buildDetailedStats($query),
            'trends' => $this->buildTrendsStats($query),
            default => $this->buildOverviewStats($query),
        };

        return new BookmarkStatsReadModel($scope, $data);
    }

    private function determineScope(GetBookmarkStatsQuery $query): string
    {
        if ($query->userId) {
            return "user:{$query->userId}";
        }

        if ($query->campaignId) {
            return "campaign:{$query->campaignId}";
        }

        if ($query->organizationId) {
            return "organization:{$query->organizationId}";
        }

        return 'global';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildOverviewStats(GetBookmarkStatsQuery $query): array
    {
        if ($query->userId) {
            return $this->buildUserOverviewStats($query->userId);
        }

        if ($query->campaignId) {
            return $this->buildCampaignOverviewStats($query->campaignId);
        }

        if ($query->organizationId) {
            return $this->buildOrganizationOverviewStats($query->organizationId);
        }

        return $this->buildGlobalOverviewStats();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildUserOverviewStats(int $userId): array
    {
        $bookmarks = $this->repository->findByUserId($userId);
        $totalBookmarks = $bookmarks->count();

        return [
            'total_bookmarks' => $totalBookmarks,
            'active_bookmarks' => $bookmarks->filter(fn ($bookmark): bool => $bookmark->campaign && $bookmark->campaign->status === 'active')->count(),
            'unique_bookmarkers' => 1, // Single user
            'bookmarked_campaigns' => $totalBookmarks,
            'average_bookmarks_per_user' => $totalBookmarks,
            'average_bookmarks_per_campaign' => 1.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCampaignOverviewStats(int $campaignId): array
    {
        $bookmarks = $this->repository->findByCampaignId($campaignId);
        $totalBookmarks = $bookmarks->count();

        return [
            'total_bookmarks' => $totalBookmarks,
            'active_bookmarks' => $totalBookmarks, // All bookmarks for this campaign are "active"
            'unique_bookmarkers' => $bookmarks->unique('user_id')->count(),
            'bookmarked_campaigns' => 1, // Single campaign
            'average_bookmarks_per_user' => $totalBookmarks > 0 ? 1.0 : 0.0,
            'average_bookmarks_per_campaign' => $totalBookmarks,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildOrganizationOverviewStats(int $organizationId): array
    {
        $stats = $this->repository->getOrganizationBookmarkStats($organizationId);

        return [
            'total_bookmarks' => $stats['total_bookmarks'] ?? 0,
            'active_bookmarks' => $stats['active_bookmarks'] ?? 0,
            'unique_bookmarkers' => $stats['unique_bookmarkers'] ?? 0,
            'bookmarked_campaigns' => $stats['bookmarked_campaigns'] ?? 0,
            'average_bookmarks_per_user' => $stats['average_bookmarks_per_user'] ?? 0,
            'average_bookmarks_per_campaign' => $stats['average_bookmarks_per_campaign'] ?? 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildGlobalOverviewStats(): array
    {
        // This would require more complex queries across all bookmarks
        // For now, return basic structure
        return [
            'total_bookmarks' => 0,
            'active_bookmarks' => 0,
            'unique_bookmarkers' => 0,
            'bookmarked_campaigns' => 0,
            'average_bookmarks_per_user' => 0,
            'average_bookmarks_per_campaign' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDetailedStats(GetBookmarkStatsQuery $query): array
    {
        $overview = $this->buildOverviewStats($query);

        // Add detailed metrics
        return array_merge($overview, [
            'growth_stats' => [],
            'conversion_metrics' => [],
            'engagement_metrics' => [],
            'time_insights' => [],
            'comparative_stats' => [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTrendsStats(GetBookmarkStatsQuery $query): array
    {
        $overview = $this->buildOverviewStats($query);

        // Add trend data
        return array_merge($overview, [
            'daily_trends' => [],
            'weekly_trends' => [],
            'monthly_trends' => [],
            'growth_stats' => [],
        ]);
    }
}
