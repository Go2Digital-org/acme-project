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
        return DB::transaction(function () use ($command) {
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
            if (! $bookmark->campaign) {
                continue;
            }
            if (! in_array($bookmark->campaign->status, ['completed', 'cancelled', 'expired'])) {
                continue;
            }
            if (! $this->repository->deleteById($bookmark->id)) {
                continue;
            }
            $count++;
        }

        return $count;
    }
}
