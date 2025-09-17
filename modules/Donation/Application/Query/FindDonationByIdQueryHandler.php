<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Query;

use InvalidArgumentException;
use Modules\Donation\Domain\Exception\DonationException;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class FindDonationByIdQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private DonationRepositoryInterface $repository,
    ) {}

    public function handle(QueryInterface $query): Donation
    {
        if (! $query instanceof FindDonationByIdQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        $donation = $this->repository->findById($query->donationId);

        if (! $donation instanceof Donation) {
            throw DonationException::notFound($query->donationId);
        }

        return $donation;
    }
}
