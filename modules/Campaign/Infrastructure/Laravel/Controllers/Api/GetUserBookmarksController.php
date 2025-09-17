<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Campaign\Application\Service\BookmarkService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class GetUserBookmarksController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private BookmarkService $bookmarkService,
    ) {}

    /**
     * Get user's bookmarked campaigns.
     *
     * GET /api/v1/campaigns/bookmarked
     */
    public function __invoke(Request $request): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId($request);

        $bookmarkedCampaigns = $this->bookmarkService->getUserBookmarksWithDetails($userId);

        return ApiResponse::success([
            'data' => $bookmarkedCampaigns->map(fn ($campaign): array => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'slug' => $campaign->slug,
                'description' => $campaign->description,
                'goal_amount' => $campaign->goal_amount,
                'current_amount' => $campaign->current_amount,
                'progress_percentage' => $campaign->getProgressPercentage(),
                'start_date' => $campaign->start_date,
                'end_date' => $campaign->end_date,
                'status' => $campaign->status,
                'category' => $campaign->category,
                'featured_image' => $campaign->featured_image,
                'days_remaining' => $campaign->getDaysRemaining(),
                'organization' => [
                    'id' => $campaign->organization->id,
                    'name' => $campaign->organization->getName(),
                ],
                'creator' => [
                    'id' => $campaign->creator->getId(),
                    'name' => $campaign->creator->getName(),
                ],
            ]),
            'total' => $bookmarkedCampaigns->count(),
        ]);
    }
}
