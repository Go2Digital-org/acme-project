<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Query;

use InvalidArgumentException;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class ListDonationsByEmployeeQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private DonationRepositoryInterface $repository,
    ) {}

    /**
     * @return array<Donation>
     */
    public function handle(QueryInterface $query): array
    {
        if (! $query instanceof ListDonationsByEmployeeQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        return $this->repository->findByEmployee($query->employeeId);
    }
}
