<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Query;

use InvalidArgumentException;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class ListDonationsByUserQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private DonationRepositoryInterface $repository,
    ) {}

    /**
     * @return array<int, Donation>
     */
    public function handle(QueryInterface $query): array
    {
        if (! $query instanceof ListDonationsByUserQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        return $this->repository->findByEmployee($query->userId);
    }
}
