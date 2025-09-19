<?php

declare(strict_types=1);

namespace Modules\Bookmark\Infrastructure\Laravel\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Modules\Bookmark\Domain\Model\Bookmark;
use Modules\Bookmark\Domain\Repository\BookmarkRepositoryInterface;
use Modules\Campaign\Domain\Model\Campaign;

/**
 * Eloquent implementation of the Bookmark repository.
 */
final readonly class BookmarkEloquentRepository implements BookmarkRepositoryInterface
{
    public function __construct(
        private Bookmark $model
    ) {}

    /** @param array<string, mixed> $data */
    public function create(array $data): Bookmark
    {
        return $this->model->create($data);
    }

    public function findById(int $id): ?Bookmark
    {
        return $this->model->find($id);
    }

    public function findByUserAndCampaign(int $userId, int $campaignId): ?Bookmark
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('campaign_id', $campaignId)
            ->first();
    }

    public function findByUserId(int $userId): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->with(['campaign', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findByCampaignId(int $campaignId): Collection
    {
        return $this->model
            ->where('campaign_id', $campaignId)
            ->with(['user', 'campaign'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUserBookmarkedCampaigns(int $userId): Collection
    {
        return Campaign::query()
            ->whereHas('bookmarks', function ($query) use ($userId): void {
                $query->where('user_id', $userId);
            })
            ->with(['organization', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUserBookmarksWithDetails(int $userId): SupportCollection
    {
        $bookmarks = $this->model
            ->where('user_id', $userId)
            ->with([
                'campaign' => function ($query): void {
                    $query->with(['organization', 'creator']);
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($bookmark): bool {
                return $bookmark->campaign !== null; // Filter out null campaigns (deleted)
            });

        $mapped = $bookmarks->map(fn ($bookmark): array => [
            'id' => $bookmark->id,
            'user_id' => $bookmark->user_id,
            'campaign_id' => $bookmark->campaign_id,
            'created_at' => $bookmark->created_at?->toISOString() ?? null,
            'campaign' => $bookmark->campaign->toArray(),
        ]);

        /** @var SupportCollection<int, array<string, mixed>> $result */
        $result = $mapped;

        return $result;
    }

    public function exists(int $userId, int $campaignId): bool
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('campaign_id', $campaignId)
            ->exists();
    }

    public function countByCampaign(int $campaignId): int
    {
        return $this->model
            ->where('campaign_id', $campaignId)
            ->count();
    }

    public function countByUser(int $userId): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->count();
    }

    public function deleteById(int $id): bool
    {
        return $this->model->where('id', $id)->delete() > 0;
    }

    public function deleteByUserAndCampaign(int $userId, int $campaignId): bool
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('campaign_id', $campaignId)
            ->delete() > 0;
    }

    public function deleteByUserId(int $userId): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->delete();
    }

    public function deleteByCampaignId(int $campaignId): int
    {
        return $this->model
            ->where('campaign_id', $campaignId)
            ->delete();
    }

    public function getRecentByUser(int $userId, int $limit = 10): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->with(['campaign'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getMostBookmarkedCampaigns(int $limit = 10): Collection
    {
        return Campaign::query()
            ->select('campaigns.*')
            ->join('bookmarks', 'campaigns.id', '=', 'bookmarks.campaign_id')
            ->groupBy('campaigns.id')
            ->orderByRaw('COUNT(bookmarks.id) DESC')
            ->limit($limit)
            ->with(['organization', 'creator'])
            ->get();
    }

    public function findByOrganizationId(int $organizationId): Collection
    {
        return $this->model
            ->join('campaigns', 'bookmarks.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.organization_id', $organizationId)
            ->with(['user', 'campaign'])
            ->orderBy('bookmarks.created_at', 'desc')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrganizationBookmarkStats(int $organizationId): array
    {
        $stats = DB::table('bookmarks')
            ->join('campaigns', 'bookmarks.campaign_id', '=', 'campaigns.id')
            ->join('users', 'bookmarks.user_id', '=', 'users.id')
            ->where('campaigns.organization_id', $organizationId)
            ->selectRaw('
                COUNT(bookmarks.id) as total_bookmarks,
                COUNT(DISTINCT bookmarks.user_id) as unique_bookmarkers,
                COUNT(DISTINCT bookmarks.campaign_id) as bookmarked_campaigns,
                COUNT(CASE WHEN campaigns.status = "active" THEN 1 END) as active_bookmarks
            ')
            ->first();

        if (! $stats) {
            return [
                'total_bookmarks' => 0,
                'unique_bookmarkers' => 0,
                'bookmarked_campaigns' => 0,
                'active_bookmarks' => 0,
                'average_bookmarks_per_user' => 0,
                'average_bookmarks_per_campaign' => 0,
            ];
        }

        $totalBookmarks = (int) ($stats->total_bookmarks ?? 0);
        $uniqueBookmarkers = (int) ($stats->unique_bookmarkers ?? 0);
        $bookmarkedCampaigns = (int) ($stats->bookmarked_campaigns ?? 0);

        return [
            'total_bookmarks' => $totalBookmarks,
            'unique_bookmarkers' => $uniqueBookmarkers,
            'bookmarked_campaigns' => $bookmarkedCampaigns,
            'active_bookmarks' => (int) ($stats->active_bookmarks ?? 0),
            'average_bookmarks_per_user' => $uniqueBookmarkers > 0 ? round($totalBookmarks / $uniqueBookmarkers, 2) : 0,
            'average_bookmarks_per_campaign' => $bookmarkedCampaigns > 0 ? round($totalBookmarks / $bookmarkedCampaigns, 2) : 0,
        ];
    }
}
