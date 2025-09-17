<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\Query;

use Modules\Dashboard\Application\ReadModel\AnalyticsDashboardReadModel;
use Modules\Dashboard\Domain\Repository\DashboardRepositoryInterface;

final readonly class GetAnalyticsDashboardQueryHandler
{
    public function __construct(
        private DashboardRepositoryInterface $repository
    ) {}

    public function handle(GetAnalyticsDashboardQuery $query): AnalyticsDashboardReadModel
    {
        $campaignStats = $this->repository->getOptimizedCampaignStats();
        $organizationStats = $this->repository->getOrganizationStats();
        $paymentAnalytics = $this->repository->getPaymentAnalytics();
        $realtimeStats = $this->repository->getRealtimeStats();
        $revenueSummary = $this->repository->getRevenueSummary();
        $successRates = $this->repository->getSuccessRates();
        $timeBasedAnalytics = $this->repository->getTimeBasedAnalytics();
        $donationsStats = $this->repository->getTotalDonationsStats();
        $engagementStats = $this->repository->getUserEngagementStats();

        return new AnalyticsDashboardReadModel(
            id: $query->organizationId ?? 0,
            data: [
                'campaign_stats' => $campaignStats,
                'organization_stats' => $organizationStats,
                'payment_analytics' => $paymentAnalytics,
                'realtime_stats' => $realtimeStats,
                'revenue_summary' => $revenueSummary,
                'success_rates' => $successRates,
                'time_based_analytics' => $timeBasedAnalytics,
                'donations_stats' => $donationsStats,
                'engagement_stats' => $engagementStats,
            ]
        );
    }
}
