<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Command;

use Illuminate\Support\Facades\DB;
use Modules\Bookmark\Domain\Model\Bookmark;
use Modules\Bookmark\Domain\Repository\BookmarkRepositoryInterface;

/**
 * Handler for toggling bookmarks (create if not exists, remove if exists).
 */
final readonly class ToggleBookmarkCommandHandler
{
    public function __construct(
        private BookmarkRepositoryInterface $repository
    ) {}

    /**
     * @return array{bookmark: Bookmark|null, action: string}
     */
    public function handle(ToggleBookmarkCommand $command): array
    {
        return DB::transaction(function () use ($command) {
            $existingBookmark = $this->repository->findByUserAndCampaign(
                $command->userId,
                $command->campaignId
            );

            if ($existingBookmark instanceof Bookmark) {
                // Remove existing bookmark
                $this->repository->deleteByUserAndCampaign(
                    $command->userId,
                    $command->campaignId
                );

                return [
                    'bookmark' => null,
                    'action' => 'removed',
                ];
            }

            // Create new bookmark
            $bookmark = $this->repository->create([
                'user_id' => $command->userId,
                'campaign_id' => $command->campaignId,
            ]);

            return [
                'bookmark' => $bookmark,
                'action' => 'created',
            ];
        });
    }
}
