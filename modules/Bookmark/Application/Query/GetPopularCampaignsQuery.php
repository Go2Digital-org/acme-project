<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query to get most bookmarked/popular campaigns.
 */
final readonly class GetPopularCampaignsQuery implements QueryInterface
{
    public function __construct(
        public int $limit = 10,
        public ?int $organizationId = null,
        public ?string $timeframe = null // 'week', 'month', 'year'
    ) {}
}
