<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Service;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Modules\Bookmark\Application\Command\CreateBookmarkCommand;
use Modules\Bookmark\Application\Command\CreateBookmarkCommandHandler;
use Modules\Bookmark\Application\Command\OrganizeBookmarksCommand;
use Modules\Bookmark\Application\Command\OrganizeBookmarksCommandHandler;
use Modules\Bookmark\Application\Command\RemoveBookmarkCommand;
use Modules\Bookmark\Application\Command\RemoveBookmarkCommandHandler;
use Modules\Bookmark\Application\Command\ToggleBookmarkCommand;
use Modules\Bookmark\Application\Command\ToggleBookmarkCommandHandler;
use Modules\Bookmark\Application\Query\CheckBookmarkStatusQuery;
use Modules\Bookmark\Application\Query\CheckBookmarkStatusQueryHandler;
use Modules\Bookmark\Domain\Model\Bookmark;
use Modules\Bookmark\Domain\Repository\BookmarkRepositoryInterface;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Shared\Infrastructure\Laravel\Traits\HasTenantAwareCache;

/**
 * Application service for bookmark operations using CQRS pattern.
 */
final readonly class BookmarkService
{
    use HasTenantAwareCache;

    public function __construct(
        private BookmarkRepositoryInterface $repository,
        private CreateBookmarkCommandHandler $createHandler,
        private RemoveBookmarkCommandHandler $removeHandler,
        private ToggleBookmarkCommandHandler $toggleHandler,
        private OrganizeBookmarksCommandHandler $organizeHandler,
        private CheckBookmarkStatusQueryHandler $checkStatusHandler
    ) {}

    /**
     * Toggle a bookmark for a user and campaign.
     * Returns true if bookmark was created, false if it was removed.
     */
    public function toggle(int $userId, int $campaignId): bool
    {
        $command = new ToggleBookmarkCommand($userId, $campaignId);
        $result = $this->toggleHandler->handle($command);

        // Clear the user's bookmarked campaigns cache (tenant-aware)
        $this->clearUserBookmarksCache($userId);

        return $result['action'] === 'created';
    }

    /**
     * Create a bookmark for a user and campaign.
     */
    public function create(int $userId, int $campaignId): Bookmark
    {
        $command = new CreateBookmarkCommand($userId, $campaignId);
        $bookmark = $this->createHandler->handle($command);

        // Clear the user's bookmarked campaigns cache (tenant-aware)
        $this->clearUserBookmarksCache($userId);

        return $bookmark;
    }

    /**
     * Remove a specific bookmark.
     */
    public function removeBookmark(int $userId, int $campaignId): bool
    {
        $command = new RemoveBookmarkCommand($userId, $campaignId);
        $result = $this->removeHandler->handle($command);

        if ($result) {
            // Clear the user's bookmarked campaigns cache (tenant-aware)
            $this->clearUserBookmarksCache($userId);
        }

        return $result;
    }

    /**
     * Get all bookmarked campaigns for a user.
     *
     * @return Collection<int, Campaign>
     */
    public function getUserBookmarks(int $userId): Collection
    {
        // Always return the direct repository result to maintain correct types
        return $this->repository->getUserBookmarkedCampaigns($userId);
    }

    /**
     * Get all bookmarks for a user with campaign details.
     *
     * @return SupportCollection<int, Campaign>
     */
    public function getUserBookmarksWithDetails(int $userId): SupportCollection
    {
        // Return campaigns from bookmarks with proper type
        return $this->repository->getUserBookmarksWithDetails($userId)
            ->pluck('campaign')
            ->filter()
            ->values();
    }

    /**
     * Check if a campaign is bookmarked by a user.
     */
    public function isBookmarked(int $userId, int $campaignId): bool
    {
        $query = new CheckBookmarkStatusQuery($userId, $campaignId);
        $result = $this->checkStatusHandler->handle($query);

        return $result['is_bookmarked'];
    }

    /**
     * Get bookmark count for a campaign.
     */
    public function getBookmarkCount(int $campaignId): int
    {
        return $this->repository->countByCampaign($campaignId);
    }

    /**
     * Organize user bookmarks (bulk operations).
     *
     * @param  array<int>  $campaignIds
     * @return array{affected_count: int, action: string}
     */
    public function organizeBookmarks(int $userId, array $campaignIds, string $action): array
    {
        $command = new OrganizeBookmarksCommand($userId, $campaignIds, $action);
        $result = $this->organizeHandler->handle($command);

        if ($result['affected_count'] > 0) {
            // Clear the user's bookmarked campaigns cache
            $this->clearUserBookmarksCache($userId);
        }

        return $result;
    }

    /**
     * Remove all bookmarks for a user.
     */
    public function removeAllUserBookmarks(int $userId): int
    {
        $result = $this->organizeBookmarks($userId, [], 'remove_all');

        return $result['affected_count'];
    }

    /**
     * Remove bookmarks for inactive campaigns.
     */
    public function removeInactiveBookmarks(int $userId): int
    {
        $result = $this->organizeBookmarks($userId, [], 'remove_inactive');

        return $result['affected_count'];
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
