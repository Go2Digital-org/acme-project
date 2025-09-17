<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query to get bookmarks by entity (campaign, organization).
 */
final readonly class GetBookmarksByEntityQuery implements QueryInterface
{
    public function __construct(
        public string $entityType, // 'campaign', 'organization'
        public int $entityId,
        public ?int $limit = null,
        public bool $withUserDetails = false
    ) {}
}
