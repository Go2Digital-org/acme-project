<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

/**
 * Command to create a new bookmark.
 */
final readonly class CreateBookmarkCommand implements CommandInterface
{
    public function __construct(
        public int $userId,
        public int $campaignId
    ) {}
}
