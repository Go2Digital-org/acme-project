<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

/**
 * Command to remove a bookmark.
 */
final readonly class RemoveBookmarkCommand implements CommandInterface
{
    public function __construct(
        public int $userId,
        public int $campaignId
    ) {}
}
