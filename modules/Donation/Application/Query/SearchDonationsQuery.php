<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class SearchDonationsQuery implements QueryInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        public string $searchTerm = '',
        public array $filters = [],
        public int $page = 1,
        public int $perPage = 15,
        public string $sortBy = 'created_at',
        public string $sortOrder = 'desc',
    ) {}
}
