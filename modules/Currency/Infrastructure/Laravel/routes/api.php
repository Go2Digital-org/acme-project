<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Currency\Infrastructure\Laravel\Controllers\Api\CurrencyController;

Route::prefix('api/v1')->middleware(['api'])->group(function (): void {
    // Currency endpoints
    Route::prefix('currencies')->group(function (): void {
        Route::get('/', [CurrencyController::class, 'index'])->name('api.currencies.index');
        Route::get('/current', [CurrencyController::class, 'show'])->name('api.currencies.current');
        Route::post('/preference', [CurrencyController::class, 'store'])->name('api.currencies.preference');
        Route::post('/format', [CurrencyController::class, 'format'])->name('api.currencies.format');
    });

    // User currency preference (authenticated)
    Route::middleware(['auth:sanctum'])->group(function (): void {
        Route::post('/user/currency', [CurrencyController::class, 'store'])->name('api.user.currency');
    });
});
