<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers\Web;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Application\Service\CampaignService;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Infrastructure\Laravel\Requests\Web\RestoreCampaignRequest;

final class RestoreCampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService,
    ) {}

    /**
     * Handle the incoming request to restore a soft-deleted campaign.
     */
    public function __invoke(RestoreCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        try {
            $campaignTitle = $campaign->getTitle();
            $campaignId = $campaign->id;

            // Check if campaign is actually soft-deleted
            if (! $campaign->trashed()) {
                return redirect()
                    ->route('campaigns.my-campaigns')
                    ->with('error', __('Campaign ":title" is not deleted and cannot be restored.', ['title' => $campaignTitle]));
            }

            $restored = $this->campaignService->restoreCampaign($campaign->id);

            if ($restored) {
                Log::info('Campaign restored successfully', [
                    'campaign_id' => $campaignId,
                    'user_id' => auth()->id(),
                    'title' => $campaignTitle,
                ]);

                return redirect()
                    ->route('campaigns.my-campaigns')
                    ->with('success', __('Campaign ":title" has been restored successfully.', ['title' => $campaignTitle]));
            }

            return redirect()
                ->back()
                ->with('error', __('Failed to restore campaign. Please try again.'));

        } catch (Exception $e) {
            Log::error('Failed to restore campaign', [
                'campaign_id' => $campaign->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'title' => $campaign->getTitle(),
            ]);

            return redirect()
                ->back()
                ->with('error', __('Failed to restore campaign. Please try again.'));
        }
    }
}
