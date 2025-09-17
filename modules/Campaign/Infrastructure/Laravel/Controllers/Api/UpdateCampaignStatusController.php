<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\Service\UserCampaignManagementService;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Campaign\Infrastructure\Laravel\Requests\Api\UpdateCampaignStatusRequest;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class UpdateCampaignStatusController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
        private UserCampaignManagementService $managementService,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(UpdateCampaignStatusRequest $request, Campaign $campaign): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId($request);
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            return response()->json([
                'message' => 'User not found.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check if user can manage this campaign
        if (! $this->managementService->canManageCampaign($userId, $campaign)) {
            return response()->json([
                'message' => 'Unauthorized to manage this campaign.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Get validated status from request
        $newStatus = $request->getStatus();

        // Check if status change is allowed based on transition rules
        if (! $this->managementService->canChangeStatus($campaign, $newStatus)) {
            return response()->json([
                'message' => 'Status change not allowed.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Additional permission checks for non-admin users
        if (! $user->isAdmin()) {
            // Regular users (campaign owners) can only make limited transitions
            $allowedOwnerTransitions = [
                // Draft campaigns can be submitted for approval
                [CampaignStatus::DRAFT, CampaignStatus::PENDING_APPROVAL],
                // Draft campaigns can be cancelled
                [CampaignStatus::DRAFT, CampaignStatus::CANCELLED],
                // Rejected campaigns can be moved back to draft
                [CampaignStatus::REJECTED, CampaignStatus::DRAFT],
                // Rejected campaigns can be cancelled
                [CampaignStatus::REJECTED, CampaignStatus::CANCELLED],
            ];

            $isAllowedForOwner = false;
            foreach ($allowedOwnerTransitions as [$from, $to]) {
                if ($campaign->status === $from && $newStatus === $to) {
                    $isAllowedForOwner = true;
                    break;
                }
            }

            if (! $isAllowedForOwner) {
                return response()->json([
                    'message' => 'You do not have permission to make this status change. Only administrators can perform this action.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // Update the campaign status
        $success = $this->campaignRepository->updateById($campaign->id, [
            'status' => $newStatus,
        ]);

        if (! $success) {
            return response()->json([
                'message' => 'Failed to update campaign status.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Get updated campaign for response
        $updatedCampaign = $this->campaignRepository->findById($campaign->id);

        if (! $updatedCampaign instanceof Campaign) {
            return response()->json([
                'message' => 'Campaign not found after update.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Campaign status updated successfully.',
            'campaign' => [
                'id' => $updatedCampaign->id,
                'status' => $updatedCampaign->status->value,
                'status_label' => ucfirst($updatedCampaign->status->value),
            ],
        ]);
    }
}
