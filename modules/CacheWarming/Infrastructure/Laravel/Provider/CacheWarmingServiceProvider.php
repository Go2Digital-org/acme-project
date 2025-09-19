<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Infrastructure\Laravel\Provider;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Modules\CacheWarming\Application\Command\StartCacheWarmingCommandHandler;
use Modules\CacheWarming\Application\Query\GetCacheStatusQueryHandler;
use Modules\CacheWarming\Application\Service\PageStatsCalculator;
use Modules\CacheWarming\Application\Service\WidgetStatsCalculator;
use Modules\CacheWarming\Domain\Repository\CacheRepositoryInterface;
use Modules\CacheWarming\Domain\Service\CacheWarmingOrchestrator;
use Modules\CacheWarming\Infrastructure\ApiPlatform\Handler\Provider\CacheStatusProvider;
use Modules\CacheWarming\Infrastructure\Laravel\Command\WarmDashboardCacheCommand;
use Modules\CacheWarming\Infrastructure\Laravel\Jobs\WarmCacheJob;
use Modules\CacheWarming\Infrastructure\Laravel\Middleware\CacheWarmingMiddleware;
use Modules\CacheWarming\Infrastructure\Laravel\Repository\RedisCacheRepository;

final class CacheWarmingServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        CacheRepositoryInterface::class => RedisCacheRepository::class,
    ];

    public function register(): void
    {
        // Register repository binding
        $this->app->bind(CacheRepositoryInterface::class, RedisCacheRepository::class);

        // Register domain services
        $this->app->singleton(CacheWarmingOrchestrator::class);

        // Register command handlers
        $this->app->singleton(StartCacheWarmingCommandHandler::class);
        $this->app->singleton(GetCacheStatusQueryHandler::class);

        // Register application services
        $this->app->singleton(WidgetStatsCalculator::class);
        $this->app->singleton(PageStatsCalculator::class);

        // Register middleware
        $this->app->singleton(CacheWarmingMiddleware::class);

        // Register API Platform providers
        $this->app->singleton(CacheStatusProvider::class);

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                WarmDashboardCacheCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        // Register middleware alias
        $router = $this->app->make('router');
        if ($router instanceof Router) {
            $router->aliasMiddleware('cache.warming', CacheWarmingMiddleware::class);
        }

        // Schedule automatic cache warming
        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);

            // Warm critical cache keys every 30 minutes
            $schedule->job(WarmCacheJob::class, 'widgets')
                ->everyThirtyMinutes()
                ->name('cache-warm-widgets')
                ->description('Warm widget cache keys')
                ->withoutOverlapping(5);

            // Warm system cache keys every hour
            $schedule->job(WarmCacheJob::class, 'system')
                ->hourly()
                ->name('cache-warm-system')
                ->description('Warm system cache keys')
                ->withoutOverlapping(10);

            // Full cache warm daily at 2 AM
            $schedule->job(WarmCacheJob::class, 'all')
                ->dailyAt('02:00')
                ->name('cache-warm-full')
                ->description('Full cache warming')
                ->withoutOverlapping(30);
        });

        // Register event listeners if needed
        $this->registerEventListeners();
    }

    private function registerEventListeners(): void
    {
        // Listen for events that should invalidate cache
        $events = $this->app->make('events');
        if (! ($events instanceof Dispatcher)) {
            return;
        }

        // Campaign events
        $events->listen([
            'Modules\Campaign\Domain\Event\CampaignCreated',
            'Modules\Campaign\Domain\Event\CampaignUpdated',
            'Modules\Campaign\Domain\Event\CampaignCompleted',
        ], function (): void {
            $this->invalidateCampaignCache();
        });

        // Donation events
        $events->listen([
            'Modules\Donation\Domain\Event\DonationCreated',
            'Modules\Donation\Domain\Event\DonationProcessed',
        ], function (): void {
            $this->invalidateDonationCache();
        });

        // Organization events
        $events->listen([
            'Modules\Organization\Domain\Event\OrganizationCreated',
            'Modules\Organization\Domain\Event\OrganizationUpdated',
        ], function (): void {
            $this->invalidateOrganizationCache();
        });
    }

    private function invalidateCampaignCache(): void
    {
        $this->dispatchCacheInvalidation([
            'campaign_performance' => 'campaign_performance',
            'campaign_categories' => 'campaign_categories',
            'optimized_campaign_stats' => 'optimized_campaign_stats',
            'goal_completion' => 'goal_completion',
            'realtime_stats' => 'realtime_stats',
        ]);
    }

    private function invalidateDonationCache(): void
    {
        $this->dispatchCacheInvalidation([
            'total_donations' => 'total_donations',
            'average_donation' => 'average_donation',
            'donation_trends' => 'donation_trends',
            'donation_methods' => 'donation_methods',
            'revenue_summary' => 'revenue_summary',
            'realtime_stats' => 'realtime_stats',
        ]);
    }

    private function invalidateOrganizationCache(): void
    {
        $this->dispatchCacheInvalidation([
            'organization_stats' => 'organization_stats',
            'employee_participation' => 'employee_participation',
            'geographical_distribution' => 'geographical_distribution',
            'realtime_stats' => 'realtime_stats',
        ]);
    }

    /**
     * @param  array<string, mixed>  $cacheKeys
     */
    private function dispatchCacheInvalidation(array $cacheKeys): void
    {
        // Dispatch background job to re-warm specific cache keys
        WarmCacheJob::dispatch('specific', $cacheKeys)
            ->onQueue('cache')
            ->delay(60); // Small delay to allow transaction to complete
    }
}
