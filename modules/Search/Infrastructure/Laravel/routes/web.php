<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Search\Infrastructure\Laravel\Controllers\SearchController;

Route::middleware(['web'])->group(function (): void {
    // Web search page
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
});
