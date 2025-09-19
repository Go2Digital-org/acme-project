<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Query;

final readonly class ListUserCampaignsQuery
{
    public function __construct(
        public int $userId,
        public int $page = 1,
        public int $perPage = 15,
        /** @var array<string, mixed> */
        public array $filters = [],
        public string $sortBy = 'created_at',
        public string $sortOrder = 'desc',
    ) {}
}
