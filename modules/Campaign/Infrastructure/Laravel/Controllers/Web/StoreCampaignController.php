<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers\Web;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Application\Service\CampaignService;
use Modules\Campaign\Infrastructure\Laravel\Requests\Web\StoreCampaignRequest;

final class StoreCampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService,
    ) {}

    /**
     * Handle the incoming request to store a new campaign.
     */
    public function __invoke(StoreCampaignRequest $request): RedirectResponse
    {
        try {
            $validatedData = $request->validated();
            $campaign = $this->campaignService->storeCampaign($validatedData);

            // Determine the success message based on the action
            $action = $validatedData['action'] ?? 'draft';
            $successMessage = match ($action) {
                'submit' => __('campaigns.campaign_submitted_for_approval'),
                'draft' => __('campaigns.campaign_draft_saved'),
                default => __('campaigns.campaign_created'),
            };

            Log::info('Campaign created', [
                'campaign_id' => $campaign->id,
                'user_id' => auth()->id(),
                'title' => $campaign->title,
                'status' => $campaign->status->value,
                'action' => $action,
            ]);

            return redirect()
                ->route('campaigns.show', $campaign->uuid)
                ->with('success', $successMessage);
        } catch (Exception $e) {
            Log::error('Failed to create campaign', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('Failed to create campaign. Please try again.'));
        }
    }
}
