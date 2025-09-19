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

final readonly class ToggleBookmarkController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private BookmarkService $bookmarkService,
        private CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * Toggle bookmark for a campaign.
     *
     * POST /api/v1/campaigns/{id}/bookmark
     */
    public function __invoke(Request $request, int $campaignId): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId($request);

        // Check if campaign exists
        $campaign = $this->campaignRepository->findById($campaignId);

        if (! $campaign instanceof Campaign) {
            return ApiResponse::notFound('Campaign not found');
        }

        $wasBookmarked = $this->bookmarkService->toggle($userId, $campaignId);

        return ApiResponse::success([
            'bookmarked' => $wasBookmarked,
            'message' => $wasBookmarked
                ? 'Campaign bookmarked successfully'
                : 'Bookmark removed successfully',
            'bookmark_count' => $this->bookmarkService->getBookmarkCount($campaignId),
        ]);
    }
}
