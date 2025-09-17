<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Shared\Infrastructure\Laravel\Controllers\Api\SearchController;

/*
|--------------------------------------------------------------------------
| Shared Module API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the Shared module, including unified search
| functionality across all models.
|
*/

Route::prefix('api/v1/search')->middleware(['api', 'auth:sanctum'])->group(function (): void {
    // Global search across all models
    Route::get('/', [SearchController::class, 'global'])->name('api.search.global');

    // Model-specific search endpoints
    Route::get('/organizations', [SearchController::class, 'organizations'])->name('api.search.organizations');
    Route::get('/users', [SearchController::class, 'users'])->name('api.search.users');
    Route::get('/donations', [SearchController::class, 'donations'])->name('api.search.donations');
    Route::get('/categories', [SearchController::class, 'categories'])->name('api.search.categories');
    Route::get('/pages', [SearchController::class, 'pages'])->name('api.search.pages');

    // Autocomplete/suggestions
    Route::get('/suggestions', [SearchController::class, 'suggestions'])->name('api.search.suggestions');

    // Search facets for filtering
    Route::get('/facets', [SearchController::class, 'facets'])->name('api.search.facets');
});

// Public search endpoints (no authentication required)
Route::prefix('api/v1/public/search')->middleware(['api', 'throttle:60,1'])->group(function (): void {
    // Public organization search (only verified organizations)
    Route::get('/organizations', function (Request $request) {
        $request->merge(['verified_only' => true]);

        return app(SearchController::class)->organizations($request);
    })->name('api.public.search.organizations');

    // Public category search (only active categories)
    Route::get('/categories', function (Request $request) {
        $request->merge(['status' => 'active']);

        return app(SearchController::class)->categories($request);
    })->name('api.public.search.categories');

    // Public page search (only published pages)
    Route::get('/pages', function (Request $request) {
        $request->merge(['published_only' => true]);

        return app(SearchController::class)->pages($request);
    })->name('api.public.search.pages');

    // Public suggestions (limited to organizations and categories)
    Route::get('/suggestions', function (Request $request) {
        $allowedModels = ['organizations', 'categories'];
        $model = $request->input('model');

        if (! in_array($model, $allowedModels, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid model. Allowed models: ' . implode(', ', $allowedModels),
            ], 422);
        }

        return app(SearchController::class)->suggestions($request);
    })->name('api.public.search.suggestions');
});
