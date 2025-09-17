<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\Laravel\Provider;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Meilisearch\Client;
use Modules\Campaign\Application\Event\CampaignCreatedEvent;
use Modules\Campaign\Application\Event\CampaignUpdatedEvent;
use Modules\Donation\Application\Event\DonationCompletedEvent;
use Modules\Organization\Domain\Event\OrganizationCreatedEvent;
use Modules\Organization\Domain\Event\OrganizationUpdatedEvent;
use Modules\Search\Application\Command\IndexEntityCommandHandler;
use Modules\Search\Application\Command\ReindexAllCommandHandler;
use Modules\Search\Application\Event\SearchPerformedEvent;
use Modules\Search\Application\Query\GetSearchSuggestionsQueryHandler;
use Modules\Search\Application\Query\SearchEntitiesQueryHandler;
use Modules\Search\Domain\Repository\SearchAnalyticsRepositoryInterface;
use Modules\Search\Domain\Repository\SearchRepositoryInterface;
use Modules\Search\Domain\Service\IndexManagerInterface;
use Modules\Search\Domain\Service\SearchEngineInterface;
use Modules\Search\Infrastructure\Laravel\Command\IndexAllEntitiesCommand;
use Modules\Search\Infrastructure\Laravel\Command\OptimizeSearchIndexCommand;
use Modules\Search\Infrastructure\Laravel\Command\SearchHealthCheckCommand;
use Modules\Search\Infrastructure\Laravel\Console\SearchIndexCommand;
use Modules\Search\Infrastructure\Laravel\EventListener\IndexCampaignListener;
use Modules\Search\Infrastructure\Laravel\EventListener\IndexDonationListener;
use Modules\Search\Infrastructure\Laravel\EventListener\IndexOrganizationListener;
use Modules\Search\Infrastructure\Laravel\EventListener\TrackSearchAnalyticsListener;
use Modules\Search\Infrastructure\Laravel\Repository\SearchAnalyticsEloquentRepository;
use Modules\Search\Infrastructure\Laravel\Repository\SearchEloquentRepository;
use Modules\Search\Infrastructure\Meilisearch\MeilisearchIndexManager;
use Modules\Search\Infrastructure\Meilisearch\MeilisearchSearchEngine;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Meilisearch client
        $this->app->singleton(Client::class, fn (): Client => new Client(
            config('scout.meilisearch.host') ?? 'http://localhost:7700',
            config('scout.meilisearch.key'),
        ));

        // Register core search services
        $this->app->bind(
            SearchEngineInterface::class,
            MeilisearchSearchEngine::class,
        );

        $this->app->bind(
            IndexManagerInterface::class,
            MeilisearchIndexManager::class,
        );

        // Register repositories
        $this->app->bind(
            SearchRepositoryInterface::class,
            SearchEloquentRepository::class,
        );

        $this->app->bind(
            SearchAnalyticsRepositoryInterface::class,
            SearchAnalyticsEloquentRepository::class,
        );

        // Register query handlers
        $this->app->bind(SearchEntitiesQueryHandler::class);
        $this->app->bind(GetSearchSuggestionsQueryHandler::class);

        // Register command handlers
        $this->app->bind(IndexEntityCommandHandler::class);
        $this->app->bind(ReindexAllCommandHandler::class);

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                IndexAllEntitiesCommand::class,
                OptimizeSearchIndexCommand::class,
                SearchHealthCheckCommand::class,
                SearchIndexCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Migration');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/search.php' => config_path('search.php'),
            ], 'search-config');
        }

        // Register event listeners
        $this->registerEventListeners();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            SearchEngineInterface::class,
            IndexManagerInterface::class,
            SearchRepositoryInterface::class,
            SearchAnalyticsRepositoryInterface::class,
        ];
    }

    /**
     * Register event listeners for search indexing.
     */
    private function registerEventListeners(): void
    {
        /** @var Dispatcher $events */
        $events = $this->app->make('events');

        // Campaign events
        $events->listen(
            CampaignCreatedEvent::class,
            IndexCampaignListener::class,
        );

        $events->listen(
            CampaignUpdatedEvent::class,
            IndexCampaignListener::class,
        );

        // Donation events
        $events->listen(
            DonationCompletedEvent::class,
            IndexDonationListener::class,
        );

        // Organization events
        $events->listen(
            OrganizationCreatedEvent::class,
            IndexOrganizationListener::class,
        );

        $events->listen(
            OrganizationUpdatedEvent::class,
            IndexOrganizationListener::class,
        );

        // Search analytics events
        $events->listen(
            SearchPerformedEvent::class,
            TrackSearchAnalyticsListener::class,
        );
    }
}
