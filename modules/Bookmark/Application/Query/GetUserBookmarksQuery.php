<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query to get all bookmarks for a user.
 */
final readonly class GetUserBookmarksQuery implements QueryInterface
{
    public function __construct(
        public int $userId,
        public bool $withDetails = false,
        public ?int $limit = null,
        public ?string $sortBy = 'created_at',
        public ?string $sortOrder = 'desc'
    ) {}
}
