<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers\Web;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Application\Service\CampaignService;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Infrastructure\Laravel\Requests\Web\UpdateCampaignRequest;

final class UpdateCampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService,
    ) {}

    /**
     * Handle the incoming request to update an existing campaign.
     */
    public function __invoke(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        try {
            $updatedCampaign = $this->campaignService->updateCampaign(
                $campaign->id,
                $request->validated(),
            );

            Log::info('Campaign updated successfully', [
                'campaign_id' => $updatedCampaign->id,
                'user_id' => auth()->id(),
                'title' => $updatedCampaign->title,
            ]);

            return redirect()
                ->route('campaigns.show', $updatedCampaign->id)
                ->with('success', __('Campaign updated successfully!'));
        } catch (Exception $e) {
            Log::error('Failed to update campaign', [
                'campaign_id' => $campaign->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('Failed to update campaign. Please try again.'));
        }
    }
}
