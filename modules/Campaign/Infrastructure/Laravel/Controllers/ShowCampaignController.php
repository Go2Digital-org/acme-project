<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Campaign\Application\Service\CampaignViewDataService;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class ShowCampaignController
{
    public function __construct(
        private CampaignViewDataService $campaignViewDataService,
    ) {}

    /**
     * Get a specific campaign by ID.
     */
    public function __invoke(Campaign $campaign): JsonResponse
    {
        $campaignData = $this->campaignViewDataService->getSingleCampaignViewData($campaign);

        return ApiResponse::success(
            data: $campaignData,
            message: 'Campaign retrieved successfully.',
        );
    }
}
