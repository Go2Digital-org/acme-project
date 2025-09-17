<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Donation\Domain\Model\Donation;
use Modules\Shared\Domain\Export\DonationExportRepositoryInterface;

class DonationExportRepository implements DonationExportRepositoryInterface
{
    public function __construct(
        private readonly Donation $model,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Model>
     */
    public function getExportQuery(array $filters = []): Builder
    {
        return $this->getDonationsWithRelations($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Model>
     */
    public function getDonationsWithRelations(array $filters = []): Builder
    {
        $query = $this->model->query()
            ->with(['campaign', 'user', 'campaign.organization'])
            ->orderBy('donated_at', 'desc');

        return $this->applyFilters($query, $filters); // @phpstan-ignore-line argument.type
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Model>
     */
    public function getDonationSummaryQuery(array $filters = []): Builder
    {
        $query = $this->model->query()
            ->selectRaw('
                status,
                COUNT(*) as donation_count,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount,
                SUM(CASE WHEN processing_fee IS NOT NULL THEN processing_fee ELSE 0 END) as total_fees
            ')
            ->groupBy('status')
            ->orderBy('total_amount', 'desc');

        return $this->applyBasicFilters($query, $filters); // @phpstan-ignore-line argument.type
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Model>
     */
    public function getDonationsByCampaignQuery(array $filters = []): Builder
    {
        $query = $this->model->query()
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->join('organizations', 'campaigns.organization_id', '=', 'organizations.id')
            ->selectRaw('
                campaigns.title as campaign_title,
                organizations.name as organization_name,
                COUNT(donations.id) as donation_count,
                SUM(donations.amount) as total_raised,
                AVG(donations.amount) as average_donation,
                campaigns.goal_amount,
                (SUM(donations.amount) / campaigns.goal_amount * 100) as progress_percentage,
                COUNT(DISTINCT donations.user_id) as unique_donors
            ')
            ->where('donations.status', 'completed')
            ->groupBy('campaigns.id', 'campaigns.title', 'organizations.name', 'campaigns.goal_amount')
            ->orderBy('total_raised', 'desc');

        if (! empty($filters['date_from'])) {
            $query->where('donations.donated_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('donations.donated_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['campaign_ids'])) {
            $query->whereIn('campaigns.id', $filters['campaign_ids']);
        }

        return $query; // @phpstan-ignore-line return.type
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Model>
     */
    public function getDonationsByEmployeeQuery(array $filters = []): Builder
    {
        $query = $this->model->query()
            ->join('users', 'donations.user_id', '=', 'users.id')
            ->selectRaw('
                users.name,
                users.id as user_id,
                COUNT(donations.id) as donation_count,
                SUM(donations.amount) as total_donated,
                AVG(donations.amount) as average_donation,
                MIN(donations.donated_at) as first_donation,
                MAX(donations.donated_at) as latest_donation,
                SUM(CASE WHEN donations.is_recurring = 1 THEN 1 ELSE 0 END) as recurring_donations
            ')
            ->where('donations.status', 'completed')
            ->where('donations.is_anonymous', false)
            ->groupBy('users.id', 'users.name')
            ->orderBy('total_donated', 'desc');

        if (! empty($filters['date_from'])) {
            $query->where('donations.donated_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('donations.donated_at', '<=', $filters['date_to']);
        }

        // Department filtering removed as department field was removed from User model

        if (! empty($filters['user_ids'])) {
            $query->whereIn('users.id', $filters['user_ids']);
        }

        return $query; // @phpstan-ignore-line return.type
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Model>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        // CRITICAL: Apply user_id filter for security - users can only export their own donations
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        if (! empty($filters['payment_method'])) {
            $query->whereIn('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('donated_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('donated_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['amount_min'])) {
            $query->where('amount', '>=', $filters['amount_min']);
        }

        if (! empty($filters['amount_max'])) {
            $query->where('amount', '<=', $filters['amount_max']);
        }

        if (isset($filters['is_anonymous'])) {
            $query->where('is_anonymous', $filters['is_anonymous']);
        }

        if (isset($filters['is_recurring'])) {
            $query->where('is_recurring', $filters['is_recurring']);
        }

        if (isset($filters['tax_deductible'])) {
            $query->where('tax_deductible', $filters['tax_deductible']);
        }

        return $query;
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Model>
     */
    private function applyBasicFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('donated_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('donated_at', '<=', $filters['date_to']);
        }

        return $query;
    }
}
