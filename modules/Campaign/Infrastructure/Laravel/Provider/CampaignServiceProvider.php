<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Provider;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Meilisearch\Client;
use Modules\Campaign\Application\Query\FindCampaignByIdQueryHandler;
use Modules\Campaign\Application\Query\GetUserCampaignStatsQueryHandler;
use Modules\Campaign\Application\Query\ListCampaignsByOrganizationQueryHandler;
use Modules\Campaign\Application\Query\ListUserCampaignsQueryHandler;
use Modules\Campaign\Application\Query\SearchCampaignsQueryHandler;
use Modules\Campaign\Application\ReadModel\CampaignListReadModelBuilder;
use Modules\Campaign\Application\Service\BookmarkService;
use Modules\Campaign\Application\Service\CampaignIndexingService;
use Modules\Campaign\Application\Service\CampaignService;
use Modules\Campaign\Application\Service\CampaignViewDataService;
use Modules\Campaign\Domain\Event\CampaignApprovedEvent;
use Modules\Campaign\Domain\Event\CampaignRejectedEvent;
use Modules\Campaign\Domain\Event\CampaignStatusChangedEvent;
use Modules\Campaign\Domain\Event\CampaignSubmittedForApprovalEvent;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\Service\CampaignIndexerInterface;
use Modules\Campaign\Domain\Service\UserCampaignManagementService;
use Modules\Campaign\Infrastructure\Laravel\Console\Commands\CampaignIndexManagerCommand;
use Modules\Campaign\Infrastructure\Laravel\Listeners\SendCampaignApprovalNotificationListener;
use Modules\Campaign\Infrastructure\Laravel\Listeners\SendCampaignStatusChangeNotificationListener;
use Modules\Campaign\Infrastructure\Laravel\Observer\CampaignCacheObserver;
use Modules\Campaign\Infrastructure\Laravel\Repository\CampaignEloquentRepository;
use Modules\Campaign\Infrastructure\Laravel\Service\MeilisearchCampaignIndexer;
use Modules\Shared\Application\Helper\CurrencyHelper;

class CampaignServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CampaignRepositoryInterface::class,
            CampaignEloquentRepository::class,
        );

        $this->app->bind(
            CampaignIndexerInterface::class,
            MeilisearchCampaignIndexer::class,
        );

        // Register indexing service
        $this->app->singleton(CampaignIndexingService::class, fn ($app) => new CampaignIndexingService(
            $app->make(Client::class),
            $app->make(CampaignRepositoryInterface::class),
            config('scout.prefix', '')
        ));

        // Register campaign-specific services
        $this->app->singleton(CampaignViewDataService::class, fn ($app): CampaignViewDataService => new CampaignViewDataService(
            $app->make(CurrencyHelper::class),
        ));

        $this->app->singleton(BookmarkService::class);
        $this->app->singleton(CampaignService::class);

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CampaignIndexManagerCommand::class,
            ]);
        }

        // API Platform handlers are registered by the central ApiPlatformServiceProvider

        // Register query handlers
        $this->app->bind(SearchCampaignsQueryHandler::class);
        $this->app->bind(FindCampaignByIdQueryHandler::class);
        $this->app->bind(ListCampaignsByOrganizationQueryHandler::class);
        $this->app->bind(ListUserCampaignsQueryHandler::class);
        $this->app->bind(GetUserCampaignStatsQueryHandler::class);

        // Register domain services
        $this->app->singleton(UserCampaignManagementService::class);

        // Register read model builders
        $this->app->singleton(CampaignListReadModelBuilder::class);

    }

    public function boot(): void
    {
        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CampaignIndexManagerCommand::class,
            ]);
        }

        // Register event listeners
        $this->registerEventListeners();

        // Register model observer for cache invalidation
        Campaign::observe(CampaignCacheObserver::class);
    }

    private function registerEventListeners(): void
    {
        // Campaign submission events
        Event::listen(
            CampaignSubmittedForApprovalEvent::class,
            SendCampaignApprovalNotificationListener::class
        );

        // Campaign status change events
        Event::listen(
            CampaignStatusChangedEvent::class,
            [SendCampaignStatusChangeNotificationListener::class, 'handleStatusChanged']
        );

        Event::listen(
            CampaignApprovedEvent::class,
            [SendCampaignStatusChangeNotificationListener::class, 'handleApproved']
        );

        Event::listen(
            CampaignRejectedEvent::class,
            [SendCampaignStatusChangeNotificationListener::class, 'handleRejected']
        );
    }
}
