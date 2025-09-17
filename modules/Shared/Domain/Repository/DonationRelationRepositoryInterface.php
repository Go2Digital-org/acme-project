<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Contract for accessing donation relationships.
 * Used to decouple Campaign domain from Donation domain.
 */
interface DonationRelationRepositoryInterface
{
    /**
     * Get donations for a campaign.
     *
     * @return Collection<int, Model>
     */
    public function getDonationsForCampaign(int $campaignId): Collection;

    /**
     * Get donation count for a campaign.
     */
    public function getDonationCountForCampaign(int $campaignId): int;

    /**
     * Get total donation amount for a campaign.
     */
    public function getTotalDonationAmountForCampaign(int $campaignId): float;
}
