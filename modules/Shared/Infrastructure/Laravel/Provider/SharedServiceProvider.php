<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Provider;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Shared module routes and services.
 *
 * Registers API routes and other shared services following hexagonal architecture.
 */
final class SharedServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Services are already registered by ModulesServiceProvider
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load API routes
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}
