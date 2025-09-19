<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Repository;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Donation\Domain\Model\Donation;

interface DonationRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Donation;

    public function findById(int $id): ?Donation;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateById(int $id, array $data): bool;

    public function delete(int $id): bool;

    /**
     * @return array<int, Donation>
     */
    public function findByCampaign(int $campaignId): array;

    /**
     * @return array<int, Donation>
     */
    public function findByEmployee(int $userId): array;

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Donation>
     */
    public function paginate(
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'id',
        string $sortOrder = 'desc',
    ): LengthAwarePaginator;

    public function getTotalDonationsByCampaign(int $campaignId): float;

    public function getTotalDonationsByEmployee(int $userId): float;

    public function findByPaymentIntentId(string $paymentIntentId): ?Donation;

    public function findByTransactionId(string $transactionId): ?Donation;

    /**
     * Save a donation model.
     */
    public function save(Donation $donation): bool;

    /**
     * Find donations by status.
     */
    /**
     * @return array<int, Donation>
     */
    public function findByStatus(string $status): array;

    /**
     * Find pending donations.
     */
    /**
     * @return array<int, Donation>
     */
    public function findPendingDonations(): array;

    /**
     * Find processing donations.
     */
    /**
     * @return array<int, Donation>
     */
    public function findProcessingDonations(): array;

    /**
     * Get count of unique donors across all campaigns.
     */
    public function getUniqueDonatorsCount(): int;

    /**
     * Get campaign donation statistics in single query (performance optimized).
     */
    /**
     * @return array<string, mixed>
     */
    public function getCampaignStats(int $campaignId): array;

    /**
     * Get employee donation statistics in single query (performance optimized).
     */
    /**
     * @return array<string, mixed>
     */
    public function getUserStats(int $userId): array;

    /**
     * Get available filters for user donations (years and campaigns).
     */
    /**
     * @return array<string, mixed>
     */
    public function getAvailableFilters(int $userId): array;

    /**
     * Get count of recurring donations with filtering.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getRecurringDonationsCount(array $filters = []): int;

    /**
     * Get total amount of recurring donations with filtering.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getRecurringDonationsAmount(array $filters = []): float;

    /**
     * Get total amount of corporate donations with filtering.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getCorporateDonationsAmount(array $filters = []): float;

    /**
     * Get total amount of individual donations with filtering.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getIndividualDonationsAmount(array $filters = []): float;

    /**
     * Get total amount of donations with filtering.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getTotalAmountByFilters(array $filters = []): float;

    /**
     * Get average donation amount.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getAverageAmount(array $filters = []): float;

    /**
     * Get maximum donation amount.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getMaxAmount(array $filters = []): float;

    /**
     * Get minimum donation amount.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getMlnAmount(array $filters = []): float;

    /**
     * Get daily donation totals.
     *
     * @return array Array with date as key and total amount as value
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    public function getDailyTotals(array $filters = []): array;

    /**
     * Get weekly donation totals.
     *
     * @return array Array with week identifier as key and total amount as value
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    public function getWeeklyTotals(array $filters = []): array;

    /**
     * Get monthly donation totals.
     *
     * @return array Array with month identifier as key and total amount as value
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    public function getMonthlyTotals(array $filters = []): array;

    /**
     * Get daily growth rate percentage.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getDailyGrowthRate(array $filters = []): float;

    /**
     * Get weekly growth rate percentage.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getWeeklyGrowthRate(array $filters = []): float;

    /**
     * Get monthly growth rate percentage.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getMonthlyGrowthRate(array $filters = []): float;

    /**
     * Get top donors by total donation amount.
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getTopDonors(int $limit = 10, array $filters = []): array;

    /**
     * Get top campaigns by total donations received.
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getTopCampaignsByDonations(int $limit = 10, array $filters = []): array;

    /**
     * Get payment method breakdown statistics.
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, array<string, mixed>>
     */
    public function getPaymentMethodBreakdown(array $filters = []): array;

    /**
     * Get currency breakdown statistics.
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, array<string, mixed>>
     */
    public function getCurrencyBreakdown(array $filters = []): array;

    /**
     * Get count of donations by status.
     *
     * @return array Array with status as key and count as value
     */
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function getCountByStatus(array $filters = []): array;

    /**
     * Get count of donations with filtering.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getCountByFilters(array $filters = []): int;

    /**
     * Get unique donors count with filtering.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getUniqueDonorsCount(array $filters = []): int;

    /**
     * Get median donation amount.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getMedianAmount(array $filters = []): float;

    /**
     * Get minimum donation amount (alias for getMlnAmount).
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getMinAmount(array $filters = []): float;
}
