<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Donation Metrics read model optimized for donation analytics and donor insights.
 */
class DonationMetricsReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        int $entityId, // Can be donor ID, campaign ID, or organization ID
        array $data,
        ?string $version = null
    ) {
        parent::__construct($entityId, $data, $version);
        $this->setCacheTtl(1200); // 20 minutes for donation metrics
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'donation_metrics',
            'donation_analytics',
            'metrics_data',
        ]);
    }

    // Basic Statistics
    /**
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        return $this->get('statistics', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTrends(): array
    {
        return $this->get('trends', []);
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
    public function getSegmentation(): array
    {
        return $this->get('segmentation', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getGeographicMetrics(): array
    {
        return $this->get('geographic', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTemporalPatterns(): array
    {
        return $this->get('temporal', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRetentionMetrics(): array
    {
        return $this->get('retention', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getComparisons(): array
    {
        return $this->get('comparisons', []);
    }

    // Core Donation Metrics
    public function getTotalDonations(): int
    {
        $stats = $this->getStatistics();

        return $stats['total_donations']['value'] ?? 0;
    }

    public function getTotalAmount(): float
    {
        $stats = $this->getStatistics();

        return $stats['total_amount']['value'] ?? 0.0;
    }

    public function getAverageDonation(): float
    {
        $stats = $this->getStatistics();

        return $stats['average_amount']['value'] ?? 0.0;
    }

    public function getMinDonation(): float
    {
        $stats = $this->getStatistics();

        return $stats['min_amount']['value'] ?? 0.0;
    }

    public function getMaxDonation(): float
    {
        $stats = $this->getStatistics();

        return $stats['max_amount']['value'] ?? 0.0;
    }

    public function getUniqueDonors(): int
    {
        $stats = $this->getStatistics();

        return $stats['unique_donors']['value'] ?? 0;
    }

    // Trend Analysis
    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function getTrendData(): array
    {
        $trends = $this->getTrends();

        return $trends['data'] ?? [];
    }

    public function getTrendInterval(): string
    {
        $trends = $this->getTrends();

        return $trends['metadata']['interval'] ?? 'day';
    }

    public function getTrendDataPoints(): int
    {
        $trends = $this->getTrends();

        return $trends['metadata']['data_points'] ?? 0;
    }

    public function hasTrendData(): bool
    {
        return $this->getTrendData() !== [];
    }

    // Top Donors
    /**
     * @return array<string, mixed>
     */
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

    // Segmentation Analysis
    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function getAmountSegments(): array
    {
        $segmentation = $this->getSegmentation();

        return $segmentation['amount_segments'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function getFrequencySegments(): array
    {
        $segmentation = $this->getSegmentation();

        return $segmentation['frequency_segments'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function getPaymentMethodSegments(): array
    {
        $segmentation = $this->getSegmentation();

        return $segmentation['payment_methods'] ?? [];
    }

    public function hasSegmentationData(): bool
    {
        $segmentation = $this->getSegmentation();

        return ! empty($segmentation['amount_segments']) ||
               ! empty($segmentation['frequency_segments']) ||
               ! empty($segmentation['payment_methods']);
    }

    // Geographic Analysis
    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function getOrganizationBreakdown(): array
    {
        $geographic = $this->getGeographicMetrics();

        return $geographic['by_organization'] ?? [];
    }

    // Temporal Pattern Analysis
    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function getHourlyPattern(): array
    {
        $temporal = $this->getTemporalPatterns();

        return $temporal['hourly_pattern'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function getWeeklyPattern(): array
    {
        $temporal = $this->getTemporalPatterns();

        return $temporal['weekly_pattern'] ?? [];
    }

    public function hasTemporalPatterns(): bool
    {
        $temporal = $this->getTemporalPatterns();

        return ! empty($temporal['hourly_pattern']) || ! empty($temporal['weekly_pattern']);
    }

    // Retention Analysis
    public function getRetentionRate(): float
    {
        $retention = $this->getRetentionMetrics();

        return $retention['retention_rate'] ?? 0.0;
    }

    public function getReturningDonors(): int
    {
        $retention = $this->getRetentionMetrics();

        return $retention['returning_donors'] ?? 0;
    }

    public function getAverageDaysToReturn(): float
    {
        $retention = $this->getRetentionMetrics();

        return $retention['avg_days_to_return'] ?? 0.0;
    }

    public function hasRetentionData(): bool
    {
        return $this->getRetentionMetrics() !== [];
    }

    // Comparison Analysis
    /**
     * @return array<string, mixed>
     */
    public function getCurrentPeriodMetrics(): array
    {
        $comparisons = $this->getComparisons();

        return $comparisons['current_period'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPreviousPeriodMetrics(): array
    {
        $comparisons = $this->getComparisons();

        return $comparisons['previous_period'] ?? [];
    }

    public function hasComparisonData(): bool
    {
        $comparisons = $this->getComparisons();

        return ! empty($comparisons['current_period']) && ! empty($comparisons['previous_period']);
    }

    // Growth Calculations
    public function getDonationGrowthRate(): float
    {
        if (! $this->hasComparisonData()) {
            return 0.0;
        }

        $current = $this->getCurrentPeriodMetrics();
        $previous = $this->getPreviousPeriodMetrics();

        $currentTotal = $current['total_donations']['value'] ?? 0;
        $previousTotal = $previous['total_donations']['value'] ?? 0;

        if ($previousTotal == 0) {
            return $currentTotal > 0 ? 100.0 : 0.0;
        }

        return (($currentTotal - $previousTotal) / $previousTotal) * 100;
    }

    public function getAmountGrowthRate(): float
    {
        if (! $this->hasComparisonData()) {
            return 0.0;
        }

        $current = $this->getCurrentPeriodMetrics();
        $previous = $this->getPreviousPeriodMetrics();

        $currentAmount = $current['total_amount']['value'] ?? 0;
        $previousAmount = $previous['total_amount']['value'] ?? 0;

        if ($previousAmount == 0) {
            return $currentAmount > 0 ? 100.0 : 0.0;
        }

        return (($currentAmount - $previousAmount) / $previousAmount) * 100;
    }

    // Summary Methods
    /**
     * Get a comprehensive summary of donation metrics.
     */
    /**
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'entity_id' => $this->getId(),
            'donations' => [
                'total_count' => $this->getTotalDonations(),
                'total_amount' => $this->getTotalAmount(),
                'average_amount' => $this->getAverageDonation(),
                'min_amount' => $this->getMinDonation(),
                'max_amount' => $this->getMaxDonation(),
                'unique_donors' => $this->getUniqueDonors(),
            ],
            'trends' => [
                'has_data' => $this->hasTrendData(),
                'data_points' => $this->getTrendDataPoints(),
                'interval' => $this->getTrendInterval(),
            ],
            'segmentation' => [
                'has_data' => $this->hasSegmentationData(),
                'amount_segments_count' => count($this->getAmountSegments()),
                'frequency_segments_count' => count($this->getFrequencySegments()),
                'payment_methods_count' => count($this->getPaymentMethodSegments()),
            ],
            'retention' => [
                'has_data' => $this->hasRetentionData(),
                'retention_rate' => $this->getRetentionRate(),
                'returning_donors' => $this->getReturningDonors(),
                'avg_days_to_return' => $this->getAverageDaysToReturn(),
            ],
            'patterns' => [
                'has_temporal_data' => $this->hasTemporalPatterns(),
                'hourly_data_points' => count($this->getHourlyPattern()),
                'weekly_data_points' => count($this->getWeeklyPattern()),
            ],
            'comparisons' => [
                'has_data' => $this->hasComparisonData(),
                'donation_growth_rate' => $this->getDonationGrowthRate(),
                'amount_growth_rate' => $this->getAmountGrowthRate(),
            ],
            'top_donors_count' => $this->getTopDonorCount(),
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
            'total_donations' => $this->getTotalDonations(),
            'total_amount' => $this->getTotalAmount(),
            'average_donation' => $this->getAverageDonation(),
            'unique_donors' => $this->getUniqueDonors(),
            'retention_rate' => $this->getRetentionRate(),
            'donation_growth_rate' => $this->getDonationGrowthRate(),
            'amount_growth_rate' => $this->getAmountGrowthRate(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAnalyticsArray(): array
    {
        return [
            'donation_metrics' => [
                'entity_id' => $this->getId(),
                'summary' => $this->getSummary(),
                'kpis' => $this->getKPIs(),
                'statistics' => $this->getStatistics(),
                'trends' => $this->getTrends(),
                'top_donors' => $this->getTopDonors(),
                'segmentation' => $this->getSegmentation(),
                'geographic' => $this->getGeographicMetrics(),
                'temporal' => $this->getTemporalPatterns(),
                'retention' => $this->getRetentionMetrics(),
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
