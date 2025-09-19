<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Query;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Modules\Bookmark\Application\ReadModel\UserBookmarksReadModel;
use Modules\Bookmark\Domain\Model\Bookmark;
use Modules\Bookmark\Domain\Repository\BookmarkRepositoryInterface;

/**
 * Handler for getting user bookmarks.
 */
final readonly class GetUserBookmarksQueryHandler
{
    public function __construct(
        private BookmarkRepositoryInterface $repository
    ) {}

    /**
     * @return Collection<int, Bookmark>|UserBookmarksReadModel
     */
    public function handle(GetUserBookmarksQuery $query): Collection|UserBookmarksReadModel
    {
        if ($query->withDetails) {
            return $this->buildUserBookmarksReadModel($query);
        }

        // Return simple collection of bookmarks
        $bookmarks = $this->repository->findByUserId($query->userId);

        if ($query->limit) {
            return $bookmarks->take($query->limit);
        }

        return $bookmarks;
    }

    private function buildUserBookmarksReadModel(GetUserBookmarksQuery $query): UserBookmarksReadModel
    {
        // Get user's bookmarked campaigns with details
        $bookmarkedCampaigns = $this->repository->getUserBookmarkedCampaigns($query->userId);
        $bookmarksWithDetails = $this->repository->getUserBookmarksWithDetails($query->userId);

        // Calculate statistics
        $totalBookmarks = $bookmarksWithDetails->count();
        $activeBookmarks = $bookmarkedCampaigns->where('status', 'active')->count();
        $completedBookmarks = $bookmarkedCampaigns->where('status', 'completed')->count();

        // Recent bookmarks (last 30 days)
        $recentBookmarks = $bookmarksWithDetails->where('created_at', '>=', now()->subDays(30))->count();

        // Group by organization
        $bookmarksByOrg = $bookmarkedCampaigns->groupBy('organization.id')->map(function ($campaigns, $orgId): array {
            $orgData = $campaigns->first()->organization ?? null;

            return [
                'organization_id' => $orgId,
                'organization_name' => $orgData->name ?? 'Unknown',
                'count' => $campaigns->count(),
                'campaigns' => $campaigns->take(5)->toArray(),
            ];
        })->values();

        // Calculate metrics
        $totalAmountRaised = $bookmarkedCampaigns->sum('current_amount');
        $averageSuccessRate = $bookmarkedCampaigns->where('status', 'completed')->count() > 0
            ? ($bookmarkedCampaigns->where('status', 'completed')->count() / $totalBookmarks) * 100
            : 0;

        $data = [
            'total_bookmarks' => $totalBookmarks,
            'active_bookmarks' => $activeBookmarks,
            'completed_bookmarks' => $completedBookmarks,
            'recent_bookmarks' => $recentBookmarks,
            'bookmarked_campaigns' => $bookmarkedCampaigns->toArray(),
            'recent_campaigns' => $bookmarkedCampaigns->take(10)->toArray(),
            'bookmarks_by_organization' => $bookmarksByOrg->toArray(),
            'bookmarks_by_category' => [], // TODO: implement when categories are available
            'average_success_rate' => $averageSuccessRate,
            'total_amount_raised' => $totalAmountRaised,
            'last_bookmark_at' => $this->getLastBookmarkDate($bookmarksWithDetails),
            'bookmarks_by_status' => [
                'active' => $bookmarkedCampaigns->where('status', 'active')->toArray(),
                'completed' => $bookmarkedCampaigns->where('status', 'completed')->toArray(),
                'cancelled' => $bookmarkedCampaigns->where('status', 'cancelled')->toArray(),
            ],
        ];

        return new UserBookmarksReadModel($query->userId, $data);
    }

    /**
     * @param  SupportCollection<int, array<string, mixed>>  $bookmarksWithDetails
     */
    private function getLastBookmarkDate(SupportCollection $bookmarksWithDetails): ?string
    {
        $firstBookmark = $bookmarksWithDetails->first();
        if (is_array($firstBookmark) && isset($firstBookmark['created_at'])) {
            return (string) $firstBookmark['created_at'];
        }

        return null;
    }
}
