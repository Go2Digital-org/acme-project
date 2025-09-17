<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Provider;

use Exception;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Organization\Infrastructure\Laravel\Middleware\InitializeTenancyForNonCentralDomains;

class LivewireTenancyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Configure Livewire to use tenancy middleware for AJAX updates
        // Based on stancl/tenancy documentation for Livewire 3

        // Check if Livewire is available and bound to prevent race conditions in parallel testing
        if (class_exists(Livewire::class) && $this->app->bound('livewire')) {
            try {
                Livewire::setUpdateRoute(fn ($handle) => Route::post('/livewire/update', $handle)
                    ->middleware([
                        'web',
                        InitializeTenancyForNonCentralDomains::class,
                    ])
                    ->name('livewire.update'));
            } catch (Exception $e) {
                // Silently ignore in test environment if Livewire is not yet available
                // This handles race conditions in parallel testing
                if (! app()->environment('testing')) {
                    throw $e;
                }
            }
        }
    }
}
