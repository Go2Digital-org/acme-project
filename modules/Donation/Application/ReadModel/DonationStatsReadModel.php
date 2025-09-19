<?php

declare(strict_types=1);

namespace Modules\Donation\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Donation statistics read model optimized for analytics, reporting, and dashboard data.
 */
final class DonationStatsReadModel extends AbstractReadModel
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
     * @return array<string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'donation_stats',
            'statistics',
            'analytics',
            'dashboard',
        ]);
    }

    // Overall Donation Statistics
    public function getTotalDonations(): int
    {
        return (int) $this->get('total_donations', 0);
    }

    public function getTotalAmountRaised(): float
    {
        return (float) $this->get('total_amount_raised', 0);
    }

    public function getTotalUniqueDonors(): int
    {
        return (int) $this->get('total_unique_donors', 0);
    }

    public function getAverageDonationAmount(): float
    {
        return (float) $this->get('average_donation_amount', 0);
    }

    public function getMedianDonationAmount(): float
    {
        return (float) $this->get('median_donation_amount', 0);
    }

    public function getLargestDonationAmount(): float
    {
        return (float) $this->get('largest_donation_amount', 0);
    }

    public function getSmallestDonationAmount(): float
    {
        return (float) $this->get('smallest_donation_amount', 0);
    }

    // Donation Status Breakdown
    public function getCompletedDonations(): int
    {
        return (int) $this->get('completed_donations', 0);
    }

    public function getPendingDonations(): int
    {
        return (int) $this->get('pending_donations', 0);
    }

    public function getProcessingDonations(): int
    {
        return (int) $this->get('processing_donations', 0);
    }

    public function getFailedDonations(): int
    {
        return (int) $this->get('failed_donations', 0);
    }

    public function getCancelledDonations(): int
    {
        return (int) $this->get('cancelled_donations', 0);
    }

    public function getRefundedDonations(): int
    {
        return (int) $this->get('refunded_donations', 0);
    }

    // Success and Conversion Rates
    public function getSuccessRate(): float
    {
        $total = $this->getTotalDonations();
        if ($total <= 0) {
            return 0.0;
        }

        return ($this->getCompletedDonations() / $total) * 100;
    }

    public function getFailureRate(): float
    {
        $total = $this->getTotalDonations();
        if ($total <= 0) {
            return 0.0;
        }

        return (($this->getFailedDonations() + $this->getCancelledDonations()) / $total) * 100;
    }

    public function getRefundRate(): float
    {
        $completed = $this->getCompletedDonations();
        if ($completed <= 0) {
            return 0.0;
        }

        return ($this->getRefundedDonations() / $completed) * 100;
    }

    // Time-based Statistics
    public function getDonationsToday(): int
    {
        return (int) $this->get('donations_today', 0);
    }

    public function getDonationsThisWeek(): int
    {
        return (int) $this->get('donations_this_week', 0);
    }

    public function getDonationsThisMonth(): int
    {
        return (int) $this->get('donations_this_month', 0);
    }

    public function getDonationsThisYear(): int
    {
        return (int) $this->get('donations_this_year', 0);
    }

    public function getAmountRaisedToday(): float
    {
        return (float) $this->get('amount_raised_today', 0);
    }

    public function getAmountRaisedThisWeek(): float
    {
        return (float) $this->get('amount_raised_this_week', 0);
    }

    public function getAmountRaisedThisMonth(): float
    {
        return (float) $this->get('amount_raised_this_month', 0);
    }

    public function getAmountRaisedThisYear(): float
    {
        return (float) $this->get('amount_raised_this_year', 0);
    }

    // Growth Rates
    public function getDailyGrowthRate(): float
    {
        return (float) $this->get('daily_growth_rate', 0);
    }

    public function getWeeklyGrowthRate(): float
    {
        return (float) $this->get('weekly_growth_rate', 0);
    }

    public function getMonthlyGrowthRate(): float
    {
        return (float) $this->get('monthly_growth_rate', 0);
    }

    public function getYearlyGrowthRate(): float
    {
        return (float) $this->get('yearly_growth_rate', 0);
    }

    // Recurring Donations
    public function getRecurringDonationsCount(): int
    {
        return (int) $this->get('recurring_donations_count', 0);
    }

    public function getRecurringDonationsTotal(): float
    {
        return (float) $this->get('recurring_donations_total', 0);
    }

    public function getActiveRecurringDonations(): int
    {
        return (int) $this->get('active_recurring_donations', 0);
    }

    public function getRecurringConversionRate(): float
    {
        $total = $this->getTotalDonations();
        if ($total <= 0) {
            return 0.0;
        }

        return ($this->getRecurringDonationsCount() / $total) * 100;
    }

    // Corporate and Matching Donations
    public function getCorporateDonationsCount(): int
    {
        return (int) $this->get('corporate_donations_count', 0);
    }

    public function getCorporateDonationsTotal(): float
    {
        return (float) $this->get('corporate_donations_total', 0);
    }

    public function getMatchedDonationsCount(): int
    {
        return (int) $this->get('matched_donations_count', 0);
    }

    public function getMatchedDonationsTotal(): float
    {
        return (float) $this->get('matched_donations_total', 0);
    }

    public function getTotalMatchingAmount(): float
    {
        return (float) $this->get('total_matching_amount', 0);
    }

    public function getMatchingRate(): float
    {
        $total = $this->getTotalAmountRaised();
        if ($total <= 0) {
            return 0.0;
        }

        return ($this->getTotalMatchingAmount() / $total) * 100;
    }

    // Anonymous Donations
    public function getAnonymousDonationsCount(): int
    {
        return (int) $this->get('anonymous_donations_count', 0);
    }

    public function getAnonymousDonationsTotal(): float
    {
        return (float) $this->get('anonymous_donations_total', 0);
    }

    public function getAnonymousRate(): float
    {
        $total = $this->getTotalDonations();
        if ($total <= 0) {
            return 0.0;
        }

        return ($this->getAnonymousDonationsCount() / $total) * 100;
    }

    // Payment Method Statistics
    /**
     * @return array<string, mixed>
     */
    public function getPaymentMethodStats(): array
    {
        return $this->get('payment_method_stats', []);
    }

    public function getMostPopularPaymentMethod(): ?string
    {
        $stats = $this->getPaymentMethodStats();
        if ($stats === []) {
            return null;
        }

        $maxCount = 0;
        $topMethod = null;

        foreach ($stats as $method => $data) {
            if (($data['count'] ?? 0) > $maxCount) {
                $maxCount = $data['count'];
                $topMethod = $method;
            }
        }

        return $topMethod;
    }

    // Processing Fees
    public function getTotalProcessingFees(): float
    {
        return (float) $this->get('total_processing_fees', 0);
    }

    public function getNetAmountRaised(): float
    {
        return $this->getTotalAmountRaised() - $this->getTotalProcessingFees();
    }

    public function getAverageProcessingFee(): float
    {
        return (float) $this->get('average_processing_fee', 0);
    }

    public function getProcessingFeeRate(): float
    {
        $total = $this->getTotalAmountRaised();
        if ($total <= 0) {
            return 0.0;
        }

        return ($this->getTotalProcessingFees() / $total) * 100;
    }

    // Top Performers
    /**
     * @return array<string, mixed>
     */
    public function getTopDonorsByCampaign(): array
    {
        return $this->get('top_donors_by_campaign', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTopDonorsByAmount(): array
    {
        return $this->get('top_donors_by_amount', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTopCampaignsByDonations(): array
    {
        return $this->get('top_campaigns_by_donations', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTopOrganizationsByDonations(): array
    {
        return $this->get('top_organizations_by_donations', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getLargestDonations(): array
    {
        return $this->get('largest_donations', []);
    }

    // Trends and Patterns

    /**
     * @return array<string, mixed>
     */
    public function getHourlyPattern(): array
    {
        return $this->get('hourly_pattern', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDailyPattern(): array
    {
        return $this->get('daily_pattern', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getWeeklyPattern(): array
    {
        return $this->get('weekly_pattern', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMonthlyPattern(): array
    {
        return $this->get('monthly_pattern', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMonthlyTrend(): array
    {
        return $this->get('monthly_trend', []);
    }

    public function getPeakDonationHour(): int
    {
        $pattern = $this->getHourlyPattern();
        if ($pattern === []) {
            return 0;
        }

        return (int) array_search(max($pattern), $pattern);
    }

    public function getPeakDonationDay(): string
    {
        $pattern = $this->getDailyPattern();
        if ($pattern === []) {
            return 'Monday';
        }

        return array_search(max($pattern), $pattern) ?: 'Monday';
    }

    // Donor Behavior
    public function getFirstTimeDonorsCount(): int
    {
        return (int) $this->get('first_time_donors_count', 0);
    }

    public function getReturnDonorsCount(): int
    {
        return (int) $this->get('return_donors_count', 0);
    }

    public function getRetentionRate(): float
    {
        $total = $this->getTotalUniqueDonors();
        if ($total <= 0) {
            return 0.0;
        }

        return ($this->getReturnDonorsCount() / $total) * 100;
    }

    public function getAverageDonationsPerDonor(): float
    {
        $uniqueDonors = $this->getTotalUniqueDonors();
        if ($uniqueDonors <= 0) {
            return 0.0;
        }

        return $this->getTotalDonations() / $uniqueDonors;
    }

    public function getLifetimeValuePerDonor(): float
    {
        $uniqueDonors = $this->getTotalUniqueDonors();
        if ($uniqueDonors <= 0) {
            return 0.0;
        }

        return $this->getTotalAmountRaised() / $uniqueDonors;
    }

    // Refund Statistics
    public function getTotalRefundedAmount(): float
    {
        return (float) $this->get('total_refunded_amount', 0);
    }

    public function getAverageRefundAmount(): float
    {
        return (float) $this->get('average_refund_amount', 0);
    }

    public function getRefundProcessingTime(): float
    {
        return (float) $this->get('average_refund_processing_time_hours', 0);
    }

    // Health Score and KPIs
    public function getDonationHealthScore(): float
    {
        // Calculate health score based on multiple factors
        $successRate = $this->getSuccessRate();
        $refundRate = 100 - $this->getRefundRate(); // Invert refund rate
        $retentionRate = $this->getRetentionRate();
        $growthRate = min(100, max(0, $this->getMonthlyGrowthRate() + 50)); // Normalize growth rate

        // Weighted average
        $healthScore = (
            $successRate * 0.3 +
            $refundRate * 0.2 +
            $retentionRate * 0.25 +
            $growthRate * 0.25
        );

        return min(100, $healthScore);
    }

    public function getDonationHealthStatus(): string
    {
        $score = $this->getDonationHealthScore();

        return match (true) {
            $score >= 80 => 'excellent',
            $score >= 60 => 'good',
            $score >= 40 => 'fair',
            $score >= 20 => 'poor',
            default => 'critical',
        };
    }

    // Alert Conditions
    public function getPendingDonationsValue(): float
    {
        return (float) $this->get('pending_donations_value', 0);
    }

    public function getFailedDonationsToday(): int
    {
        return (int) $this->get('failed_donations_today', 0);
    }

    public function getHighValueDonationsPendingCount(): int
    {
        return (int) $this->get('high_value_donations_pending', 0);
    }

    public function hasPaymentIssues(): bool
    {
        if ($this->getFailedDonationsToday() > 10) {
            return true;
        }

        return $this->getFailureRate() > 15;
    }

    public function hasProcessingDelays(): bool
    {
        return $this->getProcessingDonations() > 50;
    }

    // Formatted Output

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'totals' => [
                'donations' => $this->getTotalDonations(),
                'amount_raised' => $this->getTotalAmountRaised(),
                'unique_donors' => $this->getTotalUniqueDonors(),
                'average_donation_amount' => $this->getAverageDonationAmount(),
                'median_donation_amount' => $this->getMedianDonationAmount(),
                'largest_donation_amount' => $this->getLargestDonationAmount(),
                'smallest_donation_amount' => $this->getSmallestDonationAmount(),
            ],
            'status_breakdown' => [
                'completed' => $this->getCompletedDonations(),
                'pending' => $this->getPendingDonations(),
                'processing' => $this->getProcessingDonations(),
                'failed' => $this->getFailedDonations(),
                'cancelled' => $this->getCancelledDonations(),
                'refunded' => $this->getRefundedDonations(),
            ],
            'rates' => [
                'success_rate' => $this->getSuccessRate(),
                'failure_rate' => $this->getFailureRate(),
                'refund_rate' => $this->getRefundRate(),
                'recurring_conversion_rate' => $this->getRecurringConversionRate(),
                'retention_rate' => $this->getRetentionRate(),
                'anonymous_rate' => $this->getAnonymousRate(),
                'matching_rate' => $this->getMatchingRate(),
            ],
            'time_based' => [
                'donations_today' => $this->getDonationsToday(),
                'donations_this_week' => $this->getDonationsThisWeek(),
                'donations_this_month' => $this->getDonationsThisMonth(),
                'donations_this_year' => $this->getDonationsThisYear(),
                'amount_raised_today' => $this->getAmountRaisedToday(),
                'amount_raised_this_week' => $this->getAmountRaisedThisWeek(),
                'amount_raised_this_month' => $this->getAmountRaisedThisMonth(),
                'amount_raised_this_year' => $this->getAmountRaisedThisYear(),
            ],
            'growth' => [
                'daily_growth_rate' => $this->getDailyGrowthRate(),
                'weekly_growth_rate' => $this->getWeeklyGrowthRate(),
                'monthly_growth_rate' => $this->getMonthlyGrowthRate(),
                'yearly_growth_rate' => $this->getYearlyGrowthRate(),
            ],
            'recurring' => [
                'recurring_donations_count' => $this->getRecurringDonationsCount(),
                'recurring_donations_total' => $this->getRecurringDonationsTotal(),
                'active_recurring_donations' => $this->getActiveRecurringDonations(),
            ],
            'corporate' => [
                'corporate_donations_count' => $this->getCorporateDonationsCount(),
                'corporate_donations_total' => $this->getCorporateDonationsTotal(),
                'matched_donations_count' => $this->getMatchedDonationsCount(),
                'matched_donations_total' => $this->getMatchedDonationsTotal(),
                'total_matching_amount' => $this->getTotalMatchingAmount(),
            ],
            'anonymous' => [
                'anonymous_donations_count' => $this->getAnonymousDonationsCount(),
                'anonymous_donations_total' => $this->getAnonymousDonationsTotal(),
            ],
            'payment_methods' => $this->getPaymentMethodStats(),
            'processing_fees' => [
                'total_processing_fees' => $this->getTotalProcessingFees(),
                'net_amount_raised' => $this->getNetAmountRaised(),
                'average_processing_fee' => $this->getAverageProcessingFee(),
                'processing_fee_rate' => $this->getProcessingFeeRate(),
            ],
            'top_performers' => [
                'donors_by_campaign' => $this->getTopDonorsByCampaign(),
                'donors_by_amount' => $this->getTopDonorsByAmount(),
                'campaigns_by_donations' => $this->getTopCampaignsByDonations(),
                'organizations_by_donations' => $this->getTopOrganizationsByDonations(),
                'largest_donations' => $this->getLargestDonations(),
            ],
            'patterns' => [
                'hourly_pattern' => $this->getHourlyPattern(),
                'daily_pattern' => $this->getDailyPattern(),
                'weekly_pattern' => $this->getWeeklyPattern(),
                'monthly_pattern' => $this->getMonthlyPattern(),
                'monthly_trend' => $this->getMonthlyTrend(),
                'peak_donation_hour' => $this->getPeakDonationHour(),
                'peak_donation_day' => $this->getPeakDonationDay(),
            ],
            'donor_behavior' => [
                'first_time_donors_count' => $this->getFirstTimeDonorsCount(),
                'return_donors_count' => $this->getReturnDonorsCount(),
                'average_donations_per_donor' => $this->getAverageDonationsPerDonor(),
                'lifetime_value_per_donor' => $this->getLifetimeValuePerDonor(),
            ],
            'refunds' => [
                'total_refunded_amount' => $this->getTotalRefundedAmount(),
                'average_refund_amount' => $this->getAverageRefundAmount(),
                'refund_processing_time_hours' => $this->getRefundProcessingTime(),
            ],
            'health' => [
                'score' => $this->getDonationHealthScore(),
                'status' => $this->getDonationHealthStatus(),
            ],
            'alerts' => [
                'pending_donations_value' => $this->getPendingDonationsValue(),
                'failed_donations_today' => $this->getFailedDonationsToday(),
                'high_value_donations_pending' => $this->getHighValueDonationsPendingCount(),
                'has_payment_issues' => $this->hasPaymentIssues(),
                'has_processing_delays' => $this->hasProcessingDelays(),
            ],
        ];
    }

    /**
     * Get summary data for dashboard widgets
     */
    /**
     * @return array<string, mixed>
     */
    public function toDashboardSummary(): array
    {
        return [
            'totals' => [
                'donations' => $this->getTotalDonations(),
                'amount_raised' => $this->getTotalAmountRaised(),
                'unique_donors' => $this->getTotalUniqueDonors(),
                'average_donation' => $this->getAverageDonationAmount(),
            ],
            'today' => [
                'donations' => $this->getDonationsToday(),
                'amount_raised' => $this->getAmountRaisedToday(),
            ],
            'performance' => [
                'success_rate' => $this->getSuccessRate(),
                'retention_rate' => $this->getRetentionRate(),
                'monthly_growth_rate' => $this->getMonthlyGrowthRate(),
            ],
            'health' => [
                'score' => $this->getDonationHealthScore(),
                'status' => $this->getDonationHealthStatus(),
            ],
            'alerts' => [
                'has_payment_issues' => $this->hasPaymentIssues(),
                'has_processing_delays' => $this->hasProcessingDelays(),
                'pending_value' => $this->getPendingDonationsValue(),
            ],
        ];
    }
}
