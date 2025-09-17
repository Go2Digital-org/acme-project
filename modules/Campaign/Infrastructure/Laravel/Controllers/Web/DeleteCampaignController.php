<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers\Web;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Application\Service\CampaignService;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Infrastructure\Laravel\Requests\Web\DeleteCampaignRequest;

final class DeleteCampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService,
    ) {}

    /**
     * Handle the incoming request to delete a campaign.
     */
    public function __invoke(DeleteCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        try {
            $campaignTitle = $campaign->getTitle();
            $campaignId = $campaign->id;

            $deleted = $this->campaignService->deleteCampaign($campaign->id);

            if ($deleted) {
                Log::info('Campaign deleted successfully', [
                    'campaign_id' => $campaignId,
                    'user_id' => auth()->id(),
                    'title' => $campaignTitle,
                ]);

                return redirect()
                    ->route('campaigns.my-campaigns')
                    ->with('success', __('Campaign ":title" has been deleted successfully.', ['title' => $campaignTitle]));
            }

            return redirect()
                ->back()
                ->with('error', __('Failed to delete campaign. Please try again.'));
        } catch (CampaignException $e) {
            Log::warning('Campaign deletion denied', [
                'campaign_id' => $campaign->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'title' => $campaign->getTitle(),
            ]);

            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        } catch (Exception $e) {
            Log::error('Failed to delete campaign', [
                'campaign_id' => $campaign->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'title' => $campaign->getTitle(),
            ]);

            return redirect()
                ->back()
                ->with('error', __('Failed to delete campaign. Please try again.'));
        }
    }
}
