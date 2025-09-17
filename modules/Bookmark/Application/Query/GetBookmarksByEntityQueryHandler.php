<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Query;

use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Modules\Bookmark\Application\ReadModel\CampaignBookmarksReadModel;
use Modules\Bookmark\Domain\Model\Bookmark;
use Modules\Bookmark\Domain\Repository\BookmarkRepositoryInterface;

/**
 * Handler for getting bookmarks by entity (campaign, organization).
 */
final readonly class GetBookmarksByEntityQueryHandler
{
    public function __construct(
        private BookmarkRepositoryInterface $repository
    ) {}

    /**
     * @return Collection<int, Bookmark>|CampaignBookmarksReadModel
     */
    public function handle(GetBookmarksByEntityQuery $query): Collection|CampaignBookmarksReadModel
    {
        return match ($query->entityType) {
            'campaign' => $this->handleCampaignEntity($query),
            'organization' => $this->handleOrganizationEntity($query),
            default => throw new InvalidArgumentException("Unsupported entity type: {$query->entityType}"),
        };
    }

    /**
     * @return Collection<int, Bookmark>|CampaignBookmarksReadModel
     */
    private function handleCampaignEntity(GetBookmarksByEntityQuery $query): Collection|CampaignBookmarksReadModel
    {
        if ($query->withUserDetails) {
            return $this->buildCampaignBookmarksReadModel($query->entityId);
        }

        $bookmarks = $this->repository->findByCampaignId($query->entityId);

        if ($query->limit) {
            return $bookmarks->take($query->limit);
        }

        return $bookmarks;
    }

    /**
     * @return Collection<int, Bookmark>
     */
    private function handleOrganizationEntity(GetBookmarksByEntityQuery $query): Collection
    {
        // Get organization bookmark statistics
        $this->repository->getOrganizationBookmarkStats($query->entityId);

        // Get all bookmarks for the organization's campaigns
        return $this->repository->findByOrganizationId($query->entityId);
    }

    private function buildCampaignBookmarksReadModel(int $campaignId): CampaignBookmarksReadModel
    {
        $bookmarks = $this->repository->findByCampaignId($campaignId);
        $totalBookmarks = $bookmarks->count();

        // Calculate recent bookmarks (last 7 days)
        $recentBookmarks = $bookmarks->where('created_at', '>=', now()->subDays(7))->count();

        // Get users who bookmarked (with details if needed)
        $bookmarkUsers = $bookmarks->load('user')->map(fn ($bookmark) => [
            'user_id' => $bookmark->user->id,
            'name' => $bookmark->user->name,
            'organization_id' => $bookmark->user->organization_id,
            'bookmarked_at' => $bookmark->created_at?->toISOString(),
        ])->toArray();

        // Group by organization
        $bookmarksByOrg = collect($bookmarkUsers)->groupBy('organization_id')->map(fn ($users, $orgId) => [
            'organization_id' => $orgId,
            'count' => $users->count(),
            'users' => $users->toArray(),
        ])->values()->toArray();

        // Calculate growth rate (simplified)
        $previousPeriodCount = $bookmarks->where('created_at', '<', now()->subDays(7))->count();
        $growthRate = $previousPeriodCount > 0 ? (($recentBookmarks - $previousPeriodCount) / $previousPeriodCount) * 100 : 0;

        // Daily stats for last 30 days
        $dailyStats = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = $bookmarks->where('created_at', '>=', now()->subDays($i)->startOfDay())
                ->where('created_at', '<=', now()->subDays($i)->endOfDay())
                ->count();
            $dailyStats[$date] = $count;
        }

        $data = [
            'total_bookmarks' => $totalBookmarks,
            'recent_bookmarks' => $recentBookmarks,
            'bookmark_growth_rate' => $growthRate,
            'daily_stats' => $dailyStats,
            'bookmark_users' => $bookmarkUsers,
            'bookmarks_by_organization' => $bookmarksByOrg,
            'top_organizations' => array_slice($bookmarksByOrg, 0, 5),
            'first_bookmark_at' => $bookmarks->last()?->created_at?->toISOString(),
            'last_bookmark_at' => $bookmarks->first()?->created_at?->toISOString(),
            'average_bookmarks_per_day' => $totalBookmarks > 0 ? $totalBookmarks / 30 : 0,
            'is_trending' => $recentBookmarks > ($totalBookmarks * 0.3), // 30% of bookmarks in last 7 days
        ];

        return new CampaignBookmarksReadModel($campaignId, $data);
    }
}
