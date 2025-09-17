<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Contracts\View\View;
use Modules\Category\Domain\Repository\CategoryRepositoryInterface;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;

class CreateCampaignWebController
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
    ) {}

    public function __invoke(): View
    {
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
            'campaign' => null, // For create form, no existing campaign
        ]);
    }
}
