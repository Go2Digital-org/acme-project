<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query to check if a user has bookmarked a campaign.
 */
final readonly class CheckBookmarkStatusQuery implements QueryInterface
{
    public function __construct(
        public int $userId,
        public int $campaignId
    ) {}
}
