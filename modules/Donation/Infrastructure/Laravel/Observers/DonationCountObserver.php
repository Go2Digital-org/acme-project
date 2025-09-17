<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Observers;

use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Schema;

/**
 * Observer to maintain the donations_count column on campaigns.
 * This eliminates the need for COUNT subqueries when displaying campaigns.
 */
class DonationCountObserver
{
    /**
     * Handle the Donation "created" event.
     */
    public function created(Donation $donation): void
    {
        $this->updateCampaignDonationCount($donation->campaign_id);
    }

    /**
     * Handle the Donation "updated" event.
     */
    public function updated(Donation $donation): void
    {
        // If campaign changed, update both old and new campaign counts
        if ($donation->isDirty('campaign_id')) {
            $originalCampaignId = $donation->getOriginal('campaign_id');
            if ($originalCampaignId) {
                $this->updateCampaignDonationCount($originalCampaignId);
            }
        }

        $this->updateCampaignDonationCount($donation->campaign_id);
    }

    /**
     * Handle the Donation "deleted" event.
     */
    public function deleted(Donation $donation): void
    {
        $this->updateCampaignDonationCount($donation->campaign_id);
    }

    /**
     * Handle the Donation "restored" event.
     */
    public function restored(Donation $donation): void
    {
        $this->updateCampaignDonationCount($donation->campaign_id);
    }

    /**
     * Handle the Donation "force deleted" event.
     */
    public function forceDeleted(Donation $donation): void
    {
        $this->updateCampaignDonationCount($donation->campaign_id);
    }

    /**
     * Update the donations_count column for a campaign.
     */
    private function updateCampaignDonationCount(int $campaignId): void
    {
        $campaign = Campaign::find($campaignId);

        if ($campaign && Schema::hasColumn('campaigns', 'donations_count')) {
            $count = Donation::where('campaign_id', $campaignId)
                ->whereNull('deleted_at')
                ->count();

            // Update without triggering model events to avoid recursion
            Campaign::where('id', $campaignId)
                ->update(['donations_count' => $count]);
        }
    }
}
