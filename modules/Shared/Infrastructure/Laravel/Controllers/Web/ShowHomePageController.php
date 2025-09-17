<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Modules\Shared\Application\Service\HomepageStatsService;

final readonly class ShowHomePageController
{
    public function __construct(
        private HomepageStatsService $homepageStatsService,
    ) {}

    public function __invoke(): View|Factory
    {
        // Get all homepage stats from the service (which uses cache when available)
        $stats = $this->homepageStatsService->getHomepageStats();

        // If cache is empty, calculate stats directly
        if (empty($stats['featured_campaigns'])) {
            $featuredCampaigns = $this->homepageStatsService->getFeaturedCampaigns();
            $impact = $this->homepageStatsService->getImpactStats();
            $employeeStats = $this->homepageStatsService->getEmployeeStats();
        } else {
            // Use cached data
            $featuredCampaigns = $stats['featured_campaigns'] ?? [];
            $impact = $stats['impact'] ?? [];
            $employeeStats = $stats['employee_stats'] ?? [];
        }

        return view('welcome', [
            'featuredCampaigns' => $featuredCampaigns,
            'impact' => $impact,
            'employeeStats' => $employeeStats,
        ]);
    }
}
