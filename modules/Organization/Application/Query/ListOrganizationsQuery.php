<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class ListOrganizationsQuery implements QueryInterface
{
    /**
     * @param  array<int, string>  $statuses
     * @param  array<int, string>  $types
     */
    public function __construct(
        public ?array $statuses = null,
        public ?array $types = null,
        public ?string $category = null,
        public ?string $search = null,
        public ?bool $verified = null,
        public ?bool $featured = null,
        public int $page = 1,
        public int $perPage = 15,
        public string $sortBy = 'name',
        public string $sortOrder = 'asc'
    ) {}
}
