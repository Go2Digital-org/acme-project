<?php

declare(strict_types=1);

namespace Modules\Donation\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Donation report read model optimized for donation summaries and payment gateway stats.
 */
class DonationReportReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        string $reportId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($reportId, $data, $version);
        $this->setCacheTtl(900); // 15 minutes for donation reports
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'donation_reports',
            'donations',
            'organization:' . $this->getOrganizationId(),
        ]);
    }

    // Report Metadata
    public function getReportId(): string
    {
        return (string) $this->id;
    }

    public function getReportType(): string
    {
        return $this->get('report_type', 'summary');
    }

    /**
     * @return array<string, mixed>
     */
    public function getDateRange(): array
    {
        return $this->get('date_range', []);
    }

    public function getStartDate(): ?string
    {
        return $this->get('start_date');
    }

    public function getEndDate(): ?string
    {
        return $this->get('end_date');
    }

    public function getOrganizationId(): ?int
    {
        $orgId = $this->get('organization_id');

        return $orgId ? (int) $orgId : null;
    }

    public function getOrganizationName(): ?string
    {
        return $this->get('organization_name');
    }

    public function getCampaignId(): ?int
    {
        $campaignId = $this->get('campaign_id');

        return $campaignId ? (int) $campaignId : null;
    }

    public function getCampaignTitle(): ?string
    {
        return $this->get('campaign_title');
    }

    // Financial Summary
    public function getTotalAmount(): float
    {
        return (float) $this->get('total_amount', 0);
    }

    public function getTotalDonations(): int
    {
        return (int) $this->get('total_donations', 0);
    }

    public function getAverageDonationAmount(): float
    {
        $total = $this->getTotalDonations();
        if ($total <= 0) {
            return 0.0;
        }

        return $this->getTotalAmount() / $total;
    }

    public function getMinDonationAmount(): float
    {
        return (float) $this->get('min_donation_amount', 0);
    }

    public function getMaxDonationAmount(): float
    {
        return (float) $this->get('max_donation_amount', 0);
    }

    public function getMedianDonationAmount(): float
    {
        return (float) $this->get('median_donation_amount', 0);
    }

    // Donor Statistics
    public function getUniqueDonors(): int
    {
        return (int) $this->get('unique_donors', 0);
    }

    public function getNewDonors(): int
    {
        return (int) $this->get('new_donors', 0);
    }

    public function getReturningDonors(): int
    {
        return (int) $this->get('returning_donors', 0);
    }

    public function getAnonymousDonors(): int
    {
        return (int) $this->get('anonymous_donors', 0);
    }

    public function getAnonymousDonationsAmount(): float
    {
        return (float) $this->get('anonymous_donations_amount', 0);
    }

    // Donation Status Breakdown
    public function getCompletedDonations(): int
    {
        return (int) $this->get('completed_donations', 0);
    }

    public function getCompletedAmount(): float
    {
        return (float) $this->get('completed_amount', 0);
    }

    public function getPendingDonations(): int
    {
        return (int) $this->get('pending_donations', 0);
    }

    public function getPendingAmount(): float
    {
        return (float) $this->get('pending_amount', 0);
    }

    public function getFailedDonations(): int
    {
        return (int) $this->get('failed_donations', 0);
    }

    public function getFailedAmount(): float
    {
        return (float) $this->get('failed_amount', 0);
    }

    public function getRefundedDonations(): int
    {
        return (int) $this->get('refunded_donations', 0);
    }

    public function getRefundedAmount(): float
    {
        return (float) $this->get('refunded_amount', 0);
    }

    public function getNetAmount(): float
    {
        return $this->getCompletedAmount() - $this->getRefundedAmount();
    }

    // Recurring Donations
    public function getRecurringDonations(): int
    {
        return (int) $this->get('recurring_donations', 0);
    }

    public function getRecurringAmount(): float
    {
        return (float) $this->get('recurring_amount', 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRecurringFrequencyBreakdown(): array
    {
        return $this->get('recurring_frequency_breakdown', []);
    }

    // Corporate Matching
    public function getCorporateMatchAmount(): float
    {
        return (float) $this->get('corporate_match_amount', 0);
    }

    public function getCorporateMatchedDonations(): int
    {
        return (int) $this->get('corporate_matched_donations', 0);
    }

    public function getTotalWithMatching(): float
    {
        return $this->getTotalAmount() + $this->getCorporateMatchAmount();
    }

    // Payment Gateway Statistics
    /**
     * @return array<string, mixed>
     */
    public function getPaymentGatewayStats(): array
    {
        return $this->get('payment_gateway_stats', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPaymentMethodStats(): array
    {
        return $this->get('payment_method_stats', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSuccessRateByGateway(): array
    {
        return $this->get('success_rate_by_gateway', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getGatewayFeesSummary(): array
    {
        return $this->get('gateway_fees_summary', []);
    }

    public function getTotalGatewayFees(): float
    {
        return (float) $this->get('total_gateway_fees', 0);
    }

    // Time-based Analysis
    /**
     * @return array<string, mixed>
     */
    public function getDonationsByDay(): array
    {
        return $this->get('donations_by_day', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDonationsByWeek(): array
    {
        return $this->get('donations_by_week', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDonationsByMonth(): array
    {
        return $this->get('donations_by_month', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDonationsByHour(): array
    {
        return $this->get('donations_by_hour', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPeakDonationTime(): array
    {
        return $this->get('peak_donation_time', []);
    }

    // Currency Breakdown
    /**
     * @return array<string, mixed>
     */
    public function getCurrencyBreakdown(): array
    {
        return $this->get('currency_breakdown', []);
    }

    public function getPrimaryCurrency(): string
    {
        return $this->get('primary_currency', 'USD');
    }

    // Performance Metrics
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

        return ($this->getFailedDonations() / $total) * 100;
    }

    public function getRefundRate(): float
    {
        $completed = $this->getCompletedDonations();
        if ($completed <= 0) {
            return 0.0;
        }

        return ($this->getRefundedDonations() / $completed) * 100;
    }

    public function getAnonymousRate(): float
    {
        $total = $this->getTotalDonations();
        if ($total <= 0) {
            return 0.0;
        }

        return ($this->getAnonymousDonors() / $total) * 100;
    }

    // Top Donors (anonymized for privacy)
    /**
     * @return array<int, float>
     */
    public function getTopDonationAmounts(): array
    {
        return $this->get('top_donation_amounts', []);
    }

    public function getDonorRetentionRate(): float
    {
        $unique = $this->getUniqueDonors();
        if ($unique <= 0) {
            return 0.0;
        }

        return ($this->getReturningDonors() / $unique) * 100;
    }

    // Timestamps
    public function getReportGeneratedAt(): ?string
    {
        return $this->get('report_generated_at');
    }

    public function getLastDonationAt(): ?string
    {
        return $this->get('last_donation_at');
    }

    public function getFirstDonationAt(): ?string
    {
        return $this->get('first_donation_at');
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toReportArray(): array
    {
        return [
            'metadata' => [
                'report_id' => $this->getReportId(),
                'report_type' => $this->getReportType(),
                'date_range' => $this->getDateRange(),
                'organization_id' => $this->getOrganizationId(),
                'organization_name' => $this->getOrganizationName(),
                'campaign_id' => $this->getCampaignId(),
                'campaign_title' => $this->getCampaignTitle(),
                'generated_at' => $this->getReportGeneratedAt(),
            ],
            'summary' => [
                'total_amount' => $this->getTotalAmount(),
                'total_donations' => $this->getTotalDonations(),
                'unique_donors' => $this->getUniqueDonors(),
                'average_donation_amount' => $this->getAverageDonationAmount(),
                'net_amount' => $this->getNetAmount(),
                'total_with_matching' => $this->getTotalWithMatching(),
            ],
            'donations_breakdown' => [
                'completed' => [
                    'count' => $this->getCompletedDonations(),
                    'amount' => $this->getCompletedAmount(),
                ],
                'pending' => [
                    'count' => $this->getPendingDonations(),
                    'amount' => $this->getPendingAmount(),
                ],
                'failed' => [
                    'count' => $this->getFailedDonations(),
                    'amount' => $this->getFailedAmount(),
                ],
                'refunded' => [
                    'count' => $this->getRefundedDonations(),
                    'amount' => $this->getRefundedAmount(),
                ],
            ],
            'donor_stats' => [
                'unique_donors' => $this->getUniqueDonors(),
                'new_donors' => $this->getNewDonors(),
                'returning_donors' => $this->getReturningDonors(),
                'anonymous_donors' => $this->getAnonymousDonors(),
                'retention_rate' => $this->getDonorRetentionRate(),
            ],
            'recurring_donations' => [
                'count' => $this->getRecurringDonations(),
                'amount' => $this->getRecurringAmount(),
                'frequency_breakdown' => $this->getRecurringFrequencyBreakdown(),
            ],
            'corporate_matching' => [
                'matched_donations' => $this->getCorporateMatchedDonations(),
                'match_amount' => $this->getCorporateMatchAmount(),
                'total_with_matching' => $this->getTotalWithMatching(),
            ],
            'payment_analysis' => [
                'gateway_stats' => $this->getPaymentGatewayStats(),
                'method_stats' => $this->getPaymentMethodStats(),
                'success_rates' => $this->getSuccessRateByGateway(),
                'total_fees' => $this->getTotalGatewayFees(),
            ],
            'performance_metrics' => [
                'success_rate' => $this->getSuccessRate(),
                'failure_rate' => $this->getFailureRate(),
                'refund_rate' => $this->getRefundRate(),
                'anonymous_rate' => $this->getAnonymousRate(),
            ],
            'time_analysis' => [
                'by_day' => $this->getDonationsByDay(),
                'by_hour' => $this->getDonationsByHour(),
                'peak_time' => $this->getPeakDonationTime(),
                'first_donation' => $this->getFirstDonationAt(),
                'last_donation' => $this->getLastDonationAt(),
            ],
        ];
    }
}
