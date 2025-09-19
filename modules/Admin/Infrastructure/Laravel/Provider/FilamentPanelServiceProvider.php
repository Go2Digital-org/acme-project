<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Laravel\Provider;

use Illuminate\Support\ServiceProvider;

/**
 * Filament Panel Service Provider
 *
 * Conditionally registers the appropriate Filament panel provider based on the current domain.
 * This enables multi-tenancy support by serving different admin panels for central vs tenant domains.
 */
class FilamentPanelServiceProvider extends ServiceProvider
{
    /**
     * Register the appropriate panel provider based on domain detection.
     *
     * Uses early domain detection to determine if the current request is coming from
     * a central domain (admin panel) or a tenant domain (tenant-specific panel).
     */
    public function register(): void
    {
        // Register only the AdminPanelProvider which conditionally loads resources
        // Organization module is skipped when in tenant context
        $this->app->register(AdminPanelProvider::class);

    }
}
