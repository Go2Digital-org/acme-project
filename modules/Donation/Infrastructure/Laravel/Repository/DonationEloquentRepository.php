<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Repository;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;

class DonationEloquentRepository implements DonationRepositoryInterface
{
    public function __construct(
        private readonly Donation $model,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Donation
    {
        return $this->model->create($data);
    }

    public function findById(int $id): ?Donation
    {
        return $this->model->with('campaign')->find($id);
    }

    /**
     * @return array<int, Donation>
     */
    public function findByEmployee(int $userId): array
    {
        return $this->model
            ->with(['campaign', 'campaign.creator'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    /**
     * @return array<int, Donation>
     */
    public function findByCampaign(int $campaignId): array
    {
        return $this->model
            ->where('campaign_id', $campaignId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateById(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data) > 0;
    }

    /**
     * @return LengthAwarePaginator<int, Donation>
     */
    public function paginate(
        int $page = 1,
        int $perPage = 15,
        /** @param array<string, mixed> $filters */
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->with(['campaign', 'campaign.creator']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (isset($filters['anonymous'])) {
            $query->where('anonymous', (bool) $filters['anonymous']);
        }

        return $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function delete(int $id): bool
    {
        return $this->model->where('id', $id)->delete() > 0;
    }

    public function getTotalDonationsByEmployee(int $userId): float
    {
        return (float) $this->model
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->sum('amount');
    }

    public function getTotalDonationsByCampaign(int $campaignId): float
    {
        return (float) $this->model
            ->where('campaign_id', $campaignId)
            ->where('status', 'completed')
            ->sum('amount');
    }

    public function findByPaymentIntentId(string $paymentIntentId): ?Donation
    {
        return $this->model
            ->where('gateway_response_id', $paymentIntentId)
            ->orWhere('transaction_id', $paymentIntentId)
            ->first();
    }

    public function findByTransactionId(string $transactionId): ?Donation
    {
        return $this->model
            ->where('transaction_id', $transactionId)
            ->first();
    }

    public function save(Donation $donation): bool
    {
        return $donation->save();
    }

    /**
     * @return array<int, Donation>
     */
    public function findByStatus(string $status): array
    {
        return $this->model
            ->with('campaign')
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    /**
     * @return array<int, Donation>
     */
    public function findPendingDonations(): array
    {
        return $this->findByStatus('pending');
    }

    /**
     * @return array<int, Donation>
     */
    public function findProcessingDonations(): array
    {
        return $this->findByStatus('processing');
    }

    /**
     * Get count of unique donors across all campaigns.
     */
    public function getUniqueDonatorsCount(): int
    {
        return $this->model
            ->where('status', 'completed')
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Get campaign donation statistics in single query (performance optimized).
     */
    /**
     * @return array<string, mixed>
     */
    public function getCampaignStats(int $campaignId): array
    {
        $stats = $this->model
            ->selectRaw('
                COUNT(*) as total_donations,
                COUNT(DISTINCT user_id) as unique_donors,
                SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_amount,
                AVG(CASE WHEN status = "completed" THEN amount ELSE NULL END) as average_amount,
                MAX(CASE WHEN status = "completed" THEN amount ELSE 0 END) as largest_amount,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count
            ')
            ->where('campaign_id', $campaignId)
            ->first();

        if ($stats === null) {
            return [
                'total_donations' => 0,
                'unique_donors' => 0,
                'total_amount' => 0.0,
                'average_amount' => 0.0,
                'largest_amount' => 0.0,
                'completed_count' => 0,
                'pending_count' => 0,
                'failed_count' => 0,
            ];
        }

        return [
            'total_donations' => (int) $stats->total_donations, // @phpstan-ignore-line
            'unique_donors' => (int) $stats->unique_donors, // @phpstan-ignore-line
            'total_amount' => (float) $stats->total_amount, // @phpstan-ignore-line
            'average_amount' => (float) $stats->average_amount, // @phpstan-ignore-line
            'largest_amount' => (float) $stats->largest_amount, // @phpstan-ignore-line
            'completed_count' => (int) $stats->completed_count, // @phpstan-ignore-line
            'pending_count' => (int) $stats->pending_count, // @phpstan-ignore-line
            'failed_count' => (int) $stats->failed_count, // @phpstan-ignore-line
        ];
    }

    /**
     * Get user donation statistics in single query (performance optimized).
     */
    /**
     * @return array<string, mixed>
     */
    public function getUserStats(int $userId): array
    {
        try {
            // Try Redis first - user stats continue using the existing pattern
            $redisKey = "user_stats_{$userId}";
            $cached = Redis::get($redisKey);

            if ($cached) {
                $data = json_decode($cached, true);
                if (is_array($data)) {
                    return $data;
                }
            }

            // Fallback to database cache table
            $cached = DB::table('application_cache')
                ->where('cache_key', "user_stats_{$userId}")
                ->where('cache_status', 'ready')
                ->first();

            if ($cached && isset($cached->stats_data)) {
                return json_decode((string) $cached->stats_data, true);
            }

            return $this->getEmptyUserStats();
        } catch (Exception) {
            return $this->getEmptyUserStats();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getEmptyUserStats(): array
    {
        return [
            'total_donations' => 0,
            'campaigns_supported' => 0,
            'total_amount' => 0.0,
            'average_amount' => 0.0,
            'largest_amount' => 0.0,
            'completed_count' => 0,
        ];
    }

    /**
     * Get available filters for user donations (years and campaigns).
     */
    /**
     * @return array<string, mixed>
     */
    public function getAvailableFilters(int $userId): array
    {
        try {
            // Try Redis first - user filters continue using the existing pattern
            $redisKey = "user_filters_{$userId}";
            $cached = Redis::get($redisKey);

            if ($cached) {
                $data = json_decode($cached, true);
                if (is_array($data)) {
                    return $data;
                }
            }

            // Fallback to database cache table
            $cached = DB::table('application_cache')
                ->where('cache_key', "user_filters_{$userId}")
                ->where('cache_status', 'ready')
                ->first();

            if ($cached && isset($cached->stats_data)) {
                return json_decode((string) $cached->stats_data, true);
            }

            return $this->getEmptyFilters();
        } catch (Exception) {
            return $this->getEmptyFilters();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getEmptyFilters(): array
    {
        return [
            'years' => [],
            'campaigns' => [],
            'this_year_count' => 0,
        ];
    }

    /**
     * Get count of recurring donations.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getRecurringDonationsCount(array $filters = []): int
    {
        $query = $this->model->where('is_recurring', true);

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return $query->count();
    }

    /**
     * Get total amount of recurring donations.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getRecurringDonationsAmount(array $filters = []): float
    {
        $query = $this->model->where('is_recurring', true);

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Get total amount of corporate donations.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getCorporateDonationsAmount(array $filters = []): float
    {
        $query = $this->model->where('donor_type', 'corporate');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Get total amount of individual donations.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getIndividualDonationsAmount(array $filters = []): float
    {
        $query = $this->model->where('donor_type', 'individual');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Apply filters to query.
     *
     * @param  Builder<Donation>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Donation>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['amount_min'])) {
            $query->where('amount', '>=', $filters['amount_min']);
        }

        if (isset($filters['amount_max'])) {
            $query->where('amount', '<=', $filters['amount_max']);
        }

        if (isset($filters['organization_id'])) {
            $query->whereHas('campaign', function ($q) use ($filters): void {
                $q->where('organization_id', $filters['organization_id']);
            });
        }

        return $query;
    }

    /**
     * Get total amount of donations with filtering.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getTotalAmountByFilters(array $filters = []): float
    {
        $query = $this->model->where('status', 'completed');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Get average donation amount.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getAverageAmount(array $filters = []): float
    {
        $query = $this->model->where('status', 'completed');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return (float) $query->avg('amount');
    }

    /**
     * Get maximum donation amount.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getMaxAmount(array $filters = []): float
    {
        $query = $this->model->where('status', 'completed');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return (float) $query->max('amount');
    }

    /**
     * Get minimum donation amount.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getMlnAmount(array $filters = []): float
    {
        $query = $this->model->where('status', 'completed');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return (float) $query->min('amount');
    }

    /**
     * Get daily donation totals.
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    public function getDailyTotals(array $filters = []): array
    {
        $query = $this->model
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return $query->pluck('total', 'date')->map(fn ($value): float => (float) $value)->toArray();
    }

    /**
     * Get weekly donation totals.
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    public function getWeeklyTotals(array $filters = []): array
    {
        $query = $this->model
            ->selectRaw('YEARWEEK(created_at) as week, SUM(amount) as total')
            ->where('status', 'completed')
            ->groupBy('week')
            ->orderBy('week');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return $query->pluck('total', 'week')->map(fn ($value): float => (float) $value)->toArray();
    }

    /**
     * Get monthly donation totals.
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    public function getMonthlyTotals(array $filters = []): array
    {
        $query = $this->model
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total')
            ->where('status', 'completed')
            ->groupBy('month')
            ->orderBy('month');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return $query->pluck('total', 'month')->map(fn ($value): float => (float) $value)->toArray();
    }

    /**
     * Get daily growth rate percentage.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getDailyGrowthRate(array $filters = []): float
    {
        $today = $this->getTotalAmountForDate(now()->format('Y-m-d'), $filters);
        $yesterday = $this->getTotalAmountForDate(now()->subDay()->format('Y-m-d'), $filters);

        if ($yesterday === 0.0) {
            return $today > 0 ? 100.0 : 0.0;
        }

        return (($today - $yesterday) / $yesterday) * 100;
    }

    /**
     * Get weekly growth rate percentage.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getWeeklyGrowthRate(array $filters = []): float
    {
        $thisWeek = $this->getTotalAmountForWeek(now()->format('YW'), $filters);
        $lastWeek = $this->getTotalAmountForWeek(now()->subWeek()->format('YW'), $filters);

        if ($lastWeek === 0.0) {
            return $thisWeek > 0 ? 100.0 : 0.0;
        }

        return (($thisWeek - $lastWeek) / $lastWeek) * 100;
    }

    /**
     * Get monthly growth rate percentage.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getMonthlyGrowthRate(array $filters = []): float
    {
        $thisMonth = $this->getTotalAmountForMonth(now()->format('Y-m'), $filters);
        $lastMonth = $this->getTotalAmountForMonth(now()->subMonth()->format('Y-m'), $filters);

        if ($lastMonth === 0.0) {
            return $thisMonth > 0 ? 100.0 : 0.0;
        }

        return (($thisMonth - $lastMonth) / $lastMonth) * 100;
    }

    /**
     * Get top donors by total donation amount.
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getTopDonors(int $limit = 10, array $filters = []): array
    {
        $query = $this->model
            ->selectRaw('user_id, SUM(amount) as total_amount, COUNT(*) as donation_count')
            ->where('status', 'completed')
            ->groupBy('user_id')
            ->orderBy('total_amount', 'desc')
            ->limit($limit);

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return $query->get()->map(fn ($item): array => [
            'user_id' => (int) $item->user_id, // @phpstan-ignore-line
            'total_amount' => (float) $item->total_amount, // @phpstan-ignore-line
            'donation_count' => (int) $item->donation_count, // @phpstan-ignore-line
        ])->toArray();
    }

    /**
     * Get top campaigns by total donations received.
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getTopCampaignsByDonations(int $limit = 10, array $filters = []): array
    {
        $query = $this->model
            ->selectRaw('campaign_id, SUM(amount) as total_amount, COUNT(*) as donation_count')
            ->where('status', 'completed')
            ->groupBy('campaign_id')
            ->orderBy('total_amount', 'desc')
            ->limit($limit);

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return $query->get()->map(fn ($item): array => [
            'campaign_id' => (int) $item->campaign_id, // @phpstan-ignore-line
            'total_amount' => (float) $item->total_amount, // @phpstan-ignore-line
            'donation_count' => (int) $item->donation_count, // @phpstan-ignore-line
        ])->toArray();
    }

    /**
     * Get payment method breakdown statistics.
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, array<string, mixed>>
     */
    public function getPaymentMethodBreakdown(array $filters = []): array
    {
        $query = $this->model
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total_amount')
            ->where('status', 'completed')
            ->groupBy('payment_method');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        $results = $query->get();
        $totalAmount = $results->sum('total_amount');

        return $results->mapWithKeys(function ($item) use ($totalAmount): array {
            $percentage = $totalAmount > 0 ? (((float) $item->total_amount) / $totalAmount) * 100 : 0.0; // @phpstan-ignore-line
            $paymentMethod = (string) $item->payment_method; // @phpstan-ignore-line

            return [
                $paymentMethod => [
                    'count' => (int) $item->count, // @phpstan-ignore-line
                    'total_amount' => (float) $item->total_amount, // @phpstan-ignore-line
                    'percentage' => $percentage,
                ],
            ];
        })->toArray();
    }

    /**
     * Get currency breakdown statistics.
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, array<string, mixed>>
     */
    public function getCurrencyBreakdown(array $filters = []): array
    {
        $query = $this->model
            ->selectRaw('currency, COUNT(*) as count, SUM(amount) as total_amount')
            ->where('status', 'completed')
            ->groupBy('currency');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        $results = $query->get();
        $totalAmount = $results->sum('total_amount');

        return $results->mapWithKeys(function ($item) use ($totalAmount): array {
            $percentage = $totalAmount > 0 ? (((float) $item->total_amount) / $totalAmount) * 100 : 0.0; // @phpstan-ignore-line
            $currency = (string) $item->currency; // @phpstan-ignore-line

            return [
                $currency => [
                    'count' => (int) $item->count, // @phpstan-ignore-line
                    'total_amount' => (float) $item->total_amount, // @phpstan-ignore-line
                    'percentage' => $percentage,
                ],
            ];
        })->toArray();
    }

    /**
     * Get count of donations by status.
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function getCountByStatus(array $filters = []): array
    {
        $query = $this->model
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return $query->pluck('count', 'status')->map(fn ($value): int => (int) $value)->toArray();
    }

    /**
     * Get total amount for a specific date.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    private function getTotalAmountForDate(string $date, array $filters = []): float
    {
        $query = $this->model
            ->whereDate('created_at', $date)
            ->where('status', 'completed');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Get total amount for a specific week.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    private function getTotalAmountForWeek(string $week, array $filters = []): float
    {
        $query = $this->model
            ->whereRaw('YEARWEEK(created_at) = ?', [$week])
            ->where('status', 'completed');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Get total amount for a specific month.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    private function getTotalAmountForMonth(string $month, array $filters = []): float
    {
        $query = $this->model
            ->whereRaw('DATE_FORMAT(created_at, "%Y-%m") = ?', [$month])
            ->where('status', 'completed');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Get count of donations with filtering.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getCountByFilters(array $filters = []): int
    {
        $query = $this->model->where('status', 'completed');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return $query->count();
    }

    /**
     * Get unique donors count with filtering.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getUniqueDonorsCount(array $filters = []): int
    {
        $query = $this->model->where('status', 'completed');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return $query->distinct('user_id')->count('user_id');
    }

    /**
     * Get median donation amount.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getMedianAmount(array $filters = []): float
    {
        $query = $this->model->where('status', 'completed');

        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        $count = $query->count();

        if ($count === 0) {
            return 0.0;
        }

        $middleIndex = (int) floor($count / 2);

        if ($count % 2 === 0) {
            // Even number of records - average of two middle values
            $values = $query->orderBy('amount')
                ->skip($middleIndex - 1)
                ->take(2)
                ->pluck('amount');

            return (float) ($values->sum() / 2);
        }

        // Odd number of records - middle value
        return (float) $query->orderBy('amount')
            ->skip($middleIndex)
            ->take(1)
            ->value('amount');
    }

    /**
     * Get minimum donation amount (alias for getMlnAmount).
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getMinAmount(array $filters = []): float
    {
        return $this->getMlnAmount($filters);
    }
}
