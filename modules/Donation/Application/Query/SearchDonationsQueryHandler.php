<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Query;

use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class SearchDonationsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private DonationRepositoryInterface $repository,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Donation>
     */
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        if (! $query instanceof SearchDonationsQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        // Add search term to filters
        $filters = $query->filters;

        if ($query->searchTerm !== '') {
            $filters['search'] = $query->searchTerm;
        }

        return $this->repository->paginate(
            page: $query->page,
            perPage: $query->perPage,
            filters: $filters,
            sortBy: $query->sortBy,
            sortOrder: $query->sortOrder,
        );
    }
}
