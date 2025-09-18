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

final readonly class GetBookmarkStatusController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private BookmarkService $bookmarkService,
        private CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * Check if campaign is bookmarked by user.
     *
     * GET /api/v1/campaigns/{id}/bookmark/status
     */
    public function __invoke(Request $request, int $campaignId): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId($request);

        // Check if campaign exists
        $campaign = $this->campaignRepository->findById($campaignId);

        if (! $campaign instanceof Campaign) {
            return ApiResponse::notFound('Campaign not found');
        }

        $isBookmarked = $this->bookmarkService->isBookmarked($userId, $campaignId);
        $bookmarkCount = $this->bookmarkService->getBookmarkCount($campaignId);

        return ApiResponse::success([
            'bookmarked' => $isBookmarked,
            'bookmark_count' => $bookmarkCount,
        ]);
    }
}
