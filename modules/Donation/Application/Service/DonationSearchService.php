<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Service;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Modules\Donation\Domain\Model\Donation;
use Modules\Shared\Application\Service\SearchService;

/**
 * Donation search service with specialized filtering and caching.
 *
 * @extends SearchService<Donation>
 */
class DonationSearchService extends SearchService
{
    protected function getModelClass(): string
    {
        return Donation::class;
    }

    protected function getCachePrefix(): string
    {
        return 'donation_search';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDefaultFilters(): array
    {
        return [];
    }

    /**
     * @return array<string, int>
     */
    protected function getSearchableAttributesWeights(): array
    {
        return [
            'transaction_id' => 3,
            'donor_name' => 3,
            'donor_email' => 2,
            'campaign_title' => 2,
            'organization_name' => 2,
            'notes' => 1,
        ];
    }

    /**
     * Search donations by status.
     *
     * @return LengthAwarePaginator<int, Donation>
     */
    public function searchByStatus(
        string $status,
        string $query = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->search(
            query: $query,
            filters: ['status' => $status],
            sortBy: 'donated_at',
            sortDirection: 'desc',
            perPage: $perPage,
            page: $page
        );
    }

    /**
     * Search donations by campaign.
     *
     * @return LengthAwarePaginator<int, Donation>
     */
    public function searchByCampaign(
        int $campaignId,
        string $query = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->search(
            query: $query,
            filters: ['campaign_id' => $campaignId],
            sortBy: 'donated_at',
            sortDirection: 'desc',
            perPage: $perPage,
            page: $page
        );
    }

    /**
     * Search donations by donor.
     *
     * @return LengthAwarePaginator<int, Donation>
     */
    public function searchByDonor(
        int $userId,
        string $query = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->search(
            query: $query,
            filters: ['user_id' => $userId],
            sortBy: 'donated_at',
            sortDirection: 'desc',
            perPage: $perPage,
            page: $page
        );
    }

    /**
     * Search donations by amount range.
     *
     * @return LengthAwarePaginator<int, Donation>
     */
    public function searchByAmountRange(
        string $amountRange,
        string $query = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        return $this->search(
            query: $query,
            filters: ['amount_range' => $amountRange],
            sortBy: 'amount',
            sortDirection: 'desc',
            perPage: $perPage,
            page: $page
        );
    }

    /**
     * Search successful donations only.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Donation>
     */
    public function searchSuccessful(
        string $query = '',
        array $filters = [],
        string $sortBy = 'donated_at',
        string $sortDirection = 'desc',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        $filters['is_successful'] = true;

        return $this->search($query, $filters, $sortBy, $sortDirection, $perPage, $page);
    }

    /**
     * Search recurring donations.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Donation>
     */
    public function searchRecurring(
        string $query = '',
        array $filters = [],
        string $sortBy = 'donated_at',
        string $sortDirection = 'desc',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1
    ): LengthAwarePaginator {
        $filters['recurring'] = true;

        return $this->search($query, $filters, $sortBy, $sortDirection, $perPage, $page);
    }

    /**
     * Get donation transaction suggestions for autocomplete.
     *
     * @return SupportCollection<int, array{id: int, transaction_id: string|null, amount: string, donor_name: string|null, campaign_title: string|null, status: string, donated_at: string}>
     */
    public function getTransactionSuggestions(string $query, int $limit = 10): SupportCollection
    {
        if (strlen($query) < 3) {
            return new SupportCollection;
        }

        $cacheKey = $this->getCachePrefix() . ':transaction_suggestions:' . md5($query . $limit);

        return cache()->remember($cacheKey, self::CACHE_TTL, fn () => Donation::search($query)
            ->take($limit)
            ->get()
            ->map(fn (Donation $donation): array => [
                'id' => $donation->id,
                'transaction_id' => $donation->transaction_id,
                'amount' => $donation->formatted_amount,
                'donor_name' => $donation->anonymous ? 'Anonymous' : $donation->user?->name,
                'campaign_title' => $donation->campaign?->title,
                'status' => $donation->status->value,
                'donated_at' => $donation->donated_at->format('Y-m-d H:i:s'),
            ]));
    }

    /**
     * Get status facets.
     */
    /**
     * @return array<string, int>
     */
    public function getStatusFacets(string $query = ''): array
    {
        $cacheKey = $this->getCachePrefix() . ':status_facets:' . md5($query);

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($query): array {
            $builder = Donation::search($query);
            $donations = $builder->take(1000)->get();
            $facets = [];

            foreach ($donations as $donation) {
                $status = $donation->status->value;
                $facets[$status] = ($facets[$status] ?? 0) + 1;
            }

            arsort($facets);

            return $facets;
        });
    }

    /**
     * Get payment method facets.
     */
    /**
     * @return array<string, int>
     */
    public function getPaymentMethodFacets(string $query = ''): array
    {
        $cacheKey = $this->getCachePrefix() . ':payment_method_facets:' . md5($query);

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($query): array {
            $builder = Donation::search($query);
            $donations = $builder->take(1000)->get();
            $facets = [];

            foreach ($donations as $donation) {
                $method = $donation->payment_method?->value;
                if ($method) {
                    $facets[$method] = ($facets[$method] ?? 0) + 1;
                }
            }

            arsort($facets);

            return $facets;
        });
    }

    /**
     * Get amount range facets.
     */
    /**
     * @return array<string, int>
     */
    public function getAmountRangeFacets(string $query = ''): array
    {
        $cacheKey = $this->getCachePrefix() . ':amount_range_facets:' . md5($query);

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($query): array {
            $builder = Donation::search($query);
            $donations = $builder->take(1000)->get();
            $facets = [];

            foreach ($donations as $donation) {
                $range = $donation->getAmountRange();
                $facets[$range] = ($facets[$range] ?? 0) + 1;
            }

            $rangeOrder = ['under_25', '25_to_100', '100_to_500', '500_to_1000', 'over_1000'];
            $orderedFacets = [];
            foreach ($rangeOrder as $range) {
                if (isset($facets[$range])) {
                    $orderedFacets[$range] = $facets[$range];
                }
            }

            return $orderedFacets;
        });
    }

    /**
     * Get campaign facets.
     */
    /**
     * @return array<string, int>
     */
    public function getCampaignFacets(string $query = ''): array
    {
        $cacheKey = $this->getCachePrefix() . ':campaign_facets:' . md5($query);

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($query): array {
            $builder = Donation::search($query)->where('is_successful', true);
            $donations = $builder->take(1000)->get();
            $facets = [];

            foreach ($donations as $donation) {
                $campaignTitle = $donation->campaign?->title;
                if ($campaignTitle) {
                    $facets[$campaignTitle] = ($facets[$campaignTitle] ?? 0) + 1;
                }
            }

            arsort($facets);

            return array_slice($facets, 0, 20, true); // Top 20 campaigns
        });
    }

    /**
     * Get largest donations.
     *
     * @return Collection<int, Donation>
     */
    public function getLargestDonations(int $limit = 10): Collection
    {
        $cacheKey = $this->getCachePrefix() . ':largest:' . $limit;

        return cache()->remember($cacheKey, self::CACHE_TTL, fn () => Donation::search('')
            ->where('is_successful', true)
            ->orderBy('amount', 'desc')
            ->take($limit)
            ->get());
    }

    /**
     * Get recent successful donations.
     *
     * @return Collection<int, Donation>
     */
    public function getRecentSuccessful(int $limit = 10): Collection
    {
        $cacheKey = $this->getCachePrefix() . ':recent_successful:' . $limit;

        return cache()->remember($cacheKey, self::CACHE_TTL, fn () => Donation::search('')
            ->where('is_successful', true)
            ->orderBy('donated_at', 'desc')
            ->take($limit)
            ->get());
    }

    /**
     * Get donations requiring refund processing.
     *
     * @return Collection<int, Donation>
     */
    public function getRefundable(int $limit = 50): Collection
    {
        $cacheKey = $this->getCachePrefix() . ':refundable:' . $limit;

        return cache()->remember($cacheKey, self::CACHE_TTL, fn () => Donation::search('')
            ->where('can_be_refunded', true)
            ->orderBy('donated_at', 'desc')
            ->take($limit)
            ->get());
    }
}
