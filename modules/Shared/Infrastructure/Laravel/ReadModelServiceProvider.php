<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Campaign\Infrastructure\Laravel\Repository\CampaignAnalyticsRepository;
use Modules\Donation\Infrastructure\Laravel\Repository\DonationReportRepository;
use Modules\Organization\Infrastructure\Laravel\Repository\OrganizationDashboardRepository;
use Modules\Shared\Application\ReadModel\CacheInvalidationEventListener;
use Modules\Shared\Application\ReadModel\ReadModelCacheInvalidator;
use Modules\Shared\Application\Service\CacheService;
use Modules\Shared\Infrastructure\Laravel\Console\RefreshReadModelsCommand;

/**
 * Service provider for read model infrastructure.
 */
class ReadModelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register cache invalidator
        $this->app->singleton(fn ($app): ReadModelCacheInvalidator => new ReadModelCacheInvalidator($app->make(CacheRepository::class)));

        // Register read model repositories
        $this->app->singleton(fn ($app): CampaignAnalyticsRepository => new CampaignAnalyticsRepository($app->make(CacheRepository::class), $app->make(CacheService::class)));

        $this->app->singleton(fn ($app): DonationReportRepository => new DonationReportRepository($app->make(CacheRepository::class)));

        $this->app->singleton(fn ($app): OrganizationDashboardRepository => new OrganizationDashboardRepository($app->make(CacheRepository::class), $app->make(CacheService::class)));

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                RefreshReadModelsCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        // Register event listeners for cache invalidation
        $listener = $this->app->make(CacheInvalidationEventListener::class);

        foreach ($listener->subscribe(Event::getFacadeRoot()) as $event => $method) {
            Event::listen($event, [$listener, $method]);
        }
    }
}
