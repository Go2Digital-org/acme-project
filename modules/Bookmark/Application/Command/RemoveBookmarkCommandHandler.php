<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Command;

use Illuminate\Support\Facades\DB;
use Modules\Bookmark\Domain\Repository\BookmarkRepositoryInterface;

/**
 * Handler for removing bookmarks.
 */
final readonly class RemoveBookmarkCommandHandler
{
    public function __construct(
        private BookmarkRepositoryInterface $repository
    ) {}

    public function handle(RemoveBookmarkCommand $command): bool
    {
        return DB::transaction(fn () => $this->repository->deleteByUserAndCampaign(
            $command->userId,
            $command->campaignId
        ));
    }
}
