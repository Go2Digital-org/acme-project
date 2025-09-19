<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Query;

use InvalidArgumentException;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetDonationStatsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private DonationRepositoryInterface $repository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(QueryInterface $query): array
    {
        if (! $query instanceof GetDonationStatsQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        /** @var array<string, mixed> */
        $stats = [];

        // Get total amount by campaign
        if ($query->campaignId !== null) {
            $stats['campaign_total'] = $this->repository->getTotalDonationsByCampaign($query->campaignId);
            /** @var array<int, Donation> */
            $campaignDonations = $this->repository->findByCampaign($query->campaignId);
            $stats['campaign_donations'] = $campaignDonations;
            $stats['campaign_donation_count'] = count($campaignDonations);
        }

        // Get total amount by user
        if ($query->userId !== null) {
            $stats['user_total'] = $this->repository->getTotalDonationsByEmployee($query->userId);
            /** @var array<int, Donation> */
            $userDonations = $this->repository->findByEmployee($query->userId);
            $stats['user_donations'] = $userDonations;
            $stats['user_donation_count'] = count($userDonations);
        }

        // Get pending donations count
        /** @var array<int, Donation> */
        $pendingDonations = $this->repository->findPendingDonations();
        $stats['pending_donations_count'] = count($pendingDonations);

        // Get processing donations count
        /** @var array<int, Donation> */
        $processingDonations = $this->repository->findProcessingDonations();
        $stats['processing_donations_count'] = count($processingDonations);

        return $stats;
    }
}
