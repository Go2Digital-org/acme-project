<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Currency\Infrastructure\Laravel\Controllers\Web\SetCurrencyController;

Route::middleware(['web'])->group(function (): void {
    Route::get('/currency/{currency}', SetCurrencyController::class)
        ->name('currency.switch')
        ->where('currency', '[A-Z]{3}');
});
