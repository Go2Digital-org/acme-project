<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Provider;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Application\Query\QueryBusInterface;
use Modules\Shared\Application\Service\FormComponentService;
use Modules\Shared\Application\Service\HomepageStatsService;
use Modules\Shared\Application\Service\SearchHighlightService;
use Modules\Shared\Application\Service\SocialSharingService;
use Modules\Shared\Domain\Repository\PageRepositoryInterface;
use Modules\Shared\Domain\Repository\SocialMediaRepositoryInterface;
use Modules\Shared\Infrastructure\Laravel\QueryBus\LaravelQueryBus;
use Modules\Shared\Infrastructure\Laravel\Repository\PageEloquentRepository;
use Modules\Shared\Infrastructure\Laravel\Repository\SocialMediaEloquentRepository;

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
        $this->registerRepositories();
        $this->registerSharedServices();
    }

    private function registerRepositories(): void
    {
        $this->app->bind(PageRepositoryInterface::class, PageEloquentRepository::class);
        $this->app->bind(SocialMediaRepositoryInterface::class, SocialMediaEloquentRepository::class);
    }

    private function registerSharedServices(): void
    {
        // Register QueryBus
        $this->app->bind(QueryBusInterface::class, LaravelQueryBus::class);

        // Register shared view services
        $this->app->singleton(SocialSharingService::class);
        $this->app->singleton(SearchHighlightService::class);
        $this->app->singleton(FormComponentService::class);
        $this->app->singleton(HomepageStatsService::class);
    }
}
