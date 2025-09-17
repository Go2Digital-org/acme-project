<?php

declare(strict_types=1);

namespace Modules\Search\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class ReindexAllCommand implements CommandInterface
{
    public function __construct(
        public ?string $entityType = null,
        public bool $fresh = false,
        public int $chunkSize = 1000,
    ) {}
}
