<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Laravel\Provider;

use Illuminate\Support\ServiceProvider;
use Modules\Category\Domain\Repository\CategoryRepositoryInterface;
use Modules\Category\Infrastructure\Laravel\Repository\CategoryEloquentRepository;

class CategoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the repository interface to the implementation
        $this->app->bind(CategoryRepositoryInterface::class, CategoryEloquentRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Any bootstrapping code if needed
    }
}
