<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Query;

use Modules\Donation\Application\ReadModel\DonationHistoryReadModel;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;

final readonly class GetDonationsByUserQueryHandler
{
    public function __construct(
        private DonationRepositoryInterface $donationRepository
    ) {}

    public function handle(GetDonationsByUserQuery $query): DonationHistoryReadModel
    {
        $filters = [
            'user_id' => $query->userId,
            'status' => $query->statuses,
            'date_from' => $query->dateFrom,
            'date_to' => $query->dateTo,
            'campaign_id' => $query->campaignId,
            'page' => $query->page,
            'per_page' => $query->perPage,
            'sort_by' => $query->sortBy,
            'sort_order' => $query->sortOrder,
        ];

        // Remove null filters
        $filters = array_filter($filters, fn (array|int|string|null $value): bool => $value !== null);

        $paginatedResults = $this->donationRepository->paginate(
            page: $query->page,
            perPage: $query->perPage,
            filters: $filters,
            sortBy: $query->sortBy,
            sortOrder: $query->sortOrder
        );

        // Transform to array structure expected by ReadModel
        $donations = [];
        $totalDonated = 0;
        $totalRefunded = 0;
        $campaignIds = [];
        $organizationIds = [];
        $categoryIds = [];
        $monthlyPattern = [];
        $yearlyPattern = [];
        $paymentMethods = [];
        $statusCounts = [
            'completed' => 0,
            'pending' => 0,
            'failed' => 0,
            'refunded' => 0,
        ];

        foreach ($paginatedResults->items() as $donation) {
            $donationArray = [
                'id' => $donation->id,
                'amount' => (float) $donation->amount,
                'currency' => $donation->currency ?? 'USD',
                'formatted_amount' => number_format((float) $donation->amount, 2) . ' ' . ($donation->currency ?? 'USD'),
                'status' => $donation->status,
                'status_label' => $this->getStatusLabel($donation->status->value),
                'message' => $donation->notes,
                'is_anonymous' => $donation->is_anonymous ?? false,
                'is_recurring' => $donation->is_recurring ?? false,
                'campaign_id' => $donation->campaign_id,
                'campaign_title' => $donation->campaign?->title,
                'campaign_organization_name' => $donation->campaign?->organization?->name,
                'payment_method' => $donation->payment_method,
                'transaction_id' => $donation->transaction_id,
                'has_matching' => $donation->has_matching ?? false,
                'matching_amount' => (float) ($donation->matching_amount ?? 0),
                'total_amount_with_matching' => (float) $donation->amount + (float) ($donation->matching_amount ?? 0),
                'is_corporate' => $donation->is_corporate ?? false,
                'corporate_name' => $donation->user?->name,
                'refunded_amount' => (float) ($donation->refunded_amount ?? 0),
                'created_at' => $donation->created_at?->toISOString(),
                'completed_at' => $donation->completed_at?->toISOString(),
            ];

            $donations[] = $donationArray;

            // Aggregate statistics
            if ($donation->status->value === 'completed') {
                $totalDonated += (float) $donation->amount;
            }

            $totalRefunded += (float) ($donation->refunded_amount ?? 0);

            if ($donation->campaign_id) {
                $campaignIds[] = $donation->campaign_id;
            }

            if ($donation->campaign?->organization_id) {
                $organizationIds[] = $donation->campaign->organization_id;
            }

            if ($donation->campaign?->category_id) {
                $categoryIds[] = $donation->campaign->category_id;
            }

            // Monthly pattern
            if ($donation->created_at) {
                $month = $donation->created_at->format('Y-m');
                $monthlyPattern[$month] = ($monthlyPattern[$month] ?? 0) + 1;

                $year = $donation->created_at->format('Y');
                $yearlyPattern[$year] = ($yearlyPattern[$year] ?? 0) + 1;
            }

            // Payment methods
            if ($donation->payment_method) {
                $paymentMethodValue = $donation->payment_method->value;
                $paymentMethods[$paymentMethodValue] = ($paymentMethods[$paymentMethodValue] ?? 0) + 1;
            }

            // Status counts
            $statusValue = $donation->status->value;
            if (isset($statusCounts[$statusValue])) {
                $statusCounts[$statusValue]++;
            }
        }

        // Calculate additional statistics
        $uniqueCampaigns = count(array_unique($campaignIds));
        $uniqueOrganizations = count(array_unique($organizationIds));
        $uniqueCategories = count(array_unique($categoryIds));
        $totalDonations = $paginatedResults->total();

        $avgDonationAmount = $totalDonations > 0 ? $totalDonated / $totalDonations : 0;
        $successRate = $totalDonations > 0 ? ($statusCounts['completed'] / $totalDonations) * 100 : 0;

        $data = [
            'user_id' => $query->userId,
            'current_page' => $paginatedResults->currentPage(),
            'per_page' => $paginatedResults->perPage(),
            'total' => $paginatedResults->total(),
            'last_page' => $paginatedResults->lastPage(),
            'donations' => $donations,
            'total_donated' => $totalDonated,
            'total_donation_count' => $totalDonations,
            'average_donation_amount' => $avgDonationAmount,
            'largest_donation_amount' => $donations !== [] ? max(array_column($donations, 'amount')) : 0,
            'smallest_donation_amount' => $donations !== [] ? min(array_column($donations, 'amount')) : 0,
            'total_refunded_amount' => $totalRefunded,
            'campaigns_supported' => $uniqueCampaigns,
            'organizations_supported' => $uniqueOrganizations,
            'categories_supported' => $uniqueCategories,
            'monthly_donation_pattern' => $monthlyPattern,
            'yearly_donation_pattern' => $yearlyPattern,
            'payment_method_breakdown' => $paymentMethods,
            'completed_donations_count' => $statusCounts['completed'],
            'pending_donations_count' => $statusCounts['pending'],
            'failed_donations_count' => $statusCounts['failed'],
            'refunded_donations_count' => $statusCounts['refunded'],
            'success_rate' => $successRate,
            'filters' => $filters,
        ];

        return new DonationHistoryReadModel($data);
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            'partially_refunded' => 'Partially Refunded',
            default => 'Unknown',
        };
    }
}
