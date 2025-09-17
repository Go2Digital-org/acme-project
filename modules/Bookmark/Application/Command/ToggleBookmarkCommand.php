<?php

declare(strict_types=1);

namespace Modules\Bookmark\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

/**
 * Command to toggle a bookmark (create if not exists, remove if exists).
 */
final readonly class ToggleBookmarkCommand implements CommandInterface
{
    public function __construct(
        public int $userId,
        public int $campaignId
    ) {}
}
