<?php

declare(strict_types=1);

namespace Modules\Category\Application\Query;

final class ListCategoriesQuery
{
    public function __construct(
        public ?string $parentId = null,
        public ?bool $activeOnly = true,
        public ?string $sortBy = 'sort_order',
        public ?string $sortDirection = 'asc',
        public ?int $limit = null,
        public ?int $offset = null
    ) {}
}
