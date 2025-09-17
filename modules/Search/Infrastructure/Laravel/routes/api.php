<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Search\Infrastructure\Laravel\Controllers\SearchController;
use Modules\Search\Infrastructure\Laravel\Controllers\SearchSuggestionsController;

Route::prefix('api/search')->middleware(['api'])->group(function (): void {
    // Main search endpoint
    Route::post('/', [SearchController::class, 'search'])->name('api.search');

    // Search suggestions for autocomplete
    Route::get('/suggestions', [SearchSuggestionsController::class, 'suggest'])->name('api.search.module.suggestions');

    // Get search facets
    Route::get('/facets', [SearchController::class, 'facets'])->name('api.search.module.facets');

    // Search analytics (admin only)
    Route::middleware(['auth:sanctum', 'can:viewPulse'])->group(function (): void {
        Route::get('/analytics', [SearchController::class, 'analytics'])->name('api.search.analytics');
        Route::post('/reindex', [SearchController::class, 'reindex'])->name('api.search.reindex');
    });
});
