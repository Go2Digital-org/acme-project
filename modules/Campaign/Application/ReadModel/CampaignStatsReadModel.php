<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Campaign statistics read model optimized for dashboard and reporting data.
 */
final class CampaignStatsReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        array $data,
        ?string $version = null
    ) {
        parent::__construct(0, $data, $version); // Stats don't have single ID
        $this->setCacheTtl(1800); // 30 minutes for stats
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'campaign_stats',
            'statistics',
            'dashboard',
        ]);
    }

    // Overall Campaign Statistics
    public function getTotalCampaigns(): int
    {
        return (int) $this->get('total_campaigns', 0);
    }

    public function getActiveCampaigns(): int
    {
        return (int) $this->get('active_campaigns', 0);
    }

    public function getCompletedCampaigns(): int
    {
        return (int) $this->get('completed_campaigns', 0);
    }

    public function getDraftCampaigns(): int
    {
        return (int) $this->get('draft_campaigns', 0);
    }

    public function getPendingApprovalCampaigns(): int
    {
        return (int) $this->get('pending_approval_campaigns', 0);
    }

    public function getRejectedCampaigns(): int
    {
        return (int) $this->get('rejected_campaigns', 0);
    }

    public function getCancelledCampaigns(): int
    {
        return (int) $this->get('cancelled_campaigns', 0);
    }

    // Success Rate Calculations
    public function getSuccessRate(): float
    {
        $total = $this->getTotalCampaigns();
        if ($total <= 0) {
            return 0.0;
        }

        return ($this->getCompletedCampaigns() / $total) * 100;
    }

    public function getApprovalRate(): float
    {
        $submitted = $this->getTotalCampaigns() - $this->getDraftCampaigns();
        if ($submitted <= 0) {
            return 0.0;
        }

        $approved = $this->getActiveCampaigns() + $this->getCompletedCampaigns();

        return ($approved / $submitted) * 100;
    }

    // Financial Statistics
    public function getTotalAmountRaised(): float
    {
        return (float) $this->get('total_amount_raised', 0);
    }

    public function getTotalTargetAmount(): float
    {
        return (float) $this->get('total_target_amount', 0);
    }

    public function getAverageTargetAmount(): float
    {
        return (float) $this->get('average_target_amount', 0);
    }

    public function getAverageAmountRaised(): float
    {
        return (float) $this->get('average_amount_raised', 0);
    }

    public function getOverallProgressPercentage(): float
    {
        $target = $this->getTotalTargetAmount();
        if ($target <= 0) {
            return 0.0;
        }

        return ($this->getTotalAmountRaised() / $target) * 100;
    }

    public function getLargestCampaignAmount(): float
    {
        return (float) $this->get('largest_campaign_amount', 0);
    }

    public function getSmallestCampaignAmount(): float
    {
        return (float) $this->get('smallest_campaign_amount', 0);
    }

    // Donation Statistics
    public function getTotalDonations(): int
    {
        return (int) $this->get('total_donations', 0);
    }

    public function getTotalUniqueDonators(): int
    {
        return (int) $this->get('total_unique_donators', 0);
    }

    public function getAverageDonationAmount(): float
    {
        return (float) $this->get('average_donation_amount', 0);
    }

    public function getAverageDonationsPerCampaign(): float
    {
        $campaigns = $this->getTotalCampaigns();
        if ($campaigns <= 0) {
            return 0.0;
        }

        return $this->getTotalDonations() / $campaigns;
    }

    public function getDonationConversionRate(): float
    {
        $views = $this->getTotalCampaignViews();
        if ($views <= 0) {
            return 0.0;
        }

        return ($this->getTotalDonations() / $views) * 100;
    }

    // Engagement Statistics
    public function getTotalCampaignViews(): int
    {
        return (int) $this->get('total_campaign_views', 0);
    }

    public function getTotalCampaignShares(): int
    {
        return (int) $this->get('total_campaign_shares', 0);
    }

    public function getTotalCampaignBookmarks(): int
    {
        return (int) $this->get('total_campaign_bookmarks', 0);
    }

    public function getAverageViewsPerCampaign(): float
    {
        $campaigns = $this->getTotalCampaigns();
        if ($campaigns <= 0) {
            return 0.0;
        }

        return $this->getTotalCampaignViews() / $campaigns;
    }

    // Time-based Statistics
    public function getAverageCampaignDuration(): int
    {
        return (int) $this->get('average_campaign_duration_days', 0);
    }

    public function getAverageTimeToCompletion(): int
    {
        return (int) $this->get('average_time_to_completion_days', 0);
    }

    public function getCampaignsCreatedThisMonth(): int
    {
        return (int) $this->get('campaigns_created_this_month', 0);
    }

    public function getCampaignsCompletedThisMonth(): int
    {
        return (int) $this->get('campaigns_completed_this_month', 0);
    }

    public function getMonthlyGrowthRate(): float
    {
        return (float) $this->get('monthly_growth_rate', 0);
    }

    // Top Performers
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopCampaignsByAmount(): array
    {
        return $this->get('top_campaigns_by_amount', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopCampaignsByDonations(): array
    {
        return $this->get('top_campaigns_by_donations', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopCampaignsByViews(): array
    {
        return $this->get('top_campaigns_by_views', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopOrganizations(): array
    {
        return $this->get('top_organizations', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopCategories(): array
    {
        return $this->get('top_categories', []);
    }

    // Performance Trends
    /**
     * @return array<string, mixed>
     */
    public function getMonthlyTrends(): array
    {
        return $this->get('monthly_trends', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getWeeklyTrends(): array
    {
        return $this->get('weekly_trends', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDailyTrends(): array
    {
        return $this->get('daily_trends', []);
    }

    // Category Statistics
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCampaignsByCategory(): array
    {
        return $this->get('campaigns_by_category', []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMostPopularCategory(): ?array
    {
        $categories = $this->getCampaignsByCategory();
        if ($categories === []) {
            return null;
        }

        return array_reduce($categories, fn ($max, $category) => ($max === null || $category['count'] > $max['count']) ? $category : $max);
    }

    // Status Distribution
    /**
     * @return array<string, mixed>
     */
    public function getStatusDistribution(): array
    {
        return [
            'active' => $this->getActiveCampaigns(),
            'completed' => $this->getCompletedCampaigns(),
            'draft' => $this->getDraftCampaigns(),
            'pending_approval' => $this->getPendingApprovalCampaigns(),
            'rejected' => $this->getRejectedCampaigns(),
            'cancelled' => $this->getCancelledCampaigns(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatusPercentages(): array
    {
        $total = $this->getTotalCampaigns();
        if ($total <= 0) {
            return [];
        }

        return [
            'active' => ($this->getActiveCampaigns() / $total) * 100,
            'completed' => ($this->getCompletedCampaigns() / $total) * 100,
            'draft' => ($this->getDraftCampaigns() / $total) * 100,
            'pending_approval' => ($this->getPendingApprovalCampaigns() / $total) * 100,
            'rejected' => ($this->getRejectedCampaigns() / $total) * 100,
            'cancelled' => ($this->getCancelledCampaigns() / $total) * 100,
        ];
    }

    // Alert Conditions
    public function getNeedingAttentionCount(): int
    {
        return (int) $this->get('campaigns_needing_attention', 0);
    }

    public function getExpiringSoonCount(): int
    {
        return (int) $this->get('campaigns_expiring_soon', 0);
    }

    public function getStuckCampaignsCount(): int
    {
        return (int) $this->get('stuck_campaigns', 0);
    }

    public function hasAlerts(): bool
    {
        if ($this->getNeedingAttentionCount() > 0) {
            return true;
        }
        if ($this->getExpiringSoonCount() > 0) {
            return true;
        }

        return $this->getStuckCampaignsCount() > 0;
    }

    // Health Score
    public function getHealthScore(): float
    {
        // Calculate overall health score based on multiple factors
        $successRate = $this->getSuccessRate();
        $approvalRate = $this->getApprovalRate();
        $conversionRate = $this->getDonationConversionRate();
        $avgProgress = $this->getOverallProgressPercentage();

        // Weighted average
        $healthScore = (
            $successRate * 0.3 +
            $approvalRate * 0.2 +
            $conversionRate * 0.25 +
            $avgProgress * 0.25
        );

        return min(100, $healthScore);
    }

    public function getHealthStatus(): string
    {
        $score = $this->getHealthScore();

        return match (true) {
            $score >= 80 => 'excellent',
            $score >= 60 => 'good',
            $score >= 40 => 'fair',
            $score >= 20 => 'poor',
            default => 'critical',
        };
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'totals' => [
                'campaigns' => $this->getTotalCampaigns(),
                'active' => $this->getActiveCampaigns(),
                'completed' => $this->getCompletedCampaigns(),
                'draft' => $this->getDraftCampaigns(),
                'pending_approval' => $this->getPendingApprovalCampaigns(),
                'rejected' => $this->getRejectedCampaigns(),
                'cancelled' => $this->getCancelledCampaigns(),
            ],
            'financial' => [
                'total_amount_raised' => $this->getTotalAmountRaised(),
                'total_target_amount' => $this->getTotalTargetAmount(),
                'average_target_amount' => $this->getAverageTargetAmount(),
                'average_amount_raised' => $this->getAverageAmountRaised(),
                'overall_progress_percentage' => $this->getOverallProgressPercentage(),
                'largest_campaign_amount' => $this->getLargestCampaignAmount(),
                'smallest_campaign_amount' => $this->getSmallestCampaignAmount(),
            ],
            'donations' => [
                'total_donations' => $this->getTotalDonations(),
                'total_unique_donators' => $this->getTotalUniqueDonators(),
                'average_donation_amount' => $this->getAverageDonationAmount(),
                'average_donations_per_campaign' => $this->getAverageDonationsPerCampaign(),
                'donation_conversion_rate' => $this->getDonationConversionRate(),
            ],
            'engagement' => [
                'total_views' => $this->getTotalCampaignViews(),
                'total_shares' => $this->getTotalCampaignShares(),
                'total_bookmarks' => $this->getTotalCampaignBookmarks(),
                'average_views_per_campaign' => $this->getAverageViewsPerCampaign(),
            ],
            'performance' => [
                'success_rate' => $this->getSuccessRate(),
                'approval_rate' => $this->getApprovalRate(),
                'average_campaign_duration' => $this->getAverageCampaignDuration(),
                'average_time_to_completion' => $this->getAverageTimeToCompletion(),
                'monthly_growth_rate' => $this->getMonthlyGrowthRate(),
            ],
            'trends' => [
                'campaigns_created_this_month' => $this->getCampaignsCreatedThisMonth(),
                'campaigns_completed_this_month' => $this->getCampaignsCompletedThisMonth(),
                'monthly_trends' => $this->getMonthlyTrends(),
                'weekly_trends' => $this->getWeeklyTrends(),
            ],
            'top_performers' => [
                'campaigns_by_amount' => $this->getTopCampaignsByAmount(),
                'campaigns_by_donations' => $this->getTopCampaignsByDonations(),
                'organizations' => $this->getTopOrganizations(),
                'categories' => $this->getTopCategories(),
            ],
            'distributions' => [
                'status' => $this->getStatusDistribution(),
                'status_percentages' => $this->getStatusPercentages(),
                'campaigns_by_category' => $this->getCampaignsByCategory(),
            ],
            'alerts' => [
                'needing_attention' => $this->getNeedingAttentionCount(),
                'expiring_soon' => $this->getExpiringSoonCount(),
                'stuck_campaigns' => $this->getStuckCampaignsCount(),
                'has_alerts' => $this->hasAlerts(),
            ],
            'health' => [
                'score' => $this->getHealthScore(),
                'status' => $this->getHealthStatus(),
            ],
        ];
    }

    /**
     * Get summary data for dashboard widgets
     *
     * @return array<string, mixed>
     */
    public function toDashboardSummary(): array
    {
        return [
            'totals' => [
                'campaigns' => $this->getTotalCampaigns(),
                'active' => $this->getActiveCampaigns(),
                'completed' => $this->getCompletedCampaigns(),
                'amount_raised' => $this->getTotalAmountRaised(),
            ],
            'performance' => [
                'success_rate' => $this->getSuccessRate(),
                'approval_rate' => $this->getApprovalRate(),
                'conversion_rate' => $this->getDonationConversionRate(),
                'overall_progress' => $this->getOverallProgressPercentage(),
            ],
            'alerts' => [
                'count' => $this->getNeedingAttentionCount() + $this->getExpiringSoonCount(),
                'has_alerts' => $this->hasAlerts(),
            ],
            'health' => [
                'score' => $this->getHealthScore(),
                'status' => $this->getHealthStatus(),
            ],
        ];
    }
}
