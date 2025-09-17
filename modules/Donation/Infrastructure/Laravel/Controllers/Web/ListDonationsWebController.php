<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Donation\Domain\Service\DonationStatusService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class ListDonationsWebController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private DonationRepositoryInterface $donationRepository,
        private DonationStatusService $donationStatusService,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $this->getAuthenticatedUser($request);

        // User is already authenticated by the trait method

        $filters = [
            'user_id' => $user->getId(), // Only show current user's donations
            'status' => $request->get('status'),
            'campaign_id' => $request->get('campaign'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        // Remove null values from filters
        $filters = array_filter($filters, fn ($value): bool => $value !== null);

        $page = max(1, (int) $request->get('page', 1));
        $perPage = max(1, min(50, (int) $request->get('per_page', 15)));

        $donationsPaginated = $this->donationRepository->paginate(
            page: $page,
            perPage: $perPage,
            filters: $filters,
            sortBy: $request->get('sort_by', 'created_at'),
            sortOrder: $request->get('sort_order', 'desc'),
        );

        // Add status metrics to each donation without transforming to array
        $statusMetrics = [];
        $donationsWithMetrics = $donationsPaginated->getCollection()->map(function ($donation) use (&$statusMetrics): Donation {
            $statusMetrics[$donation->id] = $this->donationStatusService->getStatusMetrics($donation);

            return $donation;
        });
        $donationsPaginated->setCollection($donationsWithMetrics);

        // Get user statistics in a single optimized query
        $userStats = $this->donationRepository->getEmployeeStats($user->getId());

        // Get available filters (years and campaigns) in optimized queries
        $availableFilters = $this->donationRepository->getAvailableFilters($user->getId());

        // Extract data from the optimized queries
        /** @var Collection<int, int> $availableYears */
        $availableYears = collect((array) $availableFilters['years']);
        /** @var Collection<int, array<string, mixed>> $supportedCampaigns */
        $supportedCampaigns = collect((array) $availableFilters['campaigns']);

        return view('donations.index', [
            'donations' => $donationsPaginated,
            'statusMetrics' => $statusMetrics,
            'totalDonated' => $userStats['total_amount'],
            'campaignsSupported' => $userStats['campaigns_supported'],
            'thisYearDonations' => $availableFilters['this_year_count'],
            'averageDonation' => $userStats['average_amount'],
            'dashboardMetrics' => [
                'total' => $userStats['total_donations'],
                'confirmedAmount' => $userStats['total_amount'],
                'averageAmount' => $userStats['average_amount'],
                'largestAmount' => $userStats['largest_amount'],
            ],
            'availableYears' => $availableYears,
            'supportedCampaigns' => $supportedCampaigns->map(fn (array $campaign): object => (object) ['id' => $campaign['id'], 'title' => $campaign['title']]),
            'filters' => array_merge([
                'status' => '',
                'campaign' => '',
                'date_from' => '',
                'date_to' => '',
                'sort_by' => 'created_at',
                'sort_order' => 'desc',
                'per_page' => 15,
            ], $request->except('user_id')), // Don't expose user_id in filters
        ]);
    }
}
