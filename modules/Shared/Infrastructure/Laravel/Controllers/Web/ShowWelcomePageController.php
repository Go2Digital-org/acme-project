<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Modules\Shared\Application\Service\HomepageStatsService;

final readonly class ShowWelcomePageController
{
    public function __construct(
        private HomepageStatsService $homepageStatsService
    ) {}

    public function __invoke(): View|Factory
    {
        $stats = $this->homepageStatsService->getHomepageStats();

        return view('welcome', [
            'impact' => $stats['impact'],
            'featuredCampaigns' => $stats['featured_campaigns'],
            'employeeStats' => $stats['employee_stats'],
        ]);
    }
}
