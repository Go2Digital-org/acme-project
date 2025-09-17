<?php

declare(strict_types=1);

namespace Modules\Donation\Application\ReadModel;

use JsonSerializable;
use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Donation summary read model for aggregated donation statistics.
 * Optimized for dashboard display and analytics with pre-calculated metrics.
 */
final class DonationSummaryReadModel extends AbstractReadModel implements JsonSerializable
{
    private readonly string $locale;

    private readonly string $baseCurrency;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        array $data,
        ?string $version = null,
        ?string $locale = null,
        ?string $baseCurrency = null
    ) {
        parent::__construct(0, $data, $version); // Summary doesn't have single ID
        $this->locale = $locale ?? app()->getLocale();
        $this->baseCurrency = $baseCurrency ?? config('app.currency', 'USD');
        $this->setCacheTtl(1800); // 30 minutes for donation summaries
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'donation_summary',
            'donation_stats',
            'analytics',
            'locale:' . $this->locale,
            'currency:' . $this->baseCurrency,
        ]);
    }

    /**
     * Get current locale for translations.
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Get base currency for conversions.
     */
    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }

    // Overall Statistics
    public function getTotalRaised(): float
    {
        return (float) $this->get('total_raised', 0);
    }

    public function getTotalDonations(): int
    {
        return (int) $this->get('total_donations', 0);
    }

    public function getUniqueDonors(): int
    {
        return (int) $this->get('unique_donors', 0);
    }

    public function getAverageDonation(): float
    {
        return (float) $this->get('average_donation', 0);
    }

    public function getMedianDonation(): float
    {
        return (float) $this->get('median_donation', 0);
    }

    public function getLargestDonation(): float
    {
        return (float) $this->get('largest_donation', 0);
    }

    public function getSmallestDonation(): float
    {
        return (float) $this->get('smallest_donation', 0);
    }

    // Time-based Metrics

    /**
     * @return array<string, float>
     */
    public function getDailyTotals(): array
    {
        return $this->get('daily_totals', []);
    }

    /**
     * @return array<string, float>
     */
    public function getWeeklyTotals(): array
    {
        return $this->get('weekly_totals', []);
    }

    /**
     * @return array<string, float>
     */
    public function getMonthlyTotals(): array
    {
        return $this->get('monthly_totals', []);
    }

    public function getTodaysTotal(): float
    {
        $dailyTotals = $this->getDailyTotals();
        $today = date('Y-m-d');

        return (float) ($dailyTotals[$today] ?? 0);
    }

    public function getThisWeeksTotal(): float
    {
        $weeklyTotals = $this->getWeeklyTotals();
        $thisWeek = date('Y-W');

        return (float) ($weeklyTotals[$thisWeek] ?? 0);
    }

    public function getThisMonthsTotal(): float
    {
        $monthlyTotals = $this->getMonthlyTotals();
        $thisMonth = date('Y-m');

        return (float) ($monthlyTotals[$thisMonth] ?? 0);
    }

    // Growth Metrics
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

    // Top Donors (anonymized for privacy)
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopDonors(int $limit = 10): array
    {
        $topDonors = $this->get('top_donors', []);

        return array_slice($topDonors, 0, $limit);
    }

    public function hasTopDonors(): bool
    {
        return $this->getTopDonors() !== [];
    }

    // Campaign Performance
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopCampaigns(int $limit = 5): array
    {
        $topCampaigns = $this->get('top_campaigns', []);

        return array_slice($topCampaigns, 0, $limit);
    }

    public function hasTopCampaigns(): bool
    {
        return $this->getTopCampaigns() !== [];
    }

    // Payment Method Breakdown
    /**
     * @return array<string, mixed>
     */
    public function getPaymentMethodBreakdown(): array
    {
        return $this->get('payment_method_breakdown', []);
    }

    public function getPaymentMethodPercentage(string $method): float
    {
        $breakdown = $this->getPaymentMethodBreakdown();

        return (float) ($breakdown[$method]['percentage'] ?? 0);
    }

    public function getPaymentMethodAmount(string $method): float
    {
        $breakdown = $this->getPaymentMethodBreakdown();

        return (float) ($breakdown[$method]['amount'] ?? 0);
    }

    // Donation Status Breakdown
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

    public function getCompletionRate(): float
    {
        $total = $this->getTotalDonations();

        if ($total === 0) {
            return 0.0;
        }

        return round(($this->getCompletedDonationsCount() / $total) * 100, 2);
    }

    // Recurring Donations
    public function getRecurringDonationsCount(): int
    {
        return (int) $this->get('recurring_donations_count', 0);
    }

    public function getRecurringDonationsAmount(): float
    {
        return (float) $this->get('recurring_donations_amount', 0);
    }

    public function getRecurringDonationPercentage(): float
    {
        $total = $this->getTotalRaised();

        if ($total === 0.0) {
            return 0.0;
        }

        return round(($this->getRecurringDonationsAmount() / $total) * 100, 2);
    }

    // Corporate vs Individual Donations
    public function getCorporateDonationsAmount(): float
    {
        return (float) $this->get('corporate_donations_amount', 0);
    }

    public function getIndividualDonationsAmount(): float
    {
        return (float) $this->get('individual_donations_amount', 0);
    }

    public function getCorporateDonationPercentage(): float
    {
        $total = $this->getTotalRaised();

        if ($total === 0.0) {
            return 0.0;
        }

        return round(($this->getCorporateDonationsAmount() / $total) * 100, 2);
    }

    // Currency Conversion (pre-calculated)
    /**
     * @return array<string, float>
     */
    public function getCurrencyBreakdown(): array
    {
        return $this->get('currency_breakdown', []);
    }

    public function getTotalInCurrency(string $currency): float
    {
        $breakdown = $this->getCurrencyBreakdown();

        return (float) ($breakdown[$currency] ?? 0);
    }

    // Formatted Values
    public function getFormattedTotalRaised(): string
    {
        return $this->formatCurrency($this->getTotalRaised());
    }

    public function getFormattedAverageDonation(): string
    {
        return $this->formatCurrency($this->getAverageDonation());
    }

    public function getFormattedLargestDonation(): string
    {
        return $this->formatCurrency($this->getLargestDonation());
    }

    public function getFormattedTodaysTotal(): string
    {
        return $this->formatCurrency($this->getTodaysTotal());
    }

    public function getFormattedThisWeeksTotal(): string
    {
        return $this->formatCurrency($this->getThisWeeksTotal());
    }

    public function getFormattedThisMonthsTotal(): string
    {
        return $this->formatCurrency($this->getThisMonthsTotal());
    }

    // Helper Methods
    private function formatCurrency(float $amount): string
    {
        return number_format($amount, 2) . ' ' . $this->baseCurrency;
    }

    /**
     * JsonSerializable implementation for optimized API responses.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toApiResponse();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'overview' => [
                'total_raised' => $this->getTotalRaised(),
                'total_donations' => $this->getTotalDonations(),
                'unique_donors' => $this->getUniqueDonors(),
                'average_donation' => $this->getAverageDonation(),
                'median_donation' => $this->getMedianDonation(),
                'largest_donation' => $this->getLargestDonation(),
                'completion_rate' => $this->getCompletionRate(),
            ],
            'time_metrics' => [
                'todays_total' => $this->getTodaysTotal(),
                'this_weeks_total' => $this->getThisWeeksTotal(),
                'this_months_total' => $this->getThisMonthsTotal(),
                'daily_growth_rate' => $this->getDailyGrowthRate(),
                'weekly_growth_rate' => $this->getWeeklyGrowthRate(),
                'monthly_growth_rate' => $this->getMonthlyGrowthRate(),
            ],
            'breakdowns' => [
                'payment_methods' => $this->getPaymentMethodBreakdown(),
                'currencies' => $this->getCurrencyBreakdown(),
                'corporate_vs_individual' => [
                    'corporate_amount' => $this->getCorporateDonationsAmount(),
                    'individual_amount' => $this->getIndividualDonationsAmount(),
                    'corporate_percentage' => $this->getCorporateDonationPercentage(),
                ],
                'recurring' => [
                    'count' => $this->getRecurringDonationsCount(),
                    'amount' => $this->getRecurringDonationsAmount(),
                    'percentage' => $this->getRecurringDonationPercentage(),
                ],
            ],
            'top_performers' => [
                'campaigns' => $this->getTopCampaigns(),
                'donors' => $this->getTopDonors(),
            ],
            'meta' => [
                'locale' => $this->getLocale(),
                'base_currency' => $this->getBaseCurrency(),
                'generated_at' => $this->getGeneratedAt(),
                'cache_ttl' => $this->getCacheTtl(),
            ],
        ];
    }

    /**
     * Get data optimized for API responses.
     *
     * @return array<string, mixed>
     */
    public function toApiResponse(): array
    {
        return [
            'data' => [
                'overview' => [
                    'total_raised' => $this->getTotalRaised(),
                    'formatted_total_raised' => $this->getFormattedTotalRaised(),
                    'total_donations' => $this->getTotalDonations(),
                    'unique_donors' => $this->getUniqueDonors(),
                    'average_donation' => $this->getAverageDonation(),
                    'formatted_average_donation' => $this->getFormattedAverageDonation(),
                    'completion_rate' => $this->getCompletionRate(),
                ],
                'time_metrics' => [
                    'today' => [
                        'amount' => $this->getTodaysTotal(),
                        'formatted' => $this->getFormattedTodaysTotal(),
                    ],
                    'this_week' => [
                        'amount' => $this->getThisWeeksTotal(),
                        'formatted' => $this->getFormattedThisWeeksTotal(),
                    ],
                    'this_month' => [
                        'amount' => $this->getThisMonthsTotal(),
                        'formatted' => $this->getFormattedThisMonthsTotal(),
                    ],
                    'growth_rates' => [
                        'daily' => $this->getDailyGrowthRate(),
                        'weekly' => $this->getWeeklyGrowthRate(),
                        'monthly' => $this->getMonthlyGrowthRate(),
                    ],
                ],
                'breakdowns' => [
                    'payment_methods' => $this->getPaymentMethodBreakdown(),
                    'recurring_percentage' => $this->getRecurringDonationPercentage(),
                    'corporate_percentage' => $this->getCorporateDonationPercentage(),
                ],
                'top_performers' => [
                    'campaigns' => $this->getTopCampaigns(5),
                    'has_top_campaigns' => $this->hasTopCampaigns(),
                ],
            ],
            'meta' => [
                'locale' => $this->getLocale(),
                'base_currency' => $this->getBaseCurrency(),
                'generated_at' => $this->getGeneratedAt(),
                'cache_ttl' => $this->getCacheTtl(),
            ],
        ];
    }
}
