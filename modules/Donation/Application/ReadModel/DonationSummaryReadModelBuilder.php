<?php

declare(strict_types=1);

namespace Modules\Donation\Application\ReadModel;

use Carbon\Carbon;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;

/**
 * Builder for creating optimized DonationSummaryReadModel instances.
 * Handles aggregation of donation statistics with performance optimizations.
 */
final readonly class DonationSummaryReadModelBuilder
{
    public function __construct(
        private DonationRepositoryInterface $donationRepository
    ) {}

    /**
     * Build a complete donation summary.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function build(array $filters = [], string $locale = 'en', string $baseCurrency = 'USD'): DonationSummaryReadModel
    {
        $data = [
            // Overall statistics
            'total_raised' => $this->getTotalRaised($filters),
            'total_donations' => $this->getTotalDonations($filters),
            'unique_donors' => $this->getUniqueDonors($filters),
            'average_donation' => $this->getAverageDonation($filters),
            'median_donation' => $this->getMedianDonation($filters),
            'largest_donation' => $this->getLargestDonation($filters),
            'smallest_donation' => $this->getSmallestDonation($filters),

            // Time-based metrics
            'daily_totals' => $this->getDailyTotals($filters),
            'weekly_totals' => $this->getWeeklyTotals($filters),
            'monthly_totals' => $this->getMonthlyTotals($filters),
            'daily_growth_rate' => $this->getDailyGrowthRate($filters),
            'weekly_growth_rate' => $this->getWeeklyGrowthRate($filters),
            'monthly_growth_rate' => $this->getMonthlyGrowthRate($filters),

            // Top performers
            'top_donors' => $this->getTopDonors($filters),
            'top_campaigns' => $this->getTopCampaigns($filters),

            // Breakdowns
            'payment_method_breakdown' => $this->getPaymentMethodBreakdown($filters),
            'currency_breakdown' => $this->getCurrencyBreakdown($filters),

            // Status counts
            'donations_by_status' => $this->getDonationCountByStatus($filters),

            // Recurring donations
            'recurring_donations_count' => $this->getRecurringDonationsCount($filters),
            'recurring_donations_amount' => $this->getRecurringDonationsAmount($filters),

            // Corporate vs Individual
            'corporate_donations_amount' => $this->getCorporateDonationsAmount($filters),
            'individual_donations_amount' => $this->getIndividualDonationsAmount($filters),
        ];

        $version = $this->generateVersion($filters);

        return new DonationSummaryReadModel($data, $version, $locale, $baseCurrency);
    }

    /**
     * Build summary for a specific date range.
     */
    public function buildForDateRange(Carbon $startDate, Carbon $endDate, string $locale = 'en', string $baseCurrency = 'USD'): DonationSummaryReadModel
    {
        $filters = [
            'date_from' => $startDate->toDateString(),
            'date_to' => $endDate->toDateString(),
        ];

        return $this->build($filters, $locale, $baseCurrency);
    }

    /**
     * Build summary for a specific campaign.
     */
    public function buildForCampaign(int $campaignId, string $locale = 'en', string $baseCurrency = 'USD'): DonationSummaryReadModel
    {
        $filters = [
            'campaign_id' => $campaignId,
        ];

        return $this->build($filters, $locale, $baseCurrency);
    }

    /**
     * Build summary for a specific organization.
     */
    public function buildForOrganization(int $organizationId, string $locale = 'en', string $baseCurrency = 'USD'): DonationSummaryReadModel
    {
        $filters = [
            'organization_id' => $organizationId,
        ];

        return $this->build($filters, $locale, $baseCurrency);
    }

    /**
     * Build today's summary.
     */
    public function buildToday(string $locale = 'en', string $baseCurrency = 'USD'): DonationSummaryReadModel
    {
        return $this->buildForDateRange(Carbon::today(), Carbon::today(), $locale, $baseCurrency);
    }

    /**
     * Build this month's summary.
     */
    public function buildThisMonth(string $locale = 'en', string $baseCurrency = 'USD'): DonationSummaryReadModel
    {
        return $this->buildForDateRange(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), $locale, $baseCurrency);
    }

    // Private helper methods for data aggregation

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getTotalRaised(array $filters): float
    {
        return $this->donationRepository->getTotalAmountByFilters($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getTotalDonations(array $filters): int
    {
        return $this->donationRepository->getCountByFilters($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getUniqueDonors(array $filters): int
    {
        return $this->donationRepository->getUniqueDonorsCount($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getAverageDonation(array $filters): float
    {
        return $this->donationRepository->getAverageAmount($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getMedianDonation(array $filters): float
    {
        return $this->donationRepository->getMedianAmount($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getLargestDonation(array $filters): float
    {
        return $this->donationRepository->getMaxAmount($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getSmallestDonation(array $filters): float
    {
        return $this->donationRepository->getMinAmount($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    private function getDailyTotals(array $filters): array
    {
        return $this->donationRepository->getDailyTotals($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    private function getWeeklyTotals(array $filters): array
    {
        return $this->donationRepository->getWeeklyTotals($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    private function getMonthlyTotals(array $filters): array
    {
        return $this->donationRepository->getMonthlyTotals($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getDailyGrowthRate(array $filters): float
    {
        return $this->donationRepository->getDailyGrowthRate($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getWeeklyGrowthRate(array $filters): float
    {
        return $this->donationRepository->getWeeklyGrowthRate($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getMonthlyGrowthRate(array $filters): float
    {
        return $this->donationRepository->getMonthlyGrowthRate($filters);
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function getTopDonors(array $filters, int $limit = 10): array
    {
        $topDonors = $this->donationRepository->getTopDonors($limit, $filters);

        return array_map(fn (array $donor): array => [
            'id' => $donor['user_id'],
            'name' => 'Anonymous Donor', // We don't have donor names in the repository method
            'total_amount' => $donor['total_amount'],
            'donation_count' => $donor['donation_count'],
            'is_corporate' => false, // We don't have this information in the repository method
        ], $topDonors);
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function getTopCampaigns(array $filters, int $limit = 5): array
    {
        return $this->donationRepository->getTopCampaignsByDonations($limit, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, array<string, mixed>>
     */
    private function getPaymentMethodBreakdown(array $filters): array
    {
        return $this->donationRepository->getPaymentMethodBreakdown($filters);
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, array<string, mixed>>
     */
    private function getCurrencyBreakdown(array $filters): array
    {
        return $this->donationRepository->getCurrencyBreakdown($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function getDonationCountByStatus(array $filters): array
    {
        return $this->donationRepository->getCountByStatus($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getRecurringDonationsCount(array $filters): int
    {
        return $this->donationRepository->getRecurringDonationsCount($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getRecurringDonationsAmount(array $filters): float
    {
        return $this->donationRepository->getRecurringDonationsAmount($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getCorporateDonationsAmount(array $filters): float
    {
        return $this->donationRepository->getCorporateDonationsAmount($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function getIndividualDonationsAmount(array $filters): float
    {
        return $this->donationRepository->getIndividualDonationsAmount($filters);
    }

    /**
     * Generate version hash based on filters and current time.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    private function generateVersion(array $filters): string
    {
        $baseData = [
            'filters' => $filters,
            'timestamp' => time(),
            'type' => 'donation_summary',
        ];

        $encodedData = json_encode($baseData);

        return hash('sha256', $encodedData ?: '{}');
    }
}
