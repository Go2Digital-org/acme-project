<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

/**
 * Command to organize user bookmarks (bulk operations).
 */
final readonly class OrganizeBookmarksCommand implements CommandInterface
{
    /**
     * @param  array<int, int>  $campaignIds  Campaign IDs to organize
     */
    public function __construct(
        public int $userId,
        public array $campaignIds,
        public string $action // 'remove_all', 'remove_selected', 'remove_inactive'
    ) {}
}
