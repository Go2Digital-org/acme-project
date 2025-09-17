<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Infrastructure\Laravel\Resource\CampaignResource;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class SearchCampaignsController
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * Search campaigns with advanced filtering.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'min:2', 'max:255'],
            'search' => ['nullable', 'string', 'min:2', 'max:255'],
            'status' => ['nullable', 'in:draft,active,completed,cancelled'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $perPage = min((int) $request->query('per_page', 20), 100);
        $page = max((int) $request->query('page', 1), 1);

        $filters = [];

        // Accept both 'q' and 'search' parameters for compatibility
        $searchTerm = $request->query('q') ?? $request->query('search');
        if ($searchTerm) {
            $filters['search'] = $searchTerm;
        }

        if ($request->filled('status')) {
            $filters['status'] = $request->query('status');
        }

        if ($request->filled('organization_id')) {
            $filters['organization_id'] = (int) $request->query('organization_id');
        }

        $paginated = $this->campaignRepository->paginate(
            page: $page,
            perPage: $perPage,
            filters: $filters,
            sortBy: 'created_at',
            sortOrder: 'desc',
        );

        // Transform to resource collection
        $campaignResources = $paginated->getCollection()->map(fn ($campaign): array => (new CampaignResource($campaign))->toArray(request()));

        // Since we're transforming to array data, create a new response structure
        return ApiResponse::success([
            'data' => $campaignResources->toArray(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
            'links' => [
                'first' => $paginated->url(1),
                'last' => $paginated->url($paginated->lastPage()),
                'prev' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
            ],
        ], 'Search results retrieved successfully.');
    }
}
