<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Donation\Domain\Service\DonationStatusService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class ListDonationsController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private DonationRepositoryInterface $donationRepository,
        private DonationStatusService $donationStatusService,
    ) {}

    /**
     * List donations with pagination and filtering.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:pending,processing,completed,failed,cancelled,refunded'],
            'campaign_id' => ['nullable', 'integer', 'exists:campaigns,id'],
            'payment_method' => ['nullable', 'in:credit_card,bank_transfer,paypal,stripe'],
            'anonymous' => ['nullable', 'boolean'],
        ]);

        $perPage = min((int) $request->query('per_page', 20), 100);
        $page = max((int) $request->query('page', 1), 1);

        $filters = [];

        if ($request->filled('status')) {
            $filters['status'] = $request->query('status');
        }

        if ($request->filled('campaign_id')) {
            $filters['campaign_id'] = (int) $request->query('campaign_id');
        }

        if ($request->filled('payment_method')) {
            $filters['payment_method'] = $request->query('payment_method');
        }

        if ($request->filled('anonymous')) {
            $filters['anonymous'] = $request->boolean('anonymous');
        }

        // Only show user's own donations unless admin
        $filters['user_id'] = $this->getAuthenticatedUserId($request);

        $paginated = $this->donationRepository->paginate(
            page: $page,
            perPage: $perPage,
            filters: $filters,
            sortBy: 'created_at',
            sortOrder: 'desc',
        );

        // Transform using donation status service and maintain proper typing
        $donationData = $paginated->getCollection()->map(fn ($donation): array => $this->donationStatusService->getListingData($donation));

        // Create a new paginator instance with transformed data to maintain type safety
        $transformedPaginated = new LengthAwarePaginator(
            items: $donationData,
            total: $paginated->total(),
            perPage: $paginated->perPage(),
            currentPage: $paginated->currentPage(),
            options: [
                'path' => request()->url(),
                'pageName' => 'page',
            ],
        );

        return ApiResponse::paginated(
            paginator: $transformedPaginated,
            message: 'Donations retrieved successfully.',
        );
    }
}
