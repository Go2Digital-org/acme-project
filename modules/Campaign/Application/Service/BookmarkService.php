<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Service;

use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Modules\Campaign\Domain\Model\Bookmark;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Shared\Infrastructure\Laravel\Traits\HasTenantAwareCache;

final class BookmarkService
{
    use HasTenantAwareCache;

    /**
     * Toggle a bookmark for a user and campaign.
     * Returns true if bookmark was created, false if it was removed.
     */
    public function toggle(int $userId, int $campaignId): bool
    {
        $existingBookmark = Bookmark::query()
            ->where('user_id', $userId)
            ->where('campaign_id', $campaignId)
            ->first();

        if ($existingBookmark) {
            $existingBookmark->delete();

            // Clear the user's bookmarked campaigns cache (tenant-aware)
            $this->clearUserBookmarksCache($userId);

            return false;
        }

        Bookmark::create([
            'user_id' => $userId,
            'campaign_id' => $campaignId,
        ]);

        // Clear the user's bookmarked campaigns cache when adding
        $this->clearUserBookmarksCache($userId);

        return true;
    }

    /**
     * Get all bookmarked campaigns for a user.
     *
     * @return Collection<int, Campaign>
     */
    public function getUserBookmarks(int $userId): Collection
    {
        return Campaign::query()
            ->whereHas('bookmarks', function ($query) use ($userId): void {
                $query->where('user_id', $userId);
            })
            ->with(['organization', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Check if a campaign is bookmarked by a user.
     */
    public function isBookmarked(int $userId, int $campaignId): bool
    {
        return DB::table('bookmarks')->where('user_id', $userId)
            ->where('campaign_id', $campaignId)
            ->exists();
    }

    /**
     * Get bookmark count for a campaign.
     */
    public function getBookmarkCount(int $campaignId): int
    {
        return DB::table('bookmarks')
            ->where('campaign_id', $campaignId)
            ->count();
    }

    /**
     * Remove a specific bookmark.
     */
    public function removeBookmark(int $userId, int $campaignId): bool
    {
        $bookmark = Bookmark::query()
            ->where('user_id', $userId)
            ->where('campaign_id', $campaignId)
            ->first();

        if (! $bookmark) {
            return false;
        }

        $bookmark->delete();

        // Clear the user's bookmarked campaigns cache (tenant-aware)
        $this->clearUserBookmarksCache($userId);

        return true;
    }

    /**
     * Get all bookmarks for a user with campaign details.
     *
     * @return SupportCollection<int, Campaign>
     */
    public function getUserBookmarksWithDetails(int $userId): SupportCollection
    {
        return Bookmark::query()
            ->where('user_id', $userId)
            ->with([
                'campaign' => function ($query): void {
                    $query->with(['organization', 'creator']);
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->pluck('campaign')
            ->filter() // Filter out null values (deleted campaigns)
            ->values();
    }

    /**
     * Clear the user's bookmarked campaigns cache (tenant-aware).
     */
    private function clearUserBookmarksCache(int $userId): void
    {
        // Clear the tenant-aware cache key for user bookmarks
        $cacheKey = self::formatCacheKey("user:{$userId}:bookmarked_campaigns");
        Cache::forget($cacheKey);
    }
}
