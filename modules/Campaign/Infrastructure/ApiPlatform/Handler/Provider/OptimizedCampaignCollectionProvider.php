<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\ApiPlatform\Handler\Provider;

use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Infrastructure\ApiPlatform\Resource\CampaignResource;
use Modules\Shared\Infrastructure\ApiPlatform\Provider\OptimizedCollectionProvider;

/**
 * @extends OptimizedCollectionProvider<Campaign>
 */
final class OptimizedCampaignCollectionProvider extends OptimizedCollectionProvider
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, string>  $sorts
     * @return Builder<Campaign>
     */
    protected function buildBaseQuery(string $locale, array $filters, array $sorts): Builder
    {
        $query = Campaign::query();

        // Eager load relations to prevent N+1 queries
        $query->with([
            'organization:id,name,category,verified',
            'employee:id,name,email',
            'donations:id,campaign_id,amount,status',
        ]);

        // Apply filters
        $this->applyFilters($query, $filters);

        // Apply sorting
        $this->applySorting($query, $sorts);

        // Apply locale-specific filtering if needed
        /** @var Builder<Campaign> $query */
        $query = $this->applyLocaleFiltering($query, $locale, $filters);

        return $query;
    }

    protected function transformToResource(Model $model, string $locale): object
    {
        /* @var Campaign $model */
        return CampaignResource::fromModel($model);
    }

    /** @return array<int, string> */
    protected function getAllowedFilters(): array
    {
        return [
            'id',
            'title',
            'status',
            'organization_id',
            'user_id',
            'start_date',
            'end_date',
            'created_at',
            'updated_at',
            'search',
            'locale',
        ];
    }

    /** @return array<int, string> */
    protected function getAllowedSorts(): array
    {
        return [
            'id',
            'title',
            'goal_amount',
            'current_amount',
            'start_date',
            'end_date',
            'created_at',
            'updated_at',
            'progress_percentage',
        ];
    }

    /**
     * @param  Builder<Campaign>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Campaign>
     */
    protected function eagerLoadRelations(Builder $query, array $filters): Builder
    {
        $relations = ['organization:id,name,category,verified', 'employee:id,name,email'];

        // Only load donations if we need them for filtering or sorting
        if (isset($filters['amount_range']) || isset($filters['donation_count'])) {
            $relations[] = 'donations:id,campaign_id,amount,status,created_at';
        }

        return $query->with($relations);
    }

    /**
     * @param  Builder<Campaign>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Campaign>
     */
    protected function addIndexHints(Builder $query, array $filters): Builder
    {
        // Add index hints for common filter combinations
        if (isset($filters['status']) && isset($filters['organization_id'])) {
            $query->fromRaw('campaigns USE INDEX (idx_campaigns_status_org)');

            return $query;
        }

        if (isset($filters['user_id'])) {
            $query->fromRaw('campaigns USE INDEX (idx_campaigns_user)');

            return $query;
        }

        if (isset($filters['status'])) {
            $query->fromRaw('campaigns USE INDEX (idx_campaigns_status)');
        }

        return $query;
    }

    /**
     * @param  Builder<Campaign>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Campaign>
     */
    protected function applyCaching(Builder $query, array $filters): Builder
    {
        // Cache queries for public campaign listings
        // Only call remember() if method exists (requires query builder extension)
        if (! isset($filters['user_id']) && ! isset($filters['organization_id']) && method_exists($query, 'remember')) {
            /** @var Builder<Campaign> $cachedQuery */
            $cachedQuery = $query->remember(600);

            // 10 minutes cache for public listings
            return $cachedQuery;
        }

        return parent::applyCaching($query, $filters);
    }

    /**
     * @param  Builder<Campaign>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Campaign>
     */
    protected function applyLocaleFiltering(Builder $query, string $locale, array $filters): Builder
    {
        // If we had translation tables, we would join them here based on locale
        // For now, we'll add locale context to the query for future use

        return $query;
    }

    /**
     * @param  Builder<Campaign>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $key => $value) {
            match ($key) {
                'id' => $query->where('id', $value),
                'title' => $query->where('title', 'LIKE', "%{$value}%"),
                'status' => $query->where('status', $value),
                'organization_id' => $query->where('organization_id', $value),
                'user_id' => $query->where('user_id', $value),
                'start_date' => $this->applyDateFilter($query, 'start_date', $value),
                'end_date' => $this->applyDateFilter($query, 'end_date', $value),
                'created_at' => $this->applyDateFilter($query, 'created_at', $value),
                'updated_at' => $this->applyDateFilter($query, 'updated_at', $value),
                'search' => $this->applySearchFilter($query, $value),
                default => null,
            };
        }
    }

    /**
     * @param  Builder<Campaign>  $query
     */
    private function applyDateFilter(Builder $query, string $field, mixed $value): void
    {
        if (! is_array($value)) {
            $query->whereDate($field, $value);

            return;
        }

        if (isset($value['before'])) {
            $query->where($field, '<=', $value['before']);
        }

        if (isset($value['after'])) {
            $query->where($field, '>=', $value['after']);
        }

        if (isset($value['strictly_before'])) {
            $query->where($field, '<', $value['strictly_before']);
        }

        if (isset($value['strictly_after'])) {
            $query->where($field, '>', $value['strictly_after']);
        }
    }

    /**
     * @param  Builder<Campaign>  $query
     */
    private function applySearchFilter(Builder $query, string $searchTerm): void
    {
        $searchFields = ['title', 'description'];

        // Use full-text search for large datasets (>10,000 records)
        $totalCampaigns = DB::table('campaigns')->count();

        if ($totalCampaigns > 10000) {
            $this->applyFullTextSearch($query, $searchTerm, $searchFields);

            // Also search in related organization names
            $query->orWhereHas('organization', function (Builder $orgQuery) use ($searchTerm): void {
                $orgQuery->where('name', 'LIKE', "%{$searchTerm}%");
            });

            return;
        }

        $this->applyMultiSearch($query, $searchTerm, $searchFields);

        // Also search in related organization names
        $query->orWhereHas('organization', function (Builder $orgQuery) use ($searchTerm): void {
            $orgQuery->where('name', 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * @param  Builder<Campaign>  $query
     * @param  array<string, string>  $sorts
     */
    private function applySorting(Builder $query, array $sorts): void
    {
        foreach ($sorts as $field => $direction) {
            match ($field) {
                'progress_percentage' => $this->applySortByProgressPercentage($query, $direction),
                'donation_count' => $this->applySortByDonationCount($query, $direction),
                default => $query->orderBy($field, $direction),
            };
        }

        // Default sorting
        if ($sorts === []) {
            $query->orderBy('created_at', 'desc');
        }
    }

    /**
     * @param  Builder<Campaign>  $query
     */
    private function applySortByProgressPercentage(Builder $query, string $direction): void
    {
        $query->orderByRaw("(current_amount / goal_amount) {$direction}");
    }

    /**
     * @param  Builder<Campaign>  $query
     */
    private function applySortByDonationCount(Builder $query, string $direction): void
    {
        // Note: donations_count is now a column, no need for withCount
        $query->orderBy('donations_count', $direction);
    }
}
