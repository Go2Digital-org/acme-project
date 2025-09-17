<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Provider;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Application\Port\BreadcrumbManagerInterface;
use Modules\Shared\Application\Service\BreadcrumbManager;
use Modules\Shared\Infrastructure\Laravel\Breadcrumb\BreadcrumbAdapter;

/**
 * Service provider for breadcrumb functionality following hexagonal architecture.
 * This provider registers the breadcrumb services and binds the interfaces
 * to their concrete implementations according to dependency inversion principle.
 */
final class BreadcrumbServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     */
    /** @var array<class-string, class-string> */
    public array $bindings = [
        // Bind the port interface to the infrastructure adapter
        BreadcrumbManagerInterface::class => BreadcrumbAdapter::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the adapter as singleton
        $this->app->singleton(BreadcrumbAdapter::class, fn ($app): BreadcrumbAdapter => new BreadcrumbAdapter);

        // Register the application service as singleton
        $this->app->singleton(BreadcrumbManager::class, fn ($app): BreadcrumbManager => new BreadcrumbManager($app->make(BreadcrumbAdapter::class)));

        // Create an alias for easier access
        $this->app->alias(BreadcrumbManager::class, 'breadcrumb.manager');

        // Bind the interface to the application service for dependency injection
        $this->app->bind(BreadcrumbManagerInterface::class, fn ($app) => $app->make(BreadcrumbManager::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register breadcrumb routes/definitions if needed
        $this->registerBreadcrumbDefinitions();

        // Register view composers if needed
        $this->registerViewComposers();
    }

    /**
     * Get the services provided by the provider.
     */
    /** @return array<array-key, mixed> */
    public function provides(): array
    {
        return [
            BreadcrumbManagerInterface::class,
            BreadcrumbManager::class,
            BreadcrumbAdapter::class,
            'breadcrumb.manager',
        ];
    }

    /**
     * Register breadcrumb definitions.
     * This method can be used to define breadcrumb routes that integrate
     * with the diglactic/laravel-breadcrumbs package.
     */
    private function registerBreadcrumbDefinitions(): void
    {
        // The diglactic/laravel-breadcrumbs package automatically loads
        // breadcrumb definitions from routes/breadcrumbs.php
        // We don't need to manually require it to avoid duplicate registration

        // You can also define common breadcrumbs here
        $this->defineCommonBreadcrumbs();
    }

    /**
     * Register view composers for breadcrumb data.
     */
    private function registerViewComposers(): void
    {
        // Register view composer for breadcrumb data
        if ($this->app->bound('view')) {
            $viewFactory = $this->app->make('view');
            $viewFactory->composer(['layouts.app'], function ($view): void {
                $breadcrumbManager = $this->app->make(BreadcrumbManager::class);
                $view->with('breadcrumbData', $breadcrumbManager->toViewData());
            });
        }
    }

    /**
     * Define common breadcrumbs used across the application.
     */
    private function defineCommonBreadcrumbs(): void
    {
        // All breadcrumb definitions are now in routes/breadcrumbs.php
        // This method is kept for backward compatibility or future use
    }
}
