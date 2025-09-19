<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Command;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Bookmark\Domain\Repository\BookmarkRepositoryInterface;

/**
 * Handler for organizing user bookmarks (bulk operations).
 */
final readonly class OrganizeBookmarksCommandHandler
{
    public function __construct(
        private BookmarkRepositoryInterface $repository
    ) {}

    /**
     * @return array{affected_count: int, action: string}
     */
    public function handle(OrganizeBookmarksCommand $command): array
    {
        return DB::transaction(function () use ($command): array {
            $affectedCount = match ($command->action) {
                'remove_all' => $this->repository->deleteByUserId($command->userId),
                'remove_selected' => $this->removeSelectedBookmarks($command),
                'remove_inactive' => $this->removeInactiveBookmarks($command),
                default => throw new InvalidArgumentException("Unknown action: {$command->action}"),
            };

            return [
                'affected_count' => $affectedCount,
                'action' => $command->action,
            ];
        });
    }

    private function removeSelectedBookmarks(OrganizeBookmarksCommand $command): int
    {
        $count = 0;
        foreach ($command->campaignIds as $campaignId) {
            if ($this->repository->deleteByUserAndCampaign($command->userId, $campaignId)) {
                $count++;
            }
        }

        return $count;
    }

    private function removeInactiveBookmarks(OrganizeBookmarksCommand $command): int
    {
        // Get user's bookmarks with campaign details
        $bookmarks = $this->repository->getUserBookmarksWithDetails($command->userId);

        $count = 0;
        foreach ($bookmarks as $bookmark) {
            // Ensure bookmark is an array with required keys
            if (! is_array($bookmark)) {
                continue;
            }
            if (! isset($bookmark['campaign'])) {
                continue;
            }
            if (! isset($bookmark['id'])) {
                continue;
            }
            // Ensure campaign is an array with status
            if (! is_array($bookmark['campaign'])) {
                continue;
            }
            if (! isset($bookmark['campaign']['status'])) {
                continue;
            }
            // Check if campaign status indicates it should be removed
            $campaignStatus = (string) $bookmark['campaign']['status'];
            if (! in_array($campaignStatus, ['completed', 'cancelled', 'expired'], true)) {
                continue;
            }

            // Safely cast the ID to integer before deletion
            $bookmarkId = is_numeric($bookmark['id']) ? (int) $bookmark['id'] : null;
            if ($bookmarkId === null) {
                continue;
            }
            if (! $this->repository->deleteById($bookmarkId)) {
                continue;
            }
            $count++;
        }

        return $count;
    }
}
