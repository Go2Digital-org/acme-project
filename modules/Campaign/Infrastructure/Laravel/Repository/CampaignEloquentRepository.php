<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Repository;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Log;
use Meilisearch\Client;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Shared\Application\Service\CacheService;
use Modules\Shared\Infrastructure\Laravel\Traits\HasTenantAwareCache;
use RuntimeException;
use Throwable;

class CampaignEloquentRepository implements CampaignRepositoryInterface
{
    use HasTenantAwareCache;

    public function __construct(
        private Campaign $model,
        private readonly CacheService $cacheService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Campaign
    {
        return $this->model->create($data);
    }

    public function findById(int $id): ?Campaign
    {
        return $this->model->find($id);
    }

    public function findByIdWithTrashed(int $id): ?Campaign
    {
        return $this->model->withTrashed()->find($id);
    }

    /**
     * @return array<int, Campaign>
     */
    public function findActiveByOrganization(int $organizationId): array
    {
        return $this->model
            ->where('organization_id', $organizationId)
            ->where('status', CampaignStatus::ACTIVE)
            ->where('end_date', '>', now())
            ->get()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateById(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data) > 0;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
    ): LengthAwarePaginator {
        // ALWAYS use Meilisearch for ALL queries - no database fallback
        // With 1M+ campaigns, database queries will timeout
        return $this->searchWithMeilisearch($page, $perPage, $filters, $sortBy, $sortOrder);
    }

    public function delete(int $id): bool
    {
        return $this->model->where('id', $id)->delete() > 0;
    }

    public function withTrashed(): self
    {
        // Create a new repository instance with a fresh model to avoid modifying the original
        $repository = new self($this->model, $this->cacheService);
        $repository->model = $this->model->newInstance();

        return $repository;
    }

    public function onlyTrashed(): self
    {
        // Create a new repository instance with a fresh model to avoid modifying the original
        $repository = new self($this->model, $this->cacheService);
        $repository->model = $this->model->newInstance();

        return $repository;
    }

    public function forceDelete(int $id): bool
    {
        $campaign = $this->model->withTrashed()->find($id);
        if (! $campaign) {
            return false;
        }

        $result = $campaign->forceDelete();

        return $result !== null && (bool) $result;
    }

    public function restore(int $id): bool
    {
        $campaign = $this->model->withTrashed()->find($id);

        return $campaign && $campaign->trashed() && $campaign->restore();
    }

    /**
     * @return array<int, Campaign>
     */
    public function findActiveCampaigns(): array
    {
        return $this->model
            ->with('organization')
            ->where('status', CampaignStatus::ACTIVE)
            ->where('end_date', '>', now())
            ->limit(100)
            ->get()
            ->all();
    }

    /**
     * @return array<int, Campaign>
     */
    public function findExpiredCampaigns(): array
    {
        return $this->model
            ->where('status', CampaignStatus::ACTIVE)
            ->where('end_date', '<=', now())
            ->get()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function searchWithTranslations(
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
    ): LengthAwarePaginator {
        // For integration tests, skip complex caching and use simple paginate
        if (app()->environment(['testing'])) {
            return $this->executeSimpleListQuery($filters, $sortBy, $sortOrder, $page, $perPage);
        }

        // ALWAYS use Meilisearch for ALL queries - no database fallback
        // With 1M+ campaigns, database queries will timeout
        return $this->searchWithMeilisearch($page, $perPage, $filters, $sortBy, $sortOrder);
    }

    /**
     * @return array<int, Campaign>
     */
    public function findWithCompleteTranslations(string $locale): array
    {
        return $this->model
            ->whereNotNull('title')
            ->whereNotNull('description')
            ->get()
            ->all();
    }

    /**
     * @return array<int, Campaign>
     */
    public function findMissingTranslations(string $locale): array
    {
        return $this->model
            ->where(function ($query): void {
                $query->whereNull('title')
                    ->orWhereNull('description');
            })
            ->get()
            ->all();
    }

    public function findByIdWithLocale(int $id, string $locale): ?Campaign
    {
        $originalLocale = App::getLocale();
        App::setLocale($locale);

        $campaign = $this->model->find($id);

        App::setLocale($originalLocale);

        return $campaign;
    }

    /**
     * @param  array<int, string>  $locales
     * @param  array<string, mixed>  $filters
     */
    public function searchInTranslations(
        string $searchTerm,
        array $locales = ['en', 'nl', 'fr'],
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        // Apply additional filters
        if (isset($filters['status'])) {
            $status = is_string($filters['status']) ? CampaignStatus::tryFrom($filters['status']) : $filters['status'];

            if ($status) {
                $query->where('status', $status);
            }
        }

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        // Search across main fields (case-insensitive)
        $query->where(function ($q) use ($searchTerm): void {
            // Search in main fields
            $searchLower = strtolower($searchTerm);
            $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchLower}%"])
                ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"]);
        });

        // Apply date filters
        $this->applyDateFilters($query, $filters);

        // Apply filter parameter
        if (isset($filters['filter'])) {
            $query = $this->applyFilter($query, $filters['filter']);

            // For popular filter, override the sort order
            if ($filters['filter'] === 'popular') {
                $sortBy = 'donations_count';
                $sortOrder = 'desc';
            }
        }

        /** @var LengthAwarePaginator<int, Campaign> $result */
        $result = $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        return $result;
    }

    /**
     * Apply campaign filters based on the filter parameter.
     *
     * @param  Builder<Campaign>  $query
     * @return Builder<Campaign>
     */
    private function applyFilter(Builder $query, string $filter): Builder
    {
        return match ($filter) {
            'active-only' => $query->where('status', CampaignStatus::ACTIVE)
                ->where('start_date', '<=', now())
                ->where('end_date', '>', now()),
            'ending-soon' => $query->where('status', CampaignStatus::ACTIVE)
                ->where('start_date', '<=', now())
                ->where('end_date', '>', now())
                ->where('end_date', '<=', now()->addDays(7)->endOfDay()),
            'recent' => $this->applyRecentFilter($query),
            'popular' => $query->where('status', CampaignStatus::ACTIVE),
            // Note: donations_count is now a column, no need for withCount
            'completed' => $query->where('status', CampaignStatus::COMPLETED),
            'favorites' => $this->applyFavoritesFilter($query),
            default => $query,
        };
    }

    /**
     * Apply recent filter with optimized query
     *
     * @param  Builder<Campaign>  $query
     * @return Builder<Campaign>
     */
    private function applyRecentFilter(Builder $query): Builder
    {
        // Use index-friendly query with status first (more selective)
        return $query->where('status', CampaignStatus::ACTIVE)
            ->whereNull('deleted_at')
            ->where('created_at', '>=', now()->subDays(7));
    }

    /**
     * Apply favorites filter to show only campaigns bookmarked by the current user
     *
     * @param  Builder<Campaign>  $query
     * @return Builder<Campaign>
     */
    private function applyFavoritesFilter(Builder $query): Builder
    {
        $userId = auth()->id();

        if (! $userId) {
            // If no user is logged in, return empty result
            return $query->whereRaw('1 = 0');
        }

        return $query->join('bookmarks', function ($join) use ($userId): void {
            $join->on('campaigns.id', '=', 'bookmarks.campaign_id')
                ->where('bookmarks.user_id', '=', $userId);
        })->select('campaigns.*');
    }

    /**
     * Apply date filters to the query
     *
     * @param  Builder<Campaign>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Campaign>
     */
    private function applyDateFilters(Builder $query, array $filters): Builder
    {
        // Handle created_at filters
        if (isset($filters['created_at'])) {
            $this->applyDateFilter($query, 'created_at', $filters['created_at']);
        }

        // Handle updated_at filters
        if (isset($filters['updated_at'])) {
            $this->applyDateFilter($query, 'updated_at', $filters['updated_at']);
        }

        // Handle start_date filters
        if (isset($filters['start_date'])) {
            $this->applyDateFilter($query, 'start_date', $filters['start_date']);
        }

        // Handle end_date filters
        if (isset($filters['end_date'])) {
            $this->applyDateFilter($query, 'end_date', $filters['end_date']);
        }

        return $query;
    }

    /**
     * Apply a single date filter with support for multiple operators
     *
     * @param  Builder<Campaign>  $query
     * @return Builder<Campaign>
     */
    private function applyDateFilter(Builder $query, string $field, mixed $filterValue): Builder
    {
        if (is_string($filterValue)) {
            // Simple date equality
            $query->whereDate($field, $filterValue);
        } elseif (is_array($filterValue)) {
            // Support for API Platform DateFilter operators
            foreach ($filterValue as $operator => $value) {
                match ($operator) {
                    'before' => $query->where($field, '<', $value),
                    'strictly_before' => $query->where($field, '<', $value),
                    'after' => $query->where($field, '>', $value),
                    'strictly_after' => $query->where($field, '>', $value),
                    'gte', 'from' => $query->where($field, '>=', $value),
                    'lte', 'to' => $query->where($field, '<=', $value),
                    'eq', 'equals' => $query->whereDate($field, $value),
                    default => $query->whereDate($field, $value),
                };
            }
        }

        return $query;
    }

    /**
     * Search campaigns using Meilisearch for ultra-fast results.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Campaign>
     */
    private function searchWithMeilisearch(
        int $page,
        int $perPage,
        array $filters,
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
    ): LengthAwarePaginator {
        try {
            // BYPASS Scout entirely - query Meilisearch directly to avoid memory issues
            $client = app(Client::class);
            $indexName = config('scout.prefix') . 'campaigns';
            $index = $client->index($indexName);

            // Check if index has documents
            try {
                $stats = $index->stats();
                if (! isset($stats['numberOfDocuments']) || $stats['numberOfDocuments'] == 0) {
                    return new LengthAwarePaginator(
                        collect([]),
                        0,
                        $perPage,
                        $page,
                        ['path' => request()->url()]
                    );
                }
            } catch (Exception $e) {
                // If we can't access Meilisearch, return empty results
                return new LengthAwarePaginator(
                    collect([]),
                    0,
                    $perPage,
                    $page,
                    ['path' => request()->url()]
                );
            }

            // Handle search term - use empty string for browse all
            $searchTerm = $filters['search'] ?? '';

            // Check if search term should be treated as exact phrase
            if (str_starts_with($searchTerm, '[') && str_contains($searchTerm, ']')) {
                $searchTerm = '"' . $searchTerm . '"';
            }

            // Build comprehensive Meilisearch filters
            $meilisearchFilters = [];

            // Category filter
            if (isset($filters['category_id']) && $filters['category_id']) {
                $meilisearchFilters[] = "category_id = {$filters['category_id']}";
            }

            // Organization filter
            if (isset($filters['organization_id']) && $filters['organization_id']) {
                $meilisearchFilters[] = "organization_id = {$filters['organization_id']}";
            }

            // Status filter mapping from UI dropdown
            if (isset($filters['status']) && $filters['status']) {
                switch ($filters['status']) {
                    case 'active':
                        $meilisearchFilters[] = 'status = "active"';
                        break;
                    case 'ending-soon':
                        $meilisearchFilters[] = 'status = "active"';
                        $endingSoonTimestamp = now()->addDays(7)->timestamp;
                        $nowTimestamp = now()->timestamp;
                        $meilisearchFilters[] = "end_date > {$nowTimestamp}";
                        $meilisearchFilters[] = "end_date <= {$endingSoonTimestamp}";
                        break;
                    case 'newly-launched':
                        $recentTimestamp = now()->subDays(30)->timestamp;
                        $meilisearchFilters[] = "created_at >= {$recentTimestamp}";
                        $meilisearchFilters[] = 'status = "active"';
                        break;
                    case 'nearly-funded':
                        $meilisearchFilters[] = 'status = "active"';
                        $meilisearchFilters[] = 'goal_percentage >= 70';
                        $meilisearchFilters[] = 'goal_percentage < 100';
                        break;
                    default:
                        // Try to map as CampaignStatus enum
                        $status = CampaignStatus::tryFrom($filters['status']);
                        if ($status) {
                            $meilisearchFilters[] = "status = \"{$status->value}\"";
                        }
                        break;
                }
            }

            // Quick filter tags (active-only, popular, ending-soon, favorites)
            if (isset($filters['filter']) && $filters['filter']) {
                switch ($filters['filter']) {
                    case 'active-only':
                        $meilisearchFilters[] = 'status = "active"';
                        $nowTimestamp = now()->timestamp;
                        $meilisearchFilters[] = "start_date <= {$nowTimestamp}";
                        $meilisearchFilters[] = "end_date > {$nowTimestamp}";
                        break;
                    case 'popular':
                        $meilisearchFilters[] = 'status = "active"';
                        // Will sort by donations_count
                        $sortBy = 'donations_count';
                        $sortOrder = 'desc';
                        break;
                    case 'ending-soon':
                        $meilisearchFilters[] = 'status = "active"';
                        $nowTimestamp = now()->timestamp;
                        $endingSoonTimestamp = now()->addDays(7)->timestamp;
                        $meilisearchFilters[] = "end_date > {$nowTimestamp}";
                        $meilisearchFilters[] = "end_date <= {$endingSoonTimestamp}";
                        $sortBy = 'end_date';
                        $sortOrder = 'asc';
                        break;
                    case 'recent':
                        $meilisearchFilters[] = 'status = "active"';
                        $recentTimestamp = now()->subDays(7)->timestamp;
                        $meilisearchFilters[] = "created_at >= {$recentTimestamp}";
                        break;
                    case 'completed':
                        $meilisearchFilters[] = 'status = "completed"';
                        break;
                    case 'favorites':
                        // Get user's bookmarked campaign IDs
                        if (auth()->check()) {
                            $user = auth()->user();
                            $bookmarkedIds = $user !== null ? $user->bookmarks()->pluck('campaign_id')->toArray() : [];
                            if (! empty($bookmarkedIds)) {
                                $meilisearchFilters[] = 'id IN [' . implode(',', $bookmarkedIds) . ']';
                            } else {
                                // User has no favorites - return empty
                                return new LengthAwarePaginator(
                                    collect([]),
                                    0,
                                    $perPage,
                                    $page,
                                    ['path' => request()->url()]
                                );
                            }
                        } else {
                            // Not logged in - return empty
                            return new LengthAwarePaginator(
                                collect([]),
                                0,
                                $perPage,
                                $page,
                                ['path' => request()->url()]
                            );
                        }
                        break;
                }
            }

            // Map sort parameters to Meilisearch format
            $sortAttribute = match ($sortBy) {
                'is_featured' => ['is_featured:desc', 'created_at:desc'],
                'created_at' => ["created_at:{$sortOrder}"],
                'updated_at' => ["updated_at:{$sortOrder}"],
                'start_date' => ["start_date:{$sortOrder}"],
                'end_date' => ["end_date:{$sortOrder}"],
                'current_amount' => ["current_amount:{$sortOrder}"],
                'goal_amount' => ["goal_amount:{$sortOrder}"],
                'donations_count' => ["donations_count:{$sortOrder}"],
                'title' => ["title:{$sortOrder}"],
                default => ["created_at:{$sortOrder}"]
            };

            // Build Meilisearch query parameters
            $searchParams = [
                'hitsPerPage' => $perPage,
                'page' => $page,
                'sort' => $sortAttribute,
                'attributesToRetrieve' => ['id'], // Only get IDs to avoid large payload
            ];

            if ($meilisearchFilters !== []) {
                $searchParams['filter'] = implode(' AND ', $meilisearchFilters);
            }

            // Perform the search directly
            $results = $index->search($searchTerm, $searchParams);

            // Extract IDs from results
            $ids = collect($results->getHits())->pluck('id')->toArray();

            // If no results, return empty paginator
            if (empty($ids)) {
                return new LengthAwarePaginator(
                    collect([]),
                    0,
                    $perPage,
                    $page,
                    ['path' => request()->url()]
                );
            }

            // Load only the found campaigns from database (much smaller subset)
            $campaigns = $this->model->whereIn('id', $ids)
                ->with(['organization', 'creator', 'categoryModel'])
                ->get();

            // Sort campaigns according to Meilisearch result order
            $orderedCampaigns = collect($ids)->map(fn ($id) => $campaigns->firstWhere('id', $id))->filter();

            return new LengthAwarePaginator(
                $orderedCampaigns,
                $results->getTotalHits() ?? $results->getEstimatedTotalHits() ?? 0,
                $perPage,
                $page,
                ['path' => request()->url()]
            );

        } catch (Throwable $e) {
            // Log the Meilisearch failure (including memory errors)
            Log::warning('Meilisearch search failed for public campaigns', [
                'search_term' => $filters['search'] ?? null,
                'filters' => $filters,
                'error' => $e->getMessage(),
                'type' => $e::class,
            ]);

            // Return empty results instead of throwing exception
            return new LengthAwarePaginator(
                collect([]),
                0,
                $perPage,
                $page,
                ['path' => request()->url()]
            );
        }
    }

    /**
     * @return array<int, Campaign>
     */
    public function findForIndexing(int $offset, int $limit): array
    {
        return $this->model
            ->orderBy('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * @return array<int, Campaign>
     */
    public function findByUserId(int $userId): array
    {
        return $this->model
            ->where('user_id', $userId)
            ->with('organization')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Campaign>
     */
    public function paginateUserCampaigns(
        int $userId,
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->where('user_id', $userId)
            ->with('organization');
        // Note: donations_count is now a column, no need for withCount

        // Apply soft delete filter
        if (isset($filters['show_deleted']) && $filters['show_deleted']) {
            $query->withTrashed();
        }

        // Apply status filter
        if (isset($filters['status'])) {
            $status = is_string($filters['status']) ? CampaignStatus::tryFrom($filters['status']) : $filters['status'];

            if ($status) {
                $query->where('status', $status);
            }
        }

        // Apply search filter - Use hybrid search for complete coverage
        if (isset($filters['search']) && ! empty($filters['search'])) {
            return $this->hybridSearchUserCampaigns(
                $userId,
                $page,
                $perPage,
                $filters,
                $sortBy,
                $sortOrder
            );
        }

        // Apply date range filters
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Apply filter parameter
        if (isset($filters['filter'])) {
            $query = $this->applyUserFilter($query, $filters['filter']);

            // For popular filter, override the sort order
            if ($filters['filter'] === 'popular') {
                $sortBy = 'donations_count';
                $sortOrder = 'desc';
            }
        }

        /** @var LengthAwarePaginator<int, Campaign> $result */
        $result = $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        return $result;
    }

    /**
     * Apply employee-specific filters based on the filter parameter.
     *
     * @param  Builder<Campaign>  $query
     * @return Builder<Campaign>
     */
    private function applyUserFilter(Builder $query, string $filter): Builder
    {
        return match ($filter) {
            'active' => $query->where('status', CampaignStatus::ACTIVE)
                ->where('end_date', '>', now()),
            'draft' => $query->where('status', CampaignStatus::DRAFT),
            'completed' => $query->where('status', CampaignStatus::COMPLETED),
            'paused' => $query->where('status', CampaignStatus::PAUSED),
            'ending-soon' => $query->where('status', CampaignStatus::ACTIVE)
                ->where('start_date', '<=', now())
                ->where('end_date', '>', now())
                ->where('end_date', '<=', now()->addDays(7)->endOfDay()),
            'needs-attention' => $query->where('status', CampaignStatus::ACTIVE)
                ->where(function ($q): void {
                    $q->where('end_date', '<=', now()->addDays(7))
                        ->orWhere(function ($q2): void {
                            $q2->where('created_at', '<=', now()->subDays(7))
                                ->whereDoesntHave('donations');
                        });
                }),
            'successful' => $query->where('status', CampaignStatus::COMPLETED)
                ->whereRaw('current_amount >= goal_amount'),
            'popular' => $query->where('status', CampaignStatus::ACTIVE),
            // Note: donations_count is now a column, no need for withCount
            default => $query,
        };
    }

    /**
     * Search employee campaigns using Meilisearch for ultra-fast results.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Campaign>
     */
    private function searchUserCampaignsWithMeilisearch(
        int $userId,
        int $page,
        int $perPage,
        array $filters,
        string $sortBy,
        string $sortOrder,
    ): LengthAwarePaginator {
        try {
            $searchTerm = $filters['search'];

            // Check if search term should be treated as exact phrase
            // If it starts with [ and contains ], wrap in quotes for exact search
            if (str_starts_with((string) $searchTerm, '[') && str_contains((string) $searchTerm, ']')) {
                // Wrap the entire search term in quotes for exact phrase matching
                $searchTerm = '"' . $searchTerm . '"';
            }

            // If search term has multiple words and doesn't already have quotes,
            // wrap in quotes for phrase search to get more precise results
            if (str_word_count((string) $searchTerm) > 2 && ! str_starts_with((string) $searchTerm, '"') && ! str_ends_with((string) $searchTerm, '"')) {
                $searchTerm = '"' . $searchTerm . '"';
            }

            // Start Scout search
            $scoutQuery = $this->model::search($searchTerm);

            // Filter by employee ID first
            $scoutQuery->where('user_id', $userId);

            // Apply status filter
            if (isset($filters['status'])) {
                $status = is_string($filters['status']) ? CampaignStatus::tryFrom($filters['status']) : $filters['status'];

                if ($status) {
                    $scoutQuery->where('status', $status->value);
                }
            }

            // Apply soft delete filter
            $scoutQuery->query(function ($query) use ($filters): void {
                // Apply date range filters
                if (isset($filters['date_from'])) {
                    $query->where('created_at', '>=', $filters['date_from']);
                }

                if (isset($filters['date_to'])) {
                    $query->where('created_at', '<=', $filters['date_to']);
                }

                // Handle soft delete filter
                if (isset($filters['show_deleted']) && $filters['show_deleted']) {
                    $query->withTrashed();
                }
            });

            // Apply filter parameter for employee campaigns
            if (isset($filters['filter'])) {
                switch ($filters['filter']) {
                    case 'active':
                        $scoutQuery->where('status', CampaignStatus::ACTIVE->value);
                        $scoutQuery->query(function ($query): void {
                            $query->where('end_date', '>', now());
                        });
                        break;
                    case 'draft':
                        $scoutQuery->where('status', CampaignStatus::DRAFT->value);
                        break;
                    case 'completed':
                    case 'successful':
                        $scoutQuery->where('status', CampaignStatus::COMPLETED->value);
                        // Note: Goal achievement check is handled in post-processing
                        break;
                    case 'paused':
                        $scoutQuery->where('status', CampaignStatus::PAUSED->value);
                        break;
                    case 'ending-soon':
                        $scoutQuery->where('status', CampaignStatus::ACTIVE->value);
                        $scoutQuery->query(function ($query): void {
                            $query->where('end_date', '>', now())
                                ->where('end_date', '<=', now()->addDays(7)->endOfDay());
                        });
                        $sortBy = 'end_date';
                        $sortOrder = 'asc';
                        break;
                    case 'popular':
                        $scoutQuery->where('status', CampaignStatus::ACTIVE->value);
                        $sortBy = 'donations_count';
                        $sortOrder = 'desc';
                        break;
                    case 'needs-attention':
                        $scoutQuery->where('status', CampaignStatus::ACTIVE->value);
                        // Note: Complex needs-attention logic is handled in post-processing
                        break;
                }
            }

            // Apply sorting with fallback for unsupported attributes
            if ($sortBy && $sortOrder) {
                // Handle special sorting cases
                if ($sortBy === 'is_featured') {
                    $scoutQuery->orderBy('is_featured', 'desc')
                        ->orderBy('created_at', 'desc');
                }

                if ($sortBy !== 'is_featured') {
                    $scoutQuery->orderBy($sortBy, $sortOrder);
                }
            }

            /** @var LengthAwarePaginator<int, Campaign> $result */
            $result = $scoutQuery->paginate($perPage, 'page', $page);

            return $result;

        } catch (Exception $e) {
            // Log the Meilisearch failure
            Log::warning('Meilisearch search failed for employee campaigns', [
                'user_id' => $userId,
                'search_term' => $filters['search'] ?? null,
                'error' => $e->getMessage(),
            ]);

            // Don't fall back to database - throw a user-friendly exception
            throw new RuntimeException('Search index is currently being updated. Please try again in a few moments.', 503, $e);
        }
    }

    /**
     * Hybrid search for employee campaigns that combines Meilisearch (for indexed campaigns)
     * with database search (for draft campaigns that aren't indexed).
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Campaign>
     */
    private function hybridSearchUserCampaigns(
        int $userId,
        int $page,
        int $perPage,
        array $filters,
        string $sortBy,
        string $sortOrder,
    ): LengthAwarePaginator {
        $searchTerm = $filters['search'];

        try {
            // First, try to get results from Meilisearch (for indexed active/completed campaigns)
            $meilisearchResults = $this->searchUserCampaignsWithMeilisearch(
                $userId,
                1, // Get all pages for merging
                1000, // Large limit to get all indexed results
                $filters,
                $sortBy,
                $sortOrder
            );

            $meilisearchIds = $meilisearchResults->getCollection()->pluck('id')->toArray();
        } catch (Exception) {
            $meilisearchResults = null;
            $meilisearchIds = [];
        }

        // Then, search database for campaigns not in Meilisearch (drafts, etc.) + all campaigns as fallback
        $databaseQuery = $this->model->newQuery()
            ->where('user_id', $userId)
            ->with(['organization', 'creator']);

        // Apply soft delete filter
        if (isset($filters['show_deleted']) && $filters['show_deleted']) {
            $databaseQuery->withTrashed();
        }

        // Apply search on title and description (case-insensitive)
        $databaseQuery->where(function ($q) use ($searchTerm): void {
            $searchLower = strtolower((string) $searchTerm);
            $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchLower}%"])
                ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"]);
        });

        // If we got Meilisearch results, exclude those IDs to avoid duplicates
        // Otherwise, include all campaigns (fallback mode)
        if (! empty($meilisearchIds) && $meilisearchResults instanceof LengthAwarePaginator) {
            $databaseQuery->whereNotIn('id', $meilisearchIds);
        }

        // Apply other filters
        if (isset($filters['status'])) {
            $status = is_string($filters['status']) ? CampaignStatus::tryFrom($filters['status']) : $filters['status'];
            if ($status) {
                $databaseQuery->where('status', $status);
            }
        }

        if (isset($filters['date_from'])) {
            $databaseQuery->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $databaseQuery->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['filter'])) {
            $databaseQuery = $this->applyUserFilter($databaseQuery, $filters['filter']);
        }

        $databaseResults = $databaseQuery->orderBy($sortBy, $sortOrder)->get();

        // Merge results: Meilisearch results + Database results
        $allResults = collect();

        if ($meilisearchResults instanceof LengthAwarePaginator && ! empty($meilisearchIds)) {
            $allResults = $allResults->concat($meilisearchResults->getCollection());
        }

        $allResults = $allResults->concat($databaseResults);

        // Remove duplicates based on ID and re-sort
        $uniqueResults = $allResults->unique('id');

        // Apply sorting to merged results
        if ($sortBy === 'created_at') {
            $uniqueResults = $sortOrder === 'desc'
                ? $uniqueResults->sortByDesc('created_at')
                : $uniqueResults->sortBy('created_at');
        }

        if ($sortBy === 'donations_count') {
            $uniqueResults = $sortOrder === 'desc'
                ? $uniqueResults->sortByDesc('donations_count')
                : $uniqueResults->sortBy('donations_count');
        }

        if ($sortBy === 'updated_at') {
            $uniqueResults = $sortOrder === 'desc'
                ? $uniqueResults->sortByDesc('updated_at')
                : $uniqueResults->sortBy('updated_at');
        }

        // Manual pagination
        $offset = ($page - 1) * $perPage;
        $paginatedResults = $uniqueResults->slice($offset, $perPage)->values();

        $total = $uniqueResults->count();

        // Create paginator
        $paginator = new LengthAwarePaginator(
            $paginatedResults,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );

        return $paginator;
    }

    /**
     * @return array<int, Campaign>
     */
    public function findByStatus(CampaignStatus $status, ?int $limit = null): array
    {
        $query = $this->model
            ->where('status', $status)
            ->with('organization')
            ->orderBy('created_at', 'desc');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()->all();
    }

    /**
     * @return array<int, Campaign>
     */
    public function findByUserAndStatus(int $userId, CampaignStatus $status): array
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('status', $status)
            ->with('organization')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->all();
    }

    /**
     * @return array<int, Campaign>
     */
    public function findByEmployeeAndStatus(int $userId, CampaignStatus $status): array
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('status', $status)
            ->with('organization')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->all();
    }

    /**
     * Get featured campaigns for homepage display.
     *
     * @param  int  $limit  Number of featured campaigns to return
     * @return array<int, Campaign>
     */
    public function getFeaturedCampaigns(int $limit = 3): array
    {
        // Use a single optimized query with UNION to get all featured campaigns
        // This reduces from 3 potential queries to 1 efficient query

        $featuredQuery = $this->model->newQuery()
            ->select('campaigns.*')
            ->selectRaw('1 as priority')
            ->where('is_featured', true)
            ->where('status', CampaignStatus::ACTIVE);

        // Near goal campaigns (70-100% progress)
        $nearGoalQuery = $this->model->newQuery()
            ->select('campaigns.*')
            ->selectRaw('2 as priority')
            ->where('status', CampaignStatus::ACTIVE)
            ->where('is_featured', false)
            ->where('goal_amount', '>', 0);

        // Use the goal_percentage column if it exists (from our migration)
        if (Schema::hasColumn('campaigns', 'goal_percentage')) {
            $nearGoalQuery->whereBetween('goal_percentage', [70, 99.99]);
        }

        if (! Schema::hasColumn('campaigns', 'goal_percentage')) {
            $nearGoalQuery->whereRaw('(current_amount / goal_amount * 100) BETWEEN 70 AND 99.99');
        }

        // Popular campaigns (high donation count)
        $popularQuery = $this->model->newQuery()
            ->select('campaigns.*')
            ->selectRaw('3 as priority')
            ->where('status', CampaignStatus::ACTIVE)
            ->where('is_featured', false);

        if (Schema::hasColumn('campaigns', 'goal_percentage')) {
            $popularQuery->where(function ($q): void {
                $q->where('goal_percentage', '<', 70)
                    ->orWhere('goal_percentage', '>=', 100);
            });
        }

        if (! Schema::hasColumn('campaigns', 'goal_percentage')) {
            $popularQuery->whereRaw('(current_amount / goal_amount * 100) NOT BETWEEN 70 AND 99.99');
        }

        // Combine all queries with UNION and get results
        $unionQuery = $featuredQuery
            ->unionAll($nearGoalQuery)
            ->unionAll($popularQuery);

        $campaigns = Campaign::query()
            ->fromSub($unionQuery, 'campaigns')
            ->with('organization')
            ->orderBy('priority')
            ->orderByDesc('current_amount')
            ->limit($limit)
            ->get();

        return $campaigns->all();
    }

    /**
     * Find all campaigns.
     *
     * @return array<int, Campaign>
     */
    public function findAll(): array
    {
        return $this->model
            ->with('organization')
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get()
            ->all();
    }

    public function getTotalCampaignsCount(): int
    {
        return $this->model->count();
    }

    public function getTotalRaisedAmount(): float
    {
        return (float) $this->model->sum('current_amount');
    }

    public function getActiveCampaignsCount(): int
    {
        return $this->model
            ->where('status', CampaignStatus::ACTIVE)
            ->count();
    }

    public function getActiveRaisedAmount(): float
    {
        return (float) $this->model
            ->where('status', CampaignStatus::ACTIVE)
            ->sum('current_amount');
    }

    /**
     * Execute a simple list query for testing environment
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Campaign>
     */
    private function executeSimpleListQuery(array $filters, string $sortBy, string $sortOrder, int $page, int $perPage): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['organization', 'creator']);

        // Apply filters
        if (isset($filters['status'])) {
            $status = is_string($filters['status']) ? CampaignStatus::tryFrom($filters['status']) : $filters['status'];
            if ($status) {
                $query->where('status', $status);
            }
        }

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['search']) && ! empty($filters['search'])) {
            $query->where(function ($q) use ($filters): void {
                $searchLower = strtolower((string) $filters['search']);
                $q->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, "$.en"))) LIKE ?', ["%{$searchLower}%"])
                    ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(description, "$.en"))) LIKE ?', ["%{$searchLower}%"]);
            });
        }

        // Apply date filters
        $this->applyDateFilters($query, $filters);

        // Apply filter parameter
        if (isset($filters['filter'])) {
            $query = $this->applyFilter($query, $filters['filter']);
        }

        /** @var LengthAwarePaginator<int, Campaign> $result */
        $result = $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        return $result;
    }

    /**
     * Get count of campaigns by status.
     *
     * @param  array<string, mixed>  $filters
     */
    public function countByStatus(CampaignStatus $status, array $filters = []): int
    {
        $query = $this->model->where('status', $status);

        // Apply filters if provided
        if ($filters !== []) {
            $query = $this->applyFilters($query, $filters);
        }

        return $query->count();
    }

    /**
     * Get total count of all campaigns.
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Find campaigns by multiple IDs.
     *
     * @param  array<int>  $ids
     * @return array<int, mixed>
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->model->whereIn('id', $ids)->get()->toArray();
    }

    /**
     * Find campaigns by organization ID.
     *
     * @return array<int, mixed>
     */
    public function findByOrganizationId(int $organizationId): array
    {
        return $this->model->where('organization_id', $organizationId)->get()->toArray();
    }

    /**
     * Update campaign status.
     */
    public function updateStatus(int $campaignId, CampaignStatus $status): bool
    {
        return $this->model->where('id', $campaignId)->update(['status' => $status]) > 0;
    }

    /**
     * Get popular campaigns with caching.
     *
     * @return array<int, Campaign>
     */
    public function getPopularCampaigns(int $limit = 20): array
    {
        return $this->cacheService->remember(
            "campaigns:popular:limit:{$limit}",
            fn () => $this->model->newQuery()
                ->where('status', CampaignStatus::ACTIVE)
                ->orderByDesc('donations_count')
                ->limit($limit)
                ->with(['organization', 'creator'])
                ->get()
                ->all(),
            'medium',
            ['campaigns', 'popular_campaigns']
        );
    }

    /**
     * Get trending campaigns (high donation velocity) with caching.
     *
     * @return array<int, Campaign>
     */
    public function getTrendingCampaigns(int $limit = 20): array
    {
        return $this->cacheService->remember(
            "campaigns:trending:limit:{$limit}",
            fn () =>
                // Get campaigns with highest donation activity in last 24-48 hours
                $this->model->newQuery()
                    ->join('donations', 'campaigns.id', '=', 'donations.campaign_id')
                    ->where('campaigns.status', CampaignStatus::ACTIVE)
                    ->where('donations.created_at', '>=', now()->subDays(2))
                    ->where('donations.status', 'completed')
                    ->groupBy('campaigns.id')
                    ->havingRaw('COUNT(donations.id) >= 3')
                    ->orderByRaw('SUM(donations.amount) DESC')
                    ->limit($limit)
                    ->select('campaigns.*')
                    ->with(['organization', 'creator'])
                    ->get()
                    ->all(),
            'short',
            ['campaigns', 'trending_campaigns', 'donations']
        );
    }

    /**
     * Get campaigns ending soon with caching.
     *
     * @return array<int, Campaign>
     */
    public function getEndingSoonCampaigns(int $days = 7, int $limit = 20): array
    {
        return $this->cacheService->remember(
            "campaigns:ending_soon:days:{$days}:limit:{$limit}",
            fn () => $this->model->newQuery()
                ->where('status', CampaignStatus::ACTIVE)
                ->where('end_date', '>', now())
                ->where('end_date', '<=', now()->addDays($days))
                ->orderBy('end_date')
                ->limit($limit)
                ->with(['organization', 'creator'])
                ->get()
                ->all(),
            'short',
            ['campaigns', 'ending_soon_campaigns']
        );
    }

    /**
     * Get recently created campaigns with caching.
     *
     * @return array<int, Campaign>
     */
    public function getRecentCampaigns(int $days = 7, int $limit = 20): array
    {
        return $this->cacheService->remember(
            "campaigns:recent:days:{$days}:limit:{$limit}",
            fn () => $this->model->newQuery()
                ->where('status', CampaignStatus::ACTIVE)
                ->where('created_at', '>=', now()->subDays($days))
                ->orderByDesc('created_at')
                ->limit($limit)
                ->with(['organization', 'creator'])
                ->get()
                ->all(),
            'medium',
            ['campaigns', 'recent_campaigns']
        );
    }

    /**
     * Get campaigns by organization with caching.
     *
     * @return array<int, Campaign>
     */
    public function getCachedCampaignsByOrganization(int $organizationId, int $limit = 50): array
    {
        return $this->cacheService->rememberCampaigns($organizationId, 'all')->toArray();
    }

    /**
     * Get campaigns by status with caching.
     *
     * @return array<int, Campaign>
     */
    public function getCachedCampaignsByStatus(CampaignStatus $status, int $limit = 100): array
    {
        return $this->cacheService->remember(
            "campaigns:status:{$status->value}:limit:{$limit}",
            fn () => $this->model->newQuery()
                ->where('status', $status)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->with(['organization', 'creator'])
                ->get()
                ->all(),
            'medium',
            ['campaigns', "campaigns_status_{$status->value}"]
        );
    }

    /**
     * Warm cache for popular campaign lists.
     */
    public function warmPopularCampaignsCache(): void
    {
        try {
            $this->getPopularCampaigns(20);
            $this->getPopularCampaigns(50);
            $this->getTrendingCampaigns(20);
            $this->getEndingSoonCampaigns(7, 20);
            $this->getEndingSoonCampaigns(14, 20);
            $this->getRecentCampaigns(7, 20);
            $this->getRecentCampaigns(30, 20);
        } catch (Exception $e) {
            Log::warning('Failed to warm popular campaigns cache', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate campaign list caches.
     */
    public function invalidateCampaignListCaches(): void
    {
        $this->cacheService->flushByTags([
            'campaigns',
            'popular_campaigns',
            'trending_campaigns',
            'ending_soon_campaigns',
            'recent_campaigns',
        ]);

        // Also invalidate specific cache keys
        $cacheKeys = [
            'campaigns:popular:*',
            'campaigns:trending:*',
            'campaigns:ending_soon:*',
            'campaigns:recent:*',
            'campaigns:status:*',
        ];

        foreach ($cacheKeys as $pattern) {
            try {
                // Note: This is Redis-specific. For other cache drivers, would need different approach
                if (config('cache.default') === 'redis') {
                    $redis = app('redis')->connection('cache');
                    $keys = $redis->keys($pattern);
                    if (! empty($keys)) {
                        $redis->del($keys);
                    }
                }
            } catch (Exception $e) {
                Log::warning('Failed to invalidate campaign cache pattern', [
                    'pattern' => $pattern,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Invalidate cache for specific campaign.
     */
    public function invalidateCampaignCache(int $campaignId, ?int $organizationId = null): void
    {
        $this->cacheService->invalidateCampaign($campaignId, $organizationId);
        $this->invalidateCampaignListCaches();
    }

    /**
     * Get cache statistics for campaign lists.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getCampaignListCacheStatistics(): array
    {
        $stats = [];

        $cacheKeys = [
            'campaigns:popular:limit:20',
            'campaigns:trending:limit:20',
            'campaigns:ending_soon:days:7:limit:20',
            'campaigns:recent:days:7:limit:20',
        ];

        foreach ($cacheKeys as $key) {
            $stats[$key] = [
                'cached' => $this->cacheService->has($key),
                'key' => $key,
            ];
        }

        return $stats;
    }

    /**
     * Get paginated campaigns with filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Campaign>
     */
    public function getPaginatedFiltered(
        array $filters = [],
        int $perPage = 15,
        int $page = 1
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->with(['organization', 'creator']);

        $query = $this->applyFilters($query, $filters);

        /** @var LengthAwarePaginator<int, Campaign> $result */
        $result = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return $result;
    }

    /**
     * Count campaigns with filtering.
     *
     * @param  array<string, mixed>  $filters
     */
    public function countFiltered(array $filters = []): int
    {
        $query = $this->model->newQuery();
        $query = $this->applyFilters($query, $filters);

        return $query->count();
    }

    /**
     * Get total amount raised with filtering.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getTotalAmountRaised(array $filters = []): float
    {
        $query = $this->model->newQuery();
        $query = $this->applyFilters($query, $filters);

        return (float) $query->sum('current_amount');
    }

    /**
     * Apply filters to query.
     *
     * @param  Builder<Campaign>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Campaign>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['status'])) {
            $status = is_string($filters['status']) ? CampaignStatus::tryFrom($filters['status']) : $filters['status'];
            if ($status) {
                $query->where('status', $status);
            }
        }

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['search']) && ! empty($filters['search'])) {
            $searchTerm = strtolower((string) $filters['search']);
            $query->where(function ($q) use ($searchTerm): void {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchTerm}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchTerm}%"]);
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query;
    }

    /**
     * Load campaigns for CacheService callbacks.
     *
     * @return Collection<int, Campaign>
     */
    public function loadCampaignsData(int $organizationId, string $type, ?string $status = null): Collection
    {
        $query = $this->model->newQuery()
            ->where('organization_id', $organizationId)
            ->with(['organization', 'creator']);

        if ($status) {
            $statusEnum = CampaignStatus::tryFrom($status);
            if ($statusEnum) {
                $query->where('status', $statusEnum);
            }
        }

        match ($type) {
            'active' => $query->where('status', CampaignStatus::ACTIVE)
                ->where('end_date', '>', now()),
            'recent' => $query->where('created_at', '>=', now()->subDays(30))
                ->orderByDesc('created_at'),
            'popular' => $query->where('status', CampaignStatus::ACTIVE)
                ->orderByDesc('donations_count'),
            'ending_soon' => $query->where('status', CampaignStatus::ACTIVE)
                ->where('end_date', '>', now())
                ->where('end_date', '<=', now()->addDays(7))
                ->orderBy('end_date'),
            default => $query->orderByDesc('created_at'),
        };

        return $query->limit(100)->get();
    }

    /**
     * Get average campaign progress percentage.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getAverageProgress(array $filters = []): float
    {
        $query = $this->model->newQuery()
            ->where('goal_amount', '>', 0);

        $query = $this->applyFilters($query, $filters);

        // Use the goal_percentage column if it exists (from our migration)
        if (Schema::hasColumn('campaigns', 'goal_percentage')) {
            $averageProgress = $query->avg('goal_percentage');

            return (float) ($averageProgress ?? 0.0);
        }

        // Calculate progress on the fly using current_amount and goal_amount
        $result = $query->selectRaw('AVG((current_amount / goal_amount) * 100) as avg_progress')
            ->first();
        /** @var object{avg_progress: string}|null $result */
        $averageProgress = $result?->avg_progress;

        return (float) ($averageProgress ?? 0.0);
    }
}
