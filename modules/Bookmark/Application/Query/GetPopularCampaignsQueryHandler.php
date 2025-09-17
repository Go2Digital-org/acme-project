<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Query;

use Illuminate\Database\Eloquent\Collection;
use Modules\Bookmark\Domain\Repository\BookmarkRepositoryInterface;
use Modules\Campaign\Domain\Model\Campaign;

/**
 * Handler for getting most bookmarked/popular campaigns.
 */
final readonly class GetPopularCampaignsQueryHandler
{
    public function __construct(
        private BookmarkRepositoryInterface $repository
    ) {}

    /**
     * @return Collection<int, Campaign>
     */
    public function handle(GetPopularCampaignsQuery $query): Collection
    {
        return $this->repository->getMostBookmarkedCampaigns($query->limit);
    }
}
