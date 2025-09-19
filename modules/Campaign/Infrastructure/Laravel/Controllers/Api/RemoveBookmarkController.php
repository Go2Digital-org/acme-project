<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Campaign\Application\Service\BookmarkService;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class RemoveBookmarkController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private BookmarkService $bookmarkService,
        private CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * Remove bookmark from a campaign.
     *
     * DELETE /api/v1/campaigns/{id}/bookmark
     */
    public function __invoke(Request $request, int $campaignId): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId($request);

        // Check if campaign exists
        $campaign = $this->campaignRepository->findById($campaignId);

        if (! $campaign instanceof Campaign) {
            return ApiResponse::notFound('Campaign not found');
        }

        $wasRemoved = $this->bookmarkService->removeBookmark($userId, $campaignId);

        if (! $wasRemoved) {
            return ApiResponse::notFound('Bookmark not found');
        }

        return ApiResponse::success([
            'message' => 'Bookmark removed successfully',
            'bookmark_count' => $this->bookmarkService->getBookmarkCount($campaignId),
        ]);
    }
}
