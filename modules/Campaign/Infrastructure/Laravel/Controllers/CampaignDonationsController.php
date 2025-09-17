<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Shared\Domain\Service\CampaignDonationServiceInterface;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class CampaignDonationsController
{
    public function __construct(
        private CampaignDonationServiceInterface $campaignDonationService,
    ) {}

    /**
     * Get all donations for a specific campaign.
     */
    public function __invoke(Request $request, Campaign $campaign): JsonResponse
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:pending,processing,completed,failed,cancelled,refunded'],
            'payment_method' => ['nullable', 'in:credit_card,bank_transfer,paypal,stripe'],
            'anonymous' => ['nullable', 'boolean'],
        ]);

        $result = $this->campaignDonationService->getDonationsForCampaign($campaign->id, $request);

        return ApiResponse::success([
            'data' => $result['donations'],
            'meta' => [
                'current_page' => $result['pagination']['current_page'],
                'per_page' => $result['pagination']['per_page'],
                'total' => $result['pagination']['total'],
                'last_page' => $result['pagination']['last_page'],
            ],
        ], 'Campaign donations retrieved successfully.');
    }
}
