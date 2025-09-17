<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Query;

use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class SearchOrganizationsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private OrganizationRepositoryInterface $repository,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Organization>
     */
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        if (! $query instanceof SearchOrganizationsQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        // Add search term to filters
        $filters = $query->filters;

        if ($query->searchTerm !== '') {
            $filters = array_merge($filters, ['search' => $query->searchTerm]);
        }

        return $this->repository->paginate(
            $query->page,
            $query->perPage,
            $filters,
            $query->sortBy,
            $query->sortOrder,
        );
    }
}
