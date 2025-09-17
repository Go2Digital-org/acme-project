<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Campaign\Application\Service\BookmarkService;
use Modules\Campaign\Application\ViewPresenter\CampaignCardPresenter;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;

class ShowCampaignWebController
{
    public function __construct(
        private readonly BookmarkService $bookmarkService,
    ) {}

    public function __invoke(Request $request, Campaign $campaign): View
    {
        // Campaign is automatically resolved by Laravel's route model binding
        // Load the relationships
        $campaign->load(['creator', 'categoryModel', 'organization']);

        // Check if campaign has a protected status and user is not authorized
        $protectedStatuses = [
            CampaignStatus::DRAFT,
            CampaignStatus::PENDING_APPROVAL,
            CampaignStatus::REJECTED,
        ];

        if (in_array($campaign->status, $protectedStatuses, true)) {
            $user = auth()->user();

            // Only allow the author or admin users to view protected campaigns
            $isAuthor = $user && $campaign->user_id === $user->id;
            $isAdmin = $user && $user->hasRole('super_admin');

            if (! $isAuthor && ! $isAdmin) {
                abort(404);
            }
        }

        // Prepare campaign data using presenter
        $presenter = new CampaignCardPresenter($campaign);
        $campaignData = $presenter->present();

        // Get recent donations for this campaign
        $recentDonations = collect(); // TODO: Implement when donation repository is available

        // Get related campaigns
        $relatedCampaigns = collect(); // TODO: Implement when needed

        // Check if user has bookmarked this campaign
        $isBookmarked = false;

        if (auth()->check() && auth()->id()) {
            $isBookmarked = $this->bookmarkService->isBookmarked((int) auth()->id(), $campaign->id);
        }

        return view('campaigns.show', [
            'campaign' => $campaign,
            'campaignData' => $campaignData,
            'progressPercentage' => $campaignData['progress_percentage'], // For backward compatibility
            'daysLeft' => $campaignData['days_remaining'], // For backward compatibility
            'recentDonations' => $recentDonations,
            'relatedCampaigns' => $relatedCampaigns,
            'isBookmarked' => $isBookmarked,
        ]);
    }
}
