<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Campaign Analytics read model optimized for campaign performance metrics and insights.
 */
class CampaignAnalyticsReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        int $campaignId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($campaignId, $data, $version);
        $this->setCacheTtl(900); // 15 minutes for campaign analytics
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'campaign_analytics',
            'campaign:' . $this->id,
            'analytics_data',
        ]);
    }

    // Campaign Performance Metrics
    public function getCampaignId(): int
    {
        return (int) $this->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPerformanceMetrics(): array
    {
        return $this->get('performance', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDonationMetrics(): array
    {
        return $this->get('donations', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDonationTrends(): array
    {
        return $this->get('donation_trends', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTopDonors(): array
    {
        return $this->get('top_donors', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getEngagementMetrics(): array
    {
        return $this->get('engagement', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getConversionMetrics(): array
    {
        return $this->get('conversion', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getBreakdowns(): array
    {
        return $this->get('breakdowns', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getComparisons(): array
    {
        return $this->get('comparisons', []);
    }

    // Calculated Properties
    public function getTotalDonations(): int
    {
        $donations = $this->getDonationMetrics();

        return $donations['total_donations']['value'] ?? 0;
    }

    public function getTotalAmount(): float
    {
        $donations = $this->getDonationMetrics();

        return $donations['total_amount']['value'] ?? 0.0;
    }

    public function getAverageDonation(): float
    {
        $donations = $this->getDonationMetrics();

        return $donations['average_amount']['value'] ?? 0.0;
    }

    public function getUniqueDonosrs(): int
    {
        $donations = $this->getDonationMetrics();

        return $donations['unique_donors']['value'] ?? 0;
    }

    public function getTotalViews(): int
    {
        $engagement = $this->getEngagementMetrics();

        return $engagement['total_views'] ?? 0;
    }

    public function getUniqueViews(): int
    {
        $engagement = $this->getEngagementMetrics();

        return $engagement['unique_views'] ?? 0;
    }

    public function getConversionRate(): float
    {
        $conversion = $this->getConversionMetrics();

        return $conversion['conversion_rate'] ?? 0.0;
    }

    public function getEngagementRate(): float
    {
        $engagement = $this->getEngagementMetrics();

        return $engagement['engagement_rate'] ?? 0.0;
    }

    // Campaign Status
    public function getTotalCampaigns(): int
    {
        $performance = $this->getPerformanceMetrics();

        return $performance['total_campaigns']['value'] ?? 0;
    }

    public function getActiveCampaigns(): int
    {
        $performance = $this->getPerformanceMetrics();

        return $performance['active_campaigns']['value'] ?? 0;
    }

    public function getCompletedCampaigns(): int
    {
        $performance = $this->getPerformanceMetrics();

        return $performance['completed_campaigns']['value'] ?? 0;
    }

    public function getSuccessfulCampaigns(): int
    {
        $performance = $this->getPerformanceMetrics();

        return $performance['successful_campaigns']['value'] ?? 0;
    }

    public function getSuccessRate(): float
    {
        $performance = $this->getPerformanceMetrics();

        return $performance['success_rate']['value'] ?? 0.0;
    }

    public function getAverageCompletionRate(): float
    {
        $performance = $this->getPerformanceMetrics();

        return $performance['average_completion_rate']['value'] ?? 0.0;
    }

    // Top Donor Information
    /**
     * @return array<string, mixed>
     */
    public function getTopDonorsList(): array
    {
        $topDonors = $this->getTopDonors();

        return $topDonors['donors'] ?? [];
    }

    public function getTopDonorCount(): int
    {
        return count($this->getTopDonorsList());
    }

    // Trend Analysis
    /**
     * @return array<string, mixed>
     */
    public function getTrendData(): array
    {
        $trends = $this->getDonationTrends();

        return $trends['data'] ?? [];
    }

    public function getTrendInterval(): string
    {
        $trends = $this->getDonationTrends();

        return $trends['metadata']['interval'] ?? 'day';
    }

    public function getTrendDataPoints(): int
    {
        $trends = $this->getDonationTrends();

        return $trends['metadata']['data_points'] ?? 0;
    }

    // Performance Comparisons
    /**
     * @return array<string, mixed>
     */
    public function getCurrentPeriodPerformance(): array
    {
        $comparisons = $this->getComparisons();

        return $comparisons['current_period'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPreviousPeriodPerformance(): array
    {
        $comparisons = $this->getComparisons();

        return $comparisons['previous_period'] ?? [];
    }

    public function hasComparisionData(): bool
    {
        $comparisons = $this->getComparisons();

        return ! empty($comparisons['current_period']) && ! empty($comparisons['previous_period']);
    }

    // Breakdown Analysis
    /**
     * @return array<string, mixed>
     */
    public function getDonationAmountBreakdown(): array
    {
        $breakdowns = $this->getBreakdowns();

        return $breakdowns['donation_amounts'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPaymentMethodBreakdown(): array
    {
        $breakdowns = $this->getBreakdowns();

        return $breakdowns['payment_methods'] ?? [];
    }

    // Summary Methods
    /**
     * Get a comprehensive summary of campaign analytics.
     */
    /**
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'campaign_id' => $this->getCampaignId(),
            'performance' => [
                'total_campaigns' => $this->getTotalCampaigns(),
                'active_campaigns' => $this->getActiveCampaigns(),
                'success_rate' => $this->getSuccessRate(),
                'completion_rate' => $this->getAverageCompletionRate(),
            ],
            'donations' => [
                'total_donations' => $this->getTotalDonations(),
                'total_amount' => $this->getTotalAmount(),
                'average_donation' => $this->getAverageDonation(),
                'unique_donors' => $this->getUniqueDonosrs(),
            ],
            'engagement' => [
                'total_views' => $this->getTotalViews(),
                'unique_views' => $this->getUniqueViews(),
                'engagement_rate' => $this->getEngagementRate(),
                'conversion_rate' => $this->getConversionRate(),
            ],
            'trends' => [
                'data_points' => $this->getTrendDataPoints(),
                'interval' => $this->getTrendInterval(),
            ],
            'top_donors_count' => $this->getTopDonorCount(),
            'has_comparison_data' => $this->hasComparisionData(),
        ];
    }

    /**
     * Get key performance indicators.
     */
    /**
     * @return array<string, mixed>
     */
    public function getKPIs(): array
    {
        return [
            'donations' => $this->getTotalDonations(),
            'amount' => $this->getTotalAmount(),
            'donors' => $this->getUniqueDonosrs(),
            'views' => $this->getTotalViews(),
            'conversion_rate' => $this->getConversionRate(),
            'success_rate' => $this->getSuccessRate(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAnalyticsArray(): array
    {
        return [
            'campaign_analytics' => [
                'campaign_id' => $this->getCampaignId(),
                'summary' => $this->getSummary(),
                'kpis' => $this->getKPIs(),
                'performance' => $this->getPerformanceMetrics(),
                'donations' => $this->getDonationMetrics(),
                'engagement' => $this->getEngagementMetrics(),
                'trends' => $this->getDonationTrends(),
                'top_donors' => $this->getTopDonors(),
                'breakdowns' => $this->getBreakdowns(),
                'comparisons' => $this->getComparisons(),
            ],
            'metadata' => [
                'version' => $this->getVersion(),
                'cache_ttl' => $this->getCacheTtl(),
                'cache_tags' => $this->getCacheTags(),
            ],
        ];
    }
}
