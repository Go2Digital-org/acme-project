<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Command;

final readonly class IndexCampaignsAsyncCommand
{
    public function __construct(
        public int $chunkSize = 1000,
        public int $offset = 0,
        public ?int $limit = null
    ) {}
}
