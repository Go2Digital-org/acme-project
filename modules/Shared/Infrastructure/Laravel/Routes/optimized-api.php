<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Infrastructure\Laravel\Controllers\UserProfileController;
use Modules\Campaign\Infrastructure\Laravel\Controllers\ListCampaignsController;
use Modules\Organization\Infrastructure\Laravel\Controllers\Api\OrganizationDashboardController;
use Modules\Shared\Infrastructure\Laravel\Controllers\Api\SearchController;

/**
 * Optimized API Routes with Performance Enhancements
 *
 * Features:
 * - Rate limiting per endpoint type
 * - Response caching headers
 * - Field selection support
 * - Relationship lazy loading
 * - Pagination optimization
 */
Route::middleware(['api', 'auth:sanctum', 'api.optimization'])->prefix('api/v1')->group(function (): void {

    // Campaign endpoints with optimized queries
    Route::middleware(['throttle:campaigns'])->group(function (): void {
        /**
         * GET /api/v1/campaigns
         *
         * Query Parameters:
         * - page: Page number (default: 1)
         * - per_page: Items per page (max: 100, default: 20)
         * - sort_by: Sort field (created_at, updated_at, current_amount, end_date, donations_count, is_featured)
         * - sort_order: Sort direction (asc, desc)
         * - status: Filter by status
         * - search: Search term
         * - filter: Predefined filters (active-only, ending-soon, recent, popular, completed, favorites)
         * - fields[]: Selective field loading (financial, timing, creator, organization, metadata, actions)
         * - include: Additional relationships (none for list view)
         *
         * Response Headers:
         * - Cache-Control: public, max-age=300 (varies by filter type)
         * - X-Total-Count: Total number of campaigns
         * - X-Page-Count: Total number of pages
         * - ETag: Response fingerprint for caching
         */
        Route::get('campaigns', ListCampaignsController::class)
            ->name('api.campaigns.index');
    });

    // Search endpoints with intelligent caching
    Route::middleware(['throttle:search'])->group(function (): void {
        /**
         * GET /api/v1/search
         *
         * Query Parameters:
         * - q: Search query (required, max 500 chars)
         * - types[]: Entity types to search (campaign, donation, user, organization)
         * - sort: Sort method (relevance, created_at, updated_at, popularity, name)
         * - limit: Results per page (max: 100, default: 20)
         * - page: Page number
         * - locale: Search locale
         * - highlight: Enable result highlighting
         * - facets: Include facet data
         *
         * Response Headers:
         * - Cache-Control: public, max-age=300 (varies by query type)
         * - X-Search-Time: Query execution time
         * - X-Total-Results: Total matching results
         * - X-Search-Engine: Search engine used (meilisearch/database)
         * - ETag: Search result fingerprint
         */
        Route::get('search', [SearchController::class, 'search'])
            ->name('api.optimized.search');

        /**
         * GET /api/v1/search/facets
         *
         * Get available search facets with 30-minute caching
         */
        Route::get('search/facets', [SearchController::class, 'facets'])
            ->name('api.optimized.search.facets');
    });

    // User profile endpoint with field selection
    Route::middleware(['throttle:profile'])->group(function (): void {
        /**
         * GET /api/v1/profile
         *
         * Query Parameters:
         * - fields[]: Selective field loading (profile, account, organization, roles)
         * - include: Additional data (donation_stats, preferences)
         *
         * Response Headers:
         * - Cache-Control: private, max-age=600 (user-specific data)
         * - X-User-ID: Current user identifier
         * - X-Profile-Version: Profile data version hash
         */
        Route::get('profile', UserProfileController::class)
            ->name('api.profile');
    });

    // Organization dashboard with read model optimization
    Route::middleware(['throttle:dashboard'])->group(function (): void {
        /**
         * GET /api/v1/organizations/{id}/dashboard
         *
         * Query Parameters:
         * - fields[]: Data sections (organization, employees, campaigns, financial, donations, performance, engagement, top_performers, recent_activity)
         * - include: Heavy data (trends)
         * - refresh: Force cache refresh (admin only)
         *
         * Response Headers:
         * - Cache-Control: private, max-age=300 (organization-specific)
         * - X-Organization-ID: Organization identifier
         * - X-Data-Source: Data source type (read_model)
         * - Last-Modified: Data last updated timestamp
         * - ETag: Dashboard data fingerprint
         */
        Route::get('organizations/{id}/dashboard', OrganizationDashboardController::class)
            ->name('api.organization.dashboard')
            ->where('id', '[0-9]+');

        // Admin endpoints for cache management
        Route::middleware(['can:manage-cache'])->group(function (): void {
            Route::get('organizations/{id}/dashboard/cache-stats', [OrganizationDashboardController::class, 'cacheStats'])
                ->name('api.organization.dashboard.cache-stats');

            Route::post('organizations/{id}/dashboard/warm-cache', [OrganizationDashboardController::class, 'warmCache'])
                ->name('api.organization.dashboard.warm-cache');

            Route::delete('organizations/{id}/dashboard/cache', [OrganizationDashboardController::class, 'invalidateCache'])
                ->name('api.organization.dashboard.invalidate-cache');
        });
    });
});

/**
 * Rate Limiting Configuration
 *
 * Add to config/rate_limiting.php or directly in RouteServiceProvider:
 */

/*
RateLimiter::for('campaigns', function (Request $request) {
    return Limit::perMinute(100)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('search', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('profile', function (Request $request) {
    return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('dashboard', function (Request $request) {
    return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
});
*/
