<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Service;

use Illuminate\Http\Request;

/**
 * Contract for campaign-donation interaction services.
 * Used by Campaign controllers to access donation data without direct coupling.
 */
interface CampaignDonationServiceInterface
{
    /**
     * Get donations for a campaign with pagination and filtering.
     *
     * @return array<string, mixed>
     */
    public function getDonationsForCampaign(int $campaignId, Request $request): array;

    /**
     * Get donation analytics for a campaign.
     *
     * @return array<string, mixed>
     */
    public function getCampaignDonationAnalytics(int $campaignId): array;
}
