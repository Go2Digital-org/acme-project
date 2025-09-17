<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Laravel\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

/**
 * Edit User Profile Controller.
 *
 * Displays user profile edit view with campaign data.
 */
final readonly class EditUserProfileController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $this->getAuthenticatedUser($request);

        // Fetch featured campaigns (most popular active campaigns)
        $featuredCampaigns = $this->campaignRepository->paginate(
            page: 1,
            perPage: 2,
            filters: ['filter' => 'popular'],
        )->items();

        // Fetch recent campaigns
        $recentCampaigns = $this->campaignRepository->paginate(
            page: 1,
            perPage: 3,
            filters: ['filter' => 'recent'],
        )->items();

        return view('dashboard', [
            'user' => $user,
            'featuredCampaigns' => $featuredCampaigns,
            'recentCampaigns' => $recentCampaigns,
        ]);
    }
}
