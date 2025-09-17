<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class DeleteCampaignController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * Delete a campaign.
     */
    public function __invoke(Request $request, Campaign $campaign): JsonResponse
    {
        // Check authorization - only campaign creator can delete
        $authenticatedUserId = $this->getAuthenticatedUserId($request);

        if ($campaign->user_id !== $authenticatedUserId) {
            return ApiResponse::forbidden('You can only delete campaigns you created.');
        }

        // Check if campaign has donations (business rule)
        if ($campaign->current_amount > 0) {
            return ApiResponse::error(
                'Cannot delete campaign that has received donations.',
                statusCode: 409,
            );
        }

        $this->campaignRepository->delete($campaign->id);

        return ApiResponse::success(
            message: 'Campaign deleted successfully.',
        );
    }
}
