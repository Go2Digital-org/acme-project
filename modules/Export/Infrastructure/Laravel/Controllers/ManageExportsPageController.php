<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Laravel\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class ManageExportsPageController
{
    use AuthenticatedUserTrait;

    /**
     * Render the manage exports page.
     */
    public function __invoke(Request $request): View
    {
        $user = $this->getAuthenticatedUser($request);

        return view('exports.manage', [
            'user' => $user,
            'exportFormats' => [
                'csv' => [
                    'label' => 'CSV',
                    'icon' => 'heroicon-o-table-cells',
                    'description' => 'Comma-separated values format, compatible with Excel and other spreadsheet applications.',
                ],
                'excel' => [
                    'label' => 'Excel',
                    'icon' => 'heroicon-o-document-chart-bar',
                    'description' => 'Microsoft Excel format with advanced formatting and multiple sheets support.',
                ],
            ],
            'exportStatuses' => [
                'pending' => 'Waiting to start',
                'processing' => 'In progress',
                'completed' => 'Ready for download',
                'failed' => 'Failed to complete',
                'cancelled' => 'Cancelled by user',
                'expired' => 'Download expired',
            ],
            'maxDateRange' => [
                'years' => 2,
                'description' => 'Maximum date range allowed is 2 years',
            ],
        ]);
    }
}
