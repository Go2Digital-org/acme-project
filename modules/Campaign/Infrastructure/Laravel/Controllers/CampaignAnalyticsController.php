<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Shared\Domain\Service\CampaignDonationServiceInterface;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class CampaignAnalyticsController
{
    public function __construct(
        private CampaignDonationServiceInterface $campaignDonationService,
    ) {}

    /**
     * Get comprehensive analytics for a specific campaign.
     */
    public function __invoke(Campaign $campaign): JsonResponse
    {
        $analytics = $this->campaignDonationService->getCampaignDonationAnalytics($campaign->id);

        return ApiResponse::success(
            data: [
                'campaign' => [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'goal_amount' => $campaign->goal_amount,
                    'current_amount' => $campaign->current_amount,
                    'progress_percentage' => $campaign->getProgressPercentage(),
                    'days_remaining' => $campaign->getDaysRemaining(),
                    'is_active' => $campaign->isActive(),
                ],
                'donation_statistics' => $analytics['overview'],
                'payment_methods' => $analytics['payment_methods'],
                'monthly_trends' => $analytics['monthly_trends'],
                'top_donations' => $analytics['top_donations'],
                'performance_metrics' => [
                    'goal_completion_percentage' => $campaign->getProgressPercentage(),
                    'remaining_amount' => $campaign->getRemainingAmount(),
                    'days_active' => $campaign->start_date?->diffInDays(now()) ?? 0,
                    'average_daily_donations' => ($campaign->start_date !== null && $campaign->start_date->diffInDays(now()) > 0)
                        ? round($analytics['overview']['completed_donations'] / $campaign->start_date->diffInDays(now()), 2)
                        : 0,
                ],
            ],
            message: 'Campaign analytics retrieved successfully.',
        );
    }
}
