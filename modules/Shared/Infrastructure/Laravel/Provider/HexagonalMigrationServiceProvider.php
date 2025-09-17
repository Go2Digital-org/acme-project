<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Provider;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * Auto-discovers and loads migrations from all hexagonal modules.
 *
 * This provider ensures that migrations stored in module directories
 * following hexagonal architecture are properly loaded by Laravel.
 */
class HexagonalMigrationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadModuleMigrations();
    }

    /**
     * Discover and load migrations from all hexagonal modules.
     */
    private function loadModuleMigrations(): void
    {
        $modulesPath = base_path('modules');

        if (! File::exists($modulesPath)) {
            return;
        }

        foreach (File::directories($modulesPath) as $modulePath) {
            $migrationPath = "{$modulePath}/Infrastructure/Laravel/Migration";

            if (File::exists($migrationPath) && count(File::files($migrationPath)) > 0) {
                $this->loadMigrationsFrom($migrationPath);
            }
        }
    }
}
