<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Repository;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Donation\Application\ReadModel\DonationReportReadModel;
use Modules\Donation\Domain\Model\Donation;
use Modules\Shared\Application\ReadModel\AbstractReadModelRepository;
use Modules\Shared\Application\ReadModel\ReadModelInterface;
use stdClass;

/**
 * Repository for Donation Report Read Models with caching.
 */
class DonationReportRepository extends AbstractReadModelRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct($cache);
        $this->defaultCacheTtl = config('read-models.caching.ttl.donation_reports', 900);
        $this->cachingEnabled = config('read-models.repositories.donation_reports.cache_enabled', true);
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    protected function buildReadModel(string|int $id, ?array $filters = null): ?ReadModelInterface
    {
        $reportId = (string) $id;

        $data = $this->buildDonationReportData($reportId, $filters);

        if ($data === []) {
            return null;
        }

        return new DonationReportReadModel(
            $reportId,
            $data,
            (string) time()
        );
    }

    /**
     * @param  array<string>  $ids
     * @param  array<string, mixed>|null  $filters
     * @return array<DonationReportReadModel>
     */
    protected function buildReadModels(array $ids, ?array $filters = null): array
    {
        $results = [];

        foreach ($ids as $reportId) {
            $data = $this->buildDonationReportData($reportId, $filters);

            if ($data !== []) {
                $results[(string) $reportId] = new DonationReportReadModel(
                    $reportId,
                    $data,
                    (string) time()
                );
            }
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>|null  $filters
     * @return array<int, mixed>
     */
    protected function buildAllReadModels(?array $filters = null, ?int $limit = null, ?int $offset = null): array
    {
        // For reports, we typically generate them on-demand
        // This could list available report types or recent reports
        return [];
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    protected function buildCount(?array $filters = null): int
    {
        return 0; // Reports are generated on-demand
    }

    protected function getCachePrefix(): string
    {
        return 'donation_report';
    }

    /**
     * @return array<int, string>
     */
    protected function getDefaultCacheTags(): array
    {
        return ['donation_reports', 'donations'];
    }

    /**
     * @return Builder<Donation>
     */
    protected function getBaseQuery(): Builder
    {
        return Donation::query()
            ->with(['campaign', 'user']);
    }

    /**
     * Build comprehensive donation report data.
     *
     * @param  array<string, mixed>|null  $filters
     * @return array<string, mixed>
     */
    private function buildDonationReportData(string $reportId, ?array $filters = null): array
    {
        $query = $this->getBaseQuery();

        // Apply filters
        if ($filters) {
            $this->applyReportFilters($query, $filters);
        }

        // Parse report ID to determine report type and parameters
        $reportInfo = $this->parseReportId($reportId);
        $reportType = $reportInfo['type'] ?? 'summary';

        // Base donation statistics
        /** @var stdClass|null $donationStats */
        $donationStats = $query->selectRaw('
            COUNT(*) as total_donations,
            COUNT(DISTINCT user_id) as unique_donors,
            COUNT(DISTINCT CASE WHEN user_id IS NOT NULL THEN user_id END) as registered_donors,
            SUM(amount) as total_amount,
            AVG(amount) as average_amount,
            MIN(amount) as min_amount,
            MAX(amount) as max_amount,
            
            -- Status breakdown
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_donations,
            SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as completed_amount,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_donations,
            SUM(CASE WHEN status = "pending" THEN amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_donations,
            SUM(CASE WHEN status = "failed" THEN amount ELSE 0 END) as failed_amount,
            SUM(CASE WHEN status = "refunded" THEN 1 ELSE 0 END) as refunded_donations,
            SUM(CASE WHEN status = "refunded" THEN amount ELSE 0 END) as refunded_amount,
            
            -- Anonymous donations
            SUM(CASE WHEN anonymous = 1 THEN 1 ELSE 0 END) as anonymous_donors,
            SUM(CASE WHEN anonymous = 1 THEN amount ELSE 0 END) as anonymous_amount,
            
            -- Recurring donations
            SUM(CASE WHEN recurring = 1 THEN 1 ELSE 0 END) as recurring_donations,
            SUM(CASE WHEN recurring = 1 THEN amount ELSE 0 END) as recurring_amount,
            
            -- Corporate matching
            SUM(COALESCE(corporate_match_amount, 0)) as corporate_match_amount,
            SUM(CASE WHEN corporate_match_amount > 0 THEN 1 ELSE 0 END) as corporate_matched_donations,
            
            -- Time bounds
            MIN(created_at) as first_donation_at,
            MAX(created_at) as last_donation_at
        ')->first();

        // Donor analysis
        $donorStats = DB::table('donations')
            ->selectRaw('
                user_id,
                COUNT(*) as donation_count,
                SUM(amount) as total_amount,
                MIN(created_at) as first_donation,
                MAX(created_at) as last_donation
            ')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->get();

        $newDonors = $donorStats->where('donation_count', 1)->count();
        $returningDonors = $donorStats->where('donation_count', '>', 1)->count();

        // Payment gateway analysis
        $gatewayStats = $query->clone()
            ->where('status', 'completed')
            ->groupBy('payment_gateway')
            ->selectRaw('
                payment_gateway,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount
            ')
            ->get()
            ->keyBy('payment_gateway')
            ->toArray();

        // Payment method analysis
        $methodStats = $query->clone()
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->selectRaw('
                payment_method,
                COUNT(*) as count,
                SUM(amount) as total_amount
            ')
            ->get()
            ->keyBy('payment_method')
            ->toArray();

        // Success rates by gateway
        $successRates = [];
        /** @var Collection<int, stdClass> $allGatewayStats */
        $allGatewayStats = $query->clone()
            ->groupBy('payment_gateway')
            ->selectRaw('
                payment_gateway,
                COUNT(*) as total_attempts,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
            ')
            ->get();

        /** @var stdClass $stats */
        foreach ($allGatewayStats as $stats) {
            $successRate = $stats->total_attempts > 0
                ? ($stats->successful / $stats->total_attempts) * 100
                : 0;

            $successRates[$stats->payment_gateway] = [
                'total_attempts' => $stats->total_attempts,
                'successful' => $stats->successful,
                'failed' => $stats->failed,
                'success_rate' => round($successRate, 2),
            ];
        }

        // Recurring frequency breakdown
        $recurringFrequency = $query->clone()
            ->where('recurring', true)
            ->whereNotNull('recurring_frequency')
            ->groupBy('recurring_frequency')
            ->selectRaw('
                recurring_frequency,
                COUNT(*) as count,
                SUM(amount) as amount
            ')
            ->get()
            ->keyBy('recurring_frequency')
            ->toArray();

        // Time-based analysis
        $dailyDonations = $query->clone()
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as count,
                SUM(amount) as amount,
                COUNT(DISTINCT user_id) as unique_donors
            ')
            ->orderBy('date')
            ->get()
            ->toArray();

        $hourlyDonations = $query->clone()
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->selectRaw('
                HOUR(created_at) as hour,
                COUNT(*) as count,
                SUM(amount) as amount
            ')
            ->orderBy('hour')
            ->get()
            ->toArray();

        // Find peak donation time
        $peakHour = collect($hourlyDonations)->sortByDesc('count')->first();

        // Currency breakdown
        $currencyStats = $query->clone()
            ->where('status', 'completed')
            ->groupBy('currency')
            ->selectRaw('
                currency,
                COUNT(*) as count,
                SUM(amount) as total_amount
            ')
            ->get()
            ->keyBy('currency')
            ->toArray();

        $primaryCurrency = collect($currencyStats)->sortByDesc('total_amount')->keys()->first() ?? 'USD';

        // Top donation amounts (for statistical analysis, anonymized)
        $topAmounts = $query->clone()
            ->where('status', 'completed')
            ->orderByDesc('amount')
            ->limit(20)
            ->pluck('amount')
            ->toArray();

        return [
            // Report metadata
            'report_type' => $reportType,
            'date_range' => $this->getDateRangeFromFilters($filters),
            'start_date' => $filters['start_date'] ?? null,
            'end_date' => $filters['end_date'] ?? null,
            'organization_id' => $filters['organization_id'] ?? null,
            'organization_name' => $this->getOrganizationName($filters['organization_id'] ?? null),
            'campaign_id' => $filters['campaign_id'] ?? null,
            'campaign_title' => $this->getCampaignTitle($filters['campaign_id'] ?? null),
            'report_generated_at' => now()->toISOString(),

            // Summary statistics
            'total_amount' => (float) ($donationStats->total_amount ?? 0),
            'total_donations' => (int) ($donationStats->total_donations ?? 0),
            'unique_donors' => (int) ($donationStats->unique_donors ?? 0),
            'average_donation_amount' => (float) ($donationStats->average_amount ?? 0),
            'min_donation_amount' => (float) ($donationStats->min_amount ?? 0),
            'max_donation_amount' => (float) ($donationStats->max_amount ?? 0),
            'median_donation_amount' => $this->calculateMedian($topAmounts),

            // Donor statistics
            'new_donors' => $newDonors,
            'returning_donors' => $returningDonors,
            'anonymous_donors' => (int) ($donationStats->anonymous_donors ?? 0),
            'anonymous_donations_amount' => (float) ($donationStats->anonymous_amount ?? 0),

            // Status breakdown
            'completed_donations' => (int) ($donationStats->completed_donations ?? 0),
            'completed_amount' => (float) ($donationStats->completed_amount ?? 0),
            'pending_donations' => (int) ($donationStats->pending_donations ?? 0),
            'pending_amount' => (float) ($donationStats->pending_amount ?? 0),
            'failed_donations' => (int) ($donationStats->failed_donations ?? 0),
            'failed_amount' => (float) ($donationStats->failed_amount ?? 0),
            'refunded_donations' => (int) ($donationStats->refunded_donations ?? 0),
            'refunded_amount' => (float) ($donationStats->refunded_amount ?? 0),

            // Recurring donations
            'recurring_donations' => (int) ($donationStats->recurring_donations ?? 0),
            'recurring_amount' => (float) ($donationStats->recurring_amount ?? 0),
            'recurring_frequency_breakdown' => $recurringFrequency,

            // Corporate matching
            'corporate_match_amount' => (float) ($donationStats->corporate_match_amount ?? 0),
            'corporate_matched_donations' => (int) ($donationStats->corporate_matched_donations ?? 0),

            // Payment analysis
            'payment_gateway_stats' => $gatewayStats,
            'payment_method_stats' => $methodStats,
            'success_rate_by_gateway' => $successRates,
            'total_gateway_fees' => 0, // To be calculated based on gateway configs

            // Time analysis
            'donations_by_day' => $dailyDonations,
            'donations_by_hour' => $hourlyDonations,
            'peak_donation_time' => $peakHour,
            'first_donation_at' => $donationStats?->first_donation_at,
            'last_donation_at' => $donationStats?->last_donation_at,

            // Currency breakdown
            'currency_breakdown' => $currencyStats,
            'primary_currency' => $primaryCurrency,

            // Top donations (anonymized)
            'top_donation_amounts' => $topAmounts,
        ];
    }

    /**
     * Parse report ID to extract type and parameters.
     *
     * @return array<string, mixed>
     */
    private function parseReportId(string $reportId): array
    {
        // Report ID format: type_param1_param2_timestamp
        $parts = explode('_', $reportId);

        return [
            'type' => $parts[0] ?? 'summary',
            'params' => array_slice($parts, 1, -1),
            'timestamp' => end($parts),
        ];
    }

    /**
     * Apply report-specific filters to query.
     *
     * @param  Builder<Donation>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyReportFilters(Builder $query, array $filters): void
    {
        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        if (isset($filters['organization_id'])) {
            $query->whereHas('campaign', function ($q) use ($filters): void {
                $q->where('organization_id', $filters['organization_id']);
            });
        }

        if (isset($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        if (isset($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (isset($filters['payment_gateway'])) {
            $query->where('payment_gateway', $filters['payment_gateway']);
        }

        if (isset($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }
    }

    /**
     * Get date range description from filters.
     *
     * @param  array<string, mixed>|null  $filters
     * @return array<string, mixed>
     */
    private function getDateRangeFromFilters(?array $filters): array
    {
        if (! $filters) {
            return [];
        }

        return [
            'start' => $filters['start_date'] ?? null,
            'end' => $filters['end_date'] ?? null,
        ];
    }

    /**
     * Get organization name by ID.
     */
    private function getOrganizationName(?int $organizationId): ?string
    {
        if (! $organizationId) {
            return null;
        }

        return DB::table('organizations')
            ->where('id', $organizationId)
            ->value('name');
    }

    /**
     * Get campaign title by ID.
     */
    private function getCampaignTitle(?int $campaignId): ?string
    {
        if (! $campaignId) {
            return null;
        }

        return DB::table('campaigns')
            ->where('id', $campaignId)
            ->value('title');
    }

    /**
     * Calculate median from array of values.
     *
     * @param  array<int, float>  $values
     */
    private function calculateMedian(array $values): float
    {
        if ($values === []) {
            return 0.0;
        }

        sort($values);
        $count = count($values);
        $middle = (int) floor($count / 2);

        if ($count % 2 === 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }

        return $values[$middle];
    }

    /**
     * Generate report for organization.
     */
    /**
     * @param  array<string, string>|null  $dateRange
     */
    public function generateOrganizationReport(int $organizationId, ?array $dateRange = null): ?ReadModelInterface
    {
        $reportId = sprintf('organization_%d_%s', $organizationId, time());

        $filters = ['organization_id' => $organizationId];
        if ($dateRange) {
            $filters = array_merge($filters, $dateRange);
        }

        return $this->find($reportId, $filters);
    }

    /**
     * Generate report for campaign.
     */
    /**
     * @param  array<string, string>|null  $dateRange
     */
    public function generateCampaignReport(int $campaignId, ?array $dateRange = null): ?ReadModelInterface
    {
        $reportId = sprintf('campaign_%d_%s', $campaignId, time());

        $filters = ['campaign_id' => $campaignId];
        if ($dateRange) {
            $filters = array_merge($filters, $dateRange);
        }

        return $this->find($reportId, $filters);
    }

    /**
     * Generate gateway performance report.
     */
    /**
     * @param  array<string, string>|null  $dateRange
     */
    public function generateGatewayReport(?array $dateRange = null): ?ReadModelInterface
    {
        $reportId = sprintf('gateway_performance_%s', time());

        $filters = [];
        if ($dateRange) {
            $filters = array_merge($filters, $dateRange);
        }

        return $this->find($reportId, $filters);
    }
}
