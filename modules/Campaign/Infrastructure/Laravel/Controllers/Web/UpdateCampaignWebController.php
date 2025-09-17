<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Category\Domain\Repository\CategoryRepositoryInterface;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

class UpdateCampaignWebController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
        private CategoryRepositoryInterface $categoryRepository,
    ) {}

    public function __invoke(Request $request, Campaign $campaign): View
    {
        // Campaign is automatically resolved by Laravel's route model binding

        // Check authorization - user should only be able to edit their own campaigns
        $userId = $this->getAuthenticatedUserId($request);

        if ($campaign->user_id !== $userId) {
            abort(403, 'Unauthorized to edit this campaign');
        }

        // Get campaign categories from database for form select options
        $categories = $this->categoryRepository->findActive()
            ->mapWithKeys(fn ($category): array => [$category->id => $category->getName()])
            ->toArray();

        // Get active organizations for form select options
        $organizations = $this->organizationRepository->findActiveOrganizations();
        $organizationOptions = collect($organizations)
            ->filter() // Remove any null values
            ->mapWithKeys(fn ($org): array => [$org->id => $org->getName()])
            ->toArray();

        return view('campaigns.create', [
            'categories' => $categories,
            'organizations' => $organizationOptions,
            'campaign' => $campaign, // For edit form, pass existing campaign
        ]);
    }
}
