<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\ViewComposer;

use Illuminate\View\View;
use Modules\Shared\Application\Service\BreadcrumbManager;

final readonly class BreadcrumbViewComposer
{
    public function __construct(
        private BreadcrumbManager $breadcrumbManager,
    ) {}

    public function compose(View $view): void
    {
        // Generate breadcrumbs from current request using the new BreadcrumbManager
        $this->breadcrumbManager->generateFromCurrentRequest();
        $viewData = $this->breadcrumbManager->toViewData();

        $breadcrumbData = [
            'breadcrumbs' => $viewData['breadcrumbs'] ?? [],
            'structuredData' => $viewData['structured_data'] ?? [],
            'showBreadcrumbs' => $viewData['show_breadcrumbs'] ?? false,
        ];

        $view->with([
            'breadcrumbData' => $breadcrumbData,
            'breadcrumbs' => $breadcrumbData['breadcrumbs'], // Keep backwards compatibility
        ]);
    }
}
