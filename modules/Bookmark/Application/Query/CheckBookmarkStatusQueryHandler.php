<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Query;

use Modules\Bookmark\Domain\Model\Bookmark;
use Modules\Bookmark\Domain\Repository\BookmarkRepositoryInterface;

/**
 * Handler for checking bookmark status.
 */
final readonly class CheckBookmarkStatusQueryHandler
{
    public function __construct(
        private BookmarkRepositoryInterface $repository
    ) {}

    /**
     * @return array{is_bookmarked: bool, bookmark_id: int|null}
     */
    public function handle(CheckBookmarkStatusQuery $query): array
    {
        $bookmark = $this->repository->findByUserAndCampaign(
            $query->userId,
            $query->campaignId
        );

        return [
            'is_bookmarked' => $bookmark instanceof Bookmark,
            'bookmark_id' => $bookmark?->id,
        ];
    }
}
