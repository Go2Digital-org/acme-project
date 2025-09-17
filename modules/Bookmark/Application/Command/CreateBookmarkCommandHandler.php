<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Command;

use Illuminate\Support\Facades\DB;
use Modules\Bookmark\Domain\Model\Bookmark;
use Modules\Bookmark\Domain\Repository\BookmarkRepositoryInterface;

/**
 * Handler for creating bookmarks.
 */
final readonly class CreateBookmarkCommandHandler
{
    public function __construct(
        private BookmarkRepositoryInterface $repository
    ) {}

    public function handle(CreateBookmarkCommand $command): Bookmark
    {
        return DB::transaction(function () use ($command) {
            // Check if bookmark already exists
            $existingBookmark = $this->repository->findByUserAndCampaign(
                $command->userId,
                $command->campaignId
            );

            if ($existingBookmark instanceof Bookmark) {
                return $existingBookmark;
            }

            // Create new bookmark
            return $this->repository->create([
                'user_id' => $command->userId,
                'campaign_id' => $command->campaignId,
            ]);
        });
    }
}
