<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Service;

use Modules\Shared\Domain\Repository\DonationRelationRepositoryInterface;

/**
 * Service to handle campaign-donation interactions without direct coupling.
 */
class CampaignDonationService
{
    public function __construct(
        private readonly DonationRelationRepositoryInterface $donationRepository,
    ) {}

    public function getDonationCount(int $campaignId): int
    {
        return $this->donationRepository->getDonationCountForCampaign($campaignId);
    }

    public function getTotalRaised(int $campaignId): float
    {
        return $this->donationRepository->getTotalDonationAmountForCampaign($campaignId);
    }

    public function hasDonations(int $campaignId): bool
    {
        return $this->getDonationCount($campaignId) > 0;
    }
}
