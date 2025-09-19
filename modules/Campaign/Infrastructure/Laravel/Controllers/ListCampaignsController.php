<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Infrastructure\Laravel\Http\Resources\CampaignListResource;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class ListCampaignsController
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * List all campaigns with optimized pagination, filtering, and field selection.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Validate request parameters
        $validated = $request->validate([
            'per_page' => 'integer|between:1,100',
            'page' => 'integer|min:1',
            'sort_by' => 'string|in:created_at,updated_at,current_amount,end_date,donations_count,is_featured',
            'sort_order' => 'string|in:asc,desc',
            'status' => 'string|in:active,completed,draft,paused,cancelled,expired,pending_approval,rejected',
            'organization_id' => 'integer|exists:organizations,id',
            'search' => 'string|max:500',
            'filter' => 'string|in:active-only,ending-soon,recent,popular,completed,favorites',
            'fields' => 'array',
            'fields.*' => 'string|in:financial,timing,creator,organization,metadata,actions',
            'include' => 'string',
        ]);

        $perPage = $validated['per_page'] ?? 20;
        $page = $validated['page'] ?? 1;
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';

        // Build filters from validated input
        $filters = [];
        if (isset($validated['status'])) {
            $filters['status'] = $validated['status'];
        }
        if (isset($validated['organization_id'])) {
            $filters['organization_id'] = $validated['organization_id'];
        }
        if (isset($validated['search'])) {
            $filters['search'] = $validated['search'];
        }
        if (isset($validated['filter'])) {
            $filters['filter'] = $validated['filter'];
        }

        // Optimize query with selective relationship loading based on requested fields
        $relationships = [];
        $requestedFields = $validated['fields'] ?? ['financial', 'timing', 'creator', 'organization'];

        if (in_array('creator', $requestedFields)) {
            $relationships[] = 'creator:id,name,title';
        }
        if (in_array('organization', $requestedFields)) {
            $relationships[] = 'organization:id,name,logo_url';
        }

        // Get paginated results with optimized query
        $paginated = $this->campaignRepository->searchWithTranslations(
            page: $page,
            perPage: $perPage,
            filters: $filters,
            sortBy: $sortBy,
            sortOrder: $sortOrder,
        );

        // Eager load relationships if needed to prevent N+1 queries
        if ($relationships !== []) {
            /** @var Collection<int, Campaign> $campaigns */
            $campaigns = $paginated->getCollection();
            $campaigns->load($relationships);
        }

        // Transform using optimized resource
        $campaigns = CampaignListResource::collection($paginated->getCollection());

        // Calculate cache age based on content type
        $cacheMaxAge = $this->calculateCacheMaxAge($filters);

        $headers = [
            'Cache-Control' => "public, max-age={$cacheMaxAge}, s-maxage={$cacheMaxAge}",
            'Vary' => 'Accept, Accept-Encoding, Authorization',
            'X-Total-Count' => (string) $paginated->total(),
            'X-Page-Count' => (string) $paginated->lastPage(),
            'X-Per-Page' => (string) $paginated->perPage(),
            'X-Current-Page' => (string) $paginated->currentPage(),
        ];

        return ApiResponse::paginated(
            $paginated->setCollection($campaigns->collection),
            'Campaigns retrieved successfully',
            $headers
        );
    }

    /**
     * Calculate appropriate cache max-age based on data volatility.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    private function calculateCacheMaxAge(array $filters): int
    {
        // Real-time data needs shorter cache
        if (isset($filters['filter']) && in_array($filters['filter'], ['ending-soon', 'recent'])) {
            return 60; // 1 minute
        }

        // Search results cache for 2 minutes
        if (isset($filters['search'])) {
            return 120;
        }

        // General list caching for 5 minutes
        return 300;
    }
}
