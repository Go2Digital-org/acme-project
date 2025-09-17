<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query to get bookmark statistics.
 */
final readonly class GetBookmarkStatsQuery implements QueryInterface
{
    public function __construct(
        public ?int $userId = null,
        public ?int $campaignId = null,
        public ?int $organizationId = null,
        public string $scope = 'overview' // 'overview', 'detailed', 'trends'
    ) {}
}
