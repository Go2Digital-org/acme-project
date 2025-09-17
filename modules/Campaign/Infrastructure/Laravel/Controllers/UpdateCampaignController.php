<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Campaign\Application\Request\UpdateCampaignRequest;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Infrastructure\Laravel\Resource\CampaignResource;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class UpdateCampaignController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * Update an existing campaign.
     */
    public function __invoke(UpdateCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        // Check authorization - only campaign creator can update
        $authenticatedUserId = $this->getAuthenticatedUserId($request);

        if ($campaign->user_id !== $authenticatedUserId) {
            return ApiResponse::forbidden('You can only update campaigns you created.');
        }

        $validated = $request->validated();

        // Update the campaign
        $this->campaignRepository->updateById($campaign->id, $validated);

        // Refresh the model
        $campaign->refresh();

        return ApiResponse::success(
            data: new CampaignResource($campaign),
            message: 'Campaign updated successfully.',
        );
    }
}
