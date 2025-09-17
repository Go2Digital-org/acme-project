<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Controllers\Api;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Category\Application\Service\CategorySearchService;
use Modules\Donation\Application\Service\DonationSearchService;
use Modules\Organization\Application\Service\OrganizationSearchService;
use Modules\Shared\Application\Service\PageSearchService;
use Modules\Shared\Infrastructure\Laravel\Controllers\BaseController;
use Modules\User\Application\Service\UserSearchService;

/**
 * Unified search API controller providing search functionality across all models.
 */
final class SearchController extends BaseController
{
    public function __construct(
        private readonly OrganizationSearchService $organizationSearchService,
        private readonly UserSearchService $userSearchService,
        private readonly DonationSearchService $donationSearchService,
        private readonly CategorySearchService $categorySearchService,
        private readonly PageSearchService $pageSearchService
    ) {}

    /**
     * Global search across all models.
     */
    public function global(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:100',
            'models' => 'sometimes|array',
            'models.*' => 'string|in:organizations,users,donations,categories,pages',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return $this->respondWithError('Invalid search parameters', $validator->errors()->toArray(), 422);
        }

        $query = $request->input('q', '');
        $models = $request->input('models', ['organizations', 'users', 'donations', 'categories', 'pages']);
        $limit = (int) $request->input('limit', 10);

        try {
            $results = [];

            if (in_array('organizations', $models, true)) {
                $results['organizations'] = $this->organizationSearchService->suggest($query, $limit);
            }

            if (in_array('users', $models, true)) {
                $results['users'] = $this->userSearchService->suggest($query, $limit);
            }

            if (in_array('donations', $models, true)) {
                $results['donations'] = $this->donationSearchService->suggest($query, $limit);
            }

            if (in_array('categories', $models, true)) {
                $results['categories'] = $this->categorySearchService->suggest($query, $limit);
            }

            if (in_array('pages', $models, true)) {
                $results['pages'] = $this->pageSearchService->suggest($query, $limit);
            }

            return $this->respondWithSuccess('Global search completed', $results);
        } catch (Exception $e) {
            return $this->respondWithError('Search failed', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Search organizations.
     */
    public function organizations(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'sometimes|string|max:100',
            'category' => 'sometimes|string',
            'location' => 'sometimes|string',
            'verified_only' => 'sometimes|boolean',
            'sort_by' => 'sometimes|string|in:name,created_at,verification_date,campaigns_count',
            'sort_direction' => 'sometimes|string|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->respondWithError('Invalid search parameters', $validator->errors()->toArray(), 422);
        }

        try {
            $query = $request->input('q', '');
            $filters = [];

            if ($request->has('category')) {
                $filters['category'] = $request->input('category');
            }

            if ($request->boolean('verified_only')) {
                $filters['is_verified'] = true;
            }

            $results = $this->organizationSearchService->search(
                query: $query,
                filters: $filters,
                sortBy: $request->input('sort_by', 'created_at'),
                sortDirection: $request->input('sort_direction', 'desc'),
                perPage: (int) $request->input('per_page', 20),
                page: (int) $request->input('page', 1)
            );

            return $this->respondWithSuccess('Organizations search completed', [
                'data' => $results->items(),
                'meta' => [
                    'current_page' => $results->currentPage(),
                    'from' => $results->firstItem(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'to' => $results->lastItem(),
                    'total' => $results->total(),
                ],
            ]);
        } catch (Exception $e) {
            return $this->respondWithError('Organization search failed', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Search users.
     */
    public function users(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'sometimes|string|max:100',
            'department' => 'sometimes|string',
            'role' => 'sometimes|string',
            'organization_id' => 'sometimes|integer',
            'verified_only' => 'sometimes|boolean',
            'sort_by' => 'sometimes|string|in:name,created_at,last_login_at',
            'sort_direction' => 'sometimes|string|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->respondWithError('Invalid search parameters', $validator->errors()->toArray(), 422);
        }

        try {
            $query = $request->input('q', '');
            $filters = [];

            if ($request->has('department')) {
                $filters['department'] = $request->input('department');
            }

            if ($request->has('role')) {
                $filters['role'] = $request->input('role');
            }

            if ($request->has('organization_id')) {
                $filters['organization_id'] = $request->input('organization_id');
            }

            if ($request->boolean('verified_only')) {
                $filters['email_verified'] = true;
            }

            $results = $this->userSearchService->search(
                query: $query,
                filters: $filters,
                sortBy: $request->input('sort_by', 'name'),
                sortDirection: $request->input('sort_direction', 'asc'),
                perPage: (int) $request->input('per_page', 20),
                page: (int) $request->input('page', 1)
            );

            return $this->respondWithSuccess('Users search completed', [
                'data' => $results->items(),
                'meta' => [
                    'current_page' => $results->currentPage(),
                    'from' => $results->firstItem(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'to' => $results->lastItem(),
                    'total' => $results->total(),
                ],
            ]);
        } catch (Exception $e) {
            return $this->respondWithError('User search failed', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Search donations.
     */
    public function donations(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'sometimes|string|max:100',
            'status' => 'sometimes|string|in:pending,processing,completed,failed,cancelled,refunded',
            'campaign_id' => 'sometimes|integer',
            'user_id' => 'sometimes|integer',
            'amount_range' => 'sometimes|string|in:under_25,25_to_100,100_to_500,500_to_1000,over_1000',
            'payment_method' => 'sometimes|string',
            'successful_only' => 'sometimes|boolean',
            'recurring_only' => 'sometimes|boolean',
            'sort_by' => 'sometimes|string|in:donated_at,amount,created_at',
            'sort_direction' => 'sometimes|string|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->respondWithError('Invalid search parameters', $validator->errors()->toArray(), 422);
        }

        try {
            $query = $request->input('q', '');
            $filters = [];

            if ($request->has('status')) {
                $filters['status'] = $request->input('status');
            }

            if ($request->has('campaign_id')) {
                $filters['campaign_id'] = $request->input('campaign_id');
            }

            if ($request->has('user_id')) {
                $filters['user_id'] = $request->input('user_id');
            }

            if ($request->has('amount_range')) {
                $filters['amount_range'] = $request->input('amount_range');
            }

            if ($request->has('payment_method')) {
                $filters['payment_method'] = $request->input('payment_method');
            }

            if ($request->boolean('successful_only')) {
                $filters['is_successful'] = true;
            }

            if ($request->boolean('recurring_only')) {
                $filters['recurring'] = true;
            }

            $results = $this->donationSearchService->search(
                query: $query,
                filters: $filters,
                sortBy: $request->input('sort_by', 'donated_at'),
                sortDirection: $request->input('sort_direction', 'desc'),
                perPage: (int) $request->input('per_page', 20),
                page: (int) $request->input('page', 1)
            );

            return $this->respondWithSuccess('Donations search completed', [
                'data' => $results->items(),
                'meta' => [
                    'current_page' => $results->currentPage(),
                    'from' => $results->firstItem(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'to' => $results->lastItem(),
                    'total' => $results->total(),
                ],
            ]);
        } catch (Exception $e) {
            return $this->respondWithError('Donation search failed', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Search categories.
     */
    public function categories(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'sometimes|string|max:100',
            'status' => 'sometimes|string|in:active,inactive',
            'with_campaigns_only' => 'sometimes|boolean',
            'color' => 'sometimes|string',
            'sort_by' => 'sometimes|string|in:name,sort_order,campaigns_count,created_at',
            'sort_direction' => 'sometimes|string|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->respondWithError('Invalid search parameters', $validator->errors()->toArray(), 422);
        }

        try {
            $query = $request->input('q', '');
            $filters = [];

            if ($request->has('status')) {
                $filters['status'] = $request->input('status');
            }

            if ($request->has('color')) {
                $filters['color'] = $request->input('color');
            }

            if ($request->boolean('with_campaigns_only')) {
                $filters['has_active_campaigns'] = true;
            }

            $results = $this->categorySearchService->search(
                query: $query,
                filters: $filters,
                sortBy: $request->input('sort_by', 'sort_order'),
                sortDirection: $request->input('sort_direction', 'asc'),
                perPage: (int) $request->input('per_page', 20),
                page: (int) $request->input('page', 1)
            );

            return $this->respondWithSuccess('Categories search completed', [
                'data' => $results->items(),
                'meta' => [
                    'current_page' => $results->currentPage(),
                    'from' => $results->firstItem(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'to' => $results->lastItem(),
                    'total' => $results->total(),
                ],
            ]);
        } catch (Exception $e) {
            return $this->respondWithError('Category search failed', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Search pages.
     */
    public function pages(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'sometimes|string|max:100',
            'status' => 'sometimes|string|in:published,draft',
            'published_only' => 'sometimes|boolean',
            'sort_by' => 'sometimes|string|in:title,order,created_at,updated_at',
            'sort_direction' => 'sometimes|string|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->respondWithError('Invalid search parameters', $validator->errors()->toArray(), 422);
        }

        try {
            $query = $request->input('q', '');
            $filters = [];

            if ($request->has('status')) {
                $filters['status'] = $request->input('status');
            }

            if ($request->boolean('published_only')) {
                $filters['is_published'] = true;
            }

            $results = $this->pageSearchService->search(
                query: $query,
                filters: $filters,
                sortBy: $request->input('sort_by', 'order'),
                sortDirection: $request->input('sort_direction', 'asc'),
                perPage: (int) $request->input('per_page', 20),
                page: (int) $request->input('page', 1)
            );

            return $this->respondWithSuccess('Pages search completed', [
                'data' => $results->items(),
                'meta' => [
                    'current_page' => $results->currentPage(),
                    'from' => $results->firstItem(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'to' => $results->lastItem(),
                    'total' => $results->total(),
                ],
            ]);
        } catch (Exception $e) {
            return $this->respondWithError('Page search failed', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get search suggestions/autocomplete for a specific model.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:100',
            'model' => 'required|string|in:organizations,users,donations,categories,pages',
            'limit' => 'sometimes|integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return $this->respondWithError('Invalid suggestion parameters', $validator->errors()->toArray(), 422);
        }

        try {
            $query = $request->input('q');
            $model = $request->input('model');
            $limit = (int) $request->input('limit', 10);

            $suggestions = match ($model) {
                'organizations' => $this->organizationSearchService->getNameSuggestions($query, $limit),
                'users' => $this->userSearchService->getNameSuggestions($query, $limit),
                'donations' => $this->donationSearchService->getTransactionSuggestions($query, $limit),
                'categories' => $this->categorySearchService->getNameSuggestions($query, $limit),
                'pages' => $this->pageSearchService->getTitleSuggestions($query, $limit),
                default => collect(),
            };

            return $this->respondWithSuccess('Suggestions retrieved', [
                'suggestions' => $suggestions,
                'model' => $model,
                'query' => $query,
            ]);
        } catch (Exception $e) {
            return $this->respondWithError('Suggestion retrieval failed', ['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get search facets for filtering.
     */
    public function facets(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model' => 'required|string|in:organizations,users,donations,categories,pages',
            'q' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->respondWithError('Invalid facet parameters', $validator->errors()->toArray(), 422);
        }

        try {
            $model = $request->input('model');
            $query = $request->input('q', '');

            $facets = match ($model) {
                'organizations' => [
                    'categories' => $this->organizationSearchService->getCategoryFacets($query),
                    'locations' => $this->organizationSearchService->getLocationFacets($query),
                ],
                'users' => [
                    'departments' => $this->userSearchService->getDepartmentFacets($query),
                    'roles' => $this->userSearchService->getRoleFacets($query),
                    'organizations' => $this->userSearchService->getOrganizationFacets($query),
                ],
                'donations' => [
                    'statuses' => $this->donationSearchService->getStatusFacets($query),
                    'payment_methods' => $this->donationSearchService->getPaymentMethodFacets($query),
                    'amount_ranges' => $this->donationSearchService->getAmountRangeFacets($query),
                    'campaigns' => $this->donationSearchService->getCampaignFacets($query),
                ],
                'categories' => [
                    'colors' => $this->categorySearchService->getColorFacets($query),
                    'statuses' => $this->categorySearchService->getStatusFacets($query),
                ],
                'pages' => [
                    'statuses' => $this->pageSearchService->getStatusFacets($query),
                ],
                default => [],
            };

            return $this->respondWithSuccess('Facets retrieved', [
                'facets' => $facets,
                'model' => $model,
                'query' => $query,
            ]);
        } catch (Exception $e) {
            return $this->respondWithError('Facet retrieval failed', ['message' => $e->getMessage()], 500);
        }
    }
}
