<?php

declare(strict_types=1);

namespace Modules\Donation\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Donation history read model optimized for displaying user's donation history and patterns.
 */
final class DonationHistoryReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        array $data,
        ?string $version = null
    ) {
        parent::__construct(0, $data, $version); // History doesn't have single ID
        $this->setCacheTtl(1200); // 20 minutes for donation history
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'donation_history',
            'user:' . $this->getUserId(),
            'donations',
        ]);
    }

    // User Information
    public function getUserId(): int
    {
        return (int) $this->get('user_id', 0);
    }

    public function getUserName(): ?string
    {
        return $this->get('user_name');
    }

    public function getUserEmail(): ?string
    {
        return $this->get('user_email');
    }

    // Pagination Information
    public function getCurrentPage(): int
    {
        return (int) $this->get('current_page', 1);
    }

    public function getPerPage(): int
    {
        return (int) $this->get('per_page', 15);
    }

    public function getTotal(): int
    {
        return (int) $this->get('total', 0);
    }

    public function getLastPage(): int
    {
        return (int) $this->get('last_page', 1);
    }

    public function hasMorePages(): bool
    {
        return $this->getCurrentPage() < $this->getLastPage();
    }

    // Donations Data
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDonations(): array
    {
        return $this->get('donations', []);
    }

    public function getDonationCount(): int
    {
        return count($this->getDonations());
    }

    public function isEmpty(): bool
    {
        return $this->getDonationCount() === 0;
    }

    // Summary Statistics
    public function getTotalDonated(): float
    {
        return (float) $this->get('total_donated', 0);
    }

    public function getTotalDonationCount(): int
    {
        return (int) $this->get('total_donation_count', 0);
    }

    public function getAverageDonationAmount(): float
    {
        $count = $this->getTotalDonationCount();
        if ($count <= 0) {
            return 0.0;
        }

        return $this->getTotalDonated() / $count;
    }

    public function getLargestDonationAmount(): float
    {
        return (float) $this->get('largest_donation_amount', 0);
    }

    public function getSmallestDonationAmount(): float
    {
        return (float) $this->get('smallest_donation_amount', 0);
    }

    public function getTotalRefundedAmount(): float
    {
        return (float) $this->get('total_refunded_amount', 0);
    }

    public function getNetDonatedAmount(): float
    {
        return $this->getTotalDonated() - $this->getTotalRefundedAmount();
    }

    // Campaign Statistics
    public function getCampaignsSupported(): int
    {
        return (int) $this->get('campaigns_supported', 0);
    }

    public function getOrganizationsSupported(): int
    {
        return (int) $this->get('organizations_supported', 0);
    }

    public function getCategoriesSupported(): int
    {
        return (int) $this->get('categories_supported', 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopSupportedCampaigns(): array
    {
        return $this->get('top_supported_campaigns', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopSupportedOrganizations(): array
    {
        return $this->get('top_supported_organizations', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopSupportedCategories(): array
    {
        return $this->get('top_supported_categories', []);
    }

    // Donation Patterns
    /**
     * @return array<string, mixed>
     */
    public function getMonthlyDonationPattern(): array
    {
        return $this->get('monthly_donation_pattern', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getYearlyDonationPattern(): array
    {
        return $this->get('yearly_donation_pattern', []);
    }

    public function getMostActiveMonth(): ?string
    {
        $pattern = $this->getMonthlyDonationPattern();
        if ($pattern === []) {
            return null;
        }

        $maxValue = max($pattern);
        $keys = array_keys($pattern, $maxValue);

        return $keys[0] ?? null;
    }

    public function getAverageDonationsPerMonth(): float
    {
        return (float) $this->get('average_donations_per_month', 0);
    }

    public function getAverageDonationAmountPerMonth(): float
    {
        return (float) $this->get('average_donation_amount_per_month', 0);
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

    public function hasRecurringDonations(): bool
    {
        return $this->getRecurringDonationsCount() > 0;
    }

    public function getRecurringPercentage(): float
    {
        $total = $this->getTotalDonationCount();
        if ($total <= 0) {
            return 0.0;
        }

        return ($this->getRecurringDonationsCount() / $total) * 100;
    }

    // Status Breakdown
    public function getCompletedDonationsCount(): int
    {
        return (int) $this->get('completed_donations_count', 0);
    }

    public function getPendingDonationsCount(): int
    {
        return (int) $this->get('pending_donations_count', 0);
    }

    public function getFailedDonationsCount(): int
    {
        return (int) $this->get('failed_donations_count', 0);
    }

    public function getRefundedDonationsCount(): int
    {
        return (int) $this->get('refunded_donations_count', 0);
    }

    public function getSuccessRate(): float
    {
        $total = $this->getTotalDonationCount();
        if ($total <= 0) {
            return 0.0;
        }

        return ($this->getCompletedDonationsCount() / $total) * 100;
    }

    // Payment Methods
    /**
     * @return array<string, mixed>
     */
    public function getPaymentMethodBreakdown(): array
    {
        return $this->get('payment_method_breakdown', []);
    }

    public function getMostUsedPaymentMethod(): ?string
    {
        $breakdown = $this->getPaymentMethodBreakdown();
        if ($breakdown === []) {
            return null;
        }

        return array_search(max($breakdown), $breakdown) ?: null;
    }

    // Corporate and Matching
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

    public function getTotalMatchingReceived(): float
    {
        return (float) $this->get('total_matching_received', 0);
    }

    public function hasCorporateDonations(): bool
    {
        return $this->getCorporateDonationsCount() > 0;
    }

    public function hasMatchedDonations(): bool
    {
        return $this->getMatchedDonationsCount() > 0;
    }

    // Time Ranges
    public function getFirstDonationDate(): ?string
    {
        return $this->get('first_donation_date');
    }

    public function getLastDonationDate(): ?string
    {
        return $this->get('last_donation_date');
    }

    public function getDonatingPeriodInDays(): int
    {
        $first = $this->getFirstDonationDate();
        $last = $this->getLastDonationDate();

        if (! $first || ! $last) {
            return 0;
        }

        return (int) ((strtotime($last) - strtotime($first)) / (60 * 60 * 24));
    }

    public function getDaysSinceLastDonation(): int
    {
        $last = $this->getLastDonationDate();
        if (! $last) {
            return 0;
        }

        return (int) ((time() - strtotime($last)) / (60 * 60 * 24));
    }

    // Filter Information
    /**
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return $this->get('filters', []);
    }

    public function getDateFromFilter(): ?string
    {
        $filters = $this->getFilters();

        return $filters['date_from'] ?? null;
    }

    public function getDateToFilter(): ?string
    {
        $filters = $this->getFilters();

        return $filters['date_to'] ?? null;
    }

    public function getStatusFilter(): ?string
    {
        $filters = $this->getFilters();

        return $filters['status'] ?? null;
    }

    public function getCampaignFilter(): ?int
    {
        $filters = $this->getFilters();
        $campaignId = $filters['campaign_id'] ?? null;

        return $campaignId ? (int) $campaignId : null;
    }

    public function hasActiveFilters(): bool
    {
        $filters = $this->getFilters();
        unset($filters['page'], $filters['per_page']);

        return $filters !== [];
    }

    // Achievements and Milestones
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMilestones(): array
    {
        return $this->get('milestones', []);
    }

    public function hasMilestones(): bool
    {
        return $this->getMilestones() !== [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getNextMilestone(): ?array
    {
        $milestones = $this->getMilestones();
        foreach ($milestones as $milestone) {
            if (! ($milestone['achieved'] ?? false)) {
                return $milestone;
            }
        }

        return null;
    }

    // Donor Level/Status
    public function getDonorLevel(): string
    {
        $total = $this->getTotalDonated();

        return match (true) {
            $total >= 10000 => 'platinum',
            $total >= 5000 => 'gold',
            $total >= 1000 => 'silver',
            $total >= 100 => 'bronze',
            default => 'supporter',
        };
    }

    public function getDonorLevelLabel(): string
    {
        return match ($this->getDonorLevel()) {
            'platinum' => 'Platinum Donor',
            'gold' => 'Gold Donor',
            'silver' => 'Silver Donor',
            'bronze' => 'Bronze Donor',
            'supporter' => 'Supporter',
            default => 'Donor',
        };
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user' => [
                'id' => $this->getUserId(),
                'name' => $this->getUserName(),
                'email' => $this->getUserEmail(),
                'donor_level' => $this->getDonorLevel(),
                'donor_level_label' => $this->getDonorLevelLabel(),
            ],
            'pagination' => [
                'current_page' => $this->getCurrentPage(),
                'per_page' => $this->getPerPage(),
                'total' => $this->getTotal(),
                'last_page' => $this->getLastPage(),
                'has_more_pages' => $this->hasMorePages(),
            ],
            'donations' => $this->getDonations(),
            'summary' => [
                'total_donated' => $this->getTotalDonated(),
                'total_donation_count' => $this->getTotalDonationCount(),
                'average_donation_amount' => $this->getAverageDonationAmount(),
                'largest_donation_amount' => $this->getLargestDonationAmount(),
                'smallest_donation_amount' => $this->getSmallestDonationAmount(),
                'total_refunded_amount' => $this->getTotalRefundedAmount(),
                'net_donated_amount' => $this->getNetDonatedAmount(),
            ],
            'campaigns' => [
                'campaigns_supported' => $this->getCampaignsSupported(),
                'organizations_supported' => $this->getOrganizationsSupported(),
                'categories_supported' => $this->getCategoriesSupported(),
                'top_supported_campaigns' => $this->getTopSupportedCampaigns(),
                'top_supported_organizations' => $this->getTopSupportedOrganizations(),
                'top_supported_categories' => $this->getTopSupportedCategories(),
            ],
            'patterns' => [
                'monthly_donation_pattern' => $this->getMonthlyDonationPattern(),
                'yearly_donation_pattern' => $this->getYearlyDonationPattern(),
                'most_active_month' => $this->getMostActiveMonth(),
                'average_donations_per_month' => $this->getAverageDonationsPerMonth(),
                'average_donation_amount_per_month' => $this->getAverageDonationAmountPerMonth(),
            ],
            'recurring' => [
                'recurring_donations_count' => $this->getRecurringDonationsCount(),
                'recurring_donations_total' => $this->getRecurringDonationsTotal(),
                'active_recurring_donations' => $this->getActiveRecurringDonations(),
                'has_recurring_donations' => $this->hasRecurringDonations(),
                'recurring_percentage' => $this->getRecurringPercentage(),
            ],
            'status_breakdown' => [
                'completed' => $this->getCompletedDonationsCount(),
                'pending' => $this->getPendingDonationsCount(),
                'failed' => $this->getFailedDonationsCount(),
                'refunded' => $this->getRefundedDonationsCount(),
                'success_rate' => $this->getSuccessRate(),
            ],
            'payment_methods' => $this->getPaymentMethodBreakdown(),
            'corporate' => [
                'corporate_donations_count' => $this->getCorporateDonationsCount(),
                'corporate_donations_total' => $this->getCorporateDonationsTotal(),
                'matched_donations_count' => $this->getMatchedDonationsCount(),
                'matched_donations_total' => $this->getMatchedDonationsTotal(),
                'total_matching_received' => $this->getTotalMatchingReceived(),
                'has_corporate_donations' => $this->hasCorporateDonations(),
                'has_matched_donations' => $this->hasMatchedDonations(),
            ],
            'timeline' => [
                'first_donation_date' => $this->getFirstDonationDate(),
                'last_donation_date' => $this->getLastDonationDate(),
                'donating_period_days' => $this->getDonatingPeriodInDays(),
                'days_since_last_donation' => $this->getDaysSinceLastDonation(),
            ],
            'achievements' => [
                'milestones' => $this->getMilestones(),
                'next_milestone' => $this->getNextMilestone(),
            ],
            'filters' => $this->getFilters(),
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
            'total_donated' => $this->getTotalDonated(),
            'total_donations' => $this->getTotalDonationCount(),
            'average_donation' => $this->getAverageDonationAmount(),
            'campaigns_supported' => $this->getCampaignsSupported(),
            'success_rate' => $this->getSuccessRate(),
            'donor_level' => $this->getDonorLevel(),
            'donor_level_label' => $this->getDonorLevelLabel(),
            'days_since_last_donation' => $this->getDaysSinceLastDonation(),
            'has_recurring_donations' => $this->hasRecurringDonations(),
        ];
    }
}
