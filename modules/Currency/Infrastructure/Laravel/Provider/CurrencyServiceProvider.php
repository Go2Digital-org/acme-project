<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Laravel\Provider;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Currency\Application\Command\SetUserCurrencyCommandHandler;
use Modules\Currency\Application\Query\GetAvailableCurrenciesQueryHandler;
use Modules\Currency\Application\Query\GetCurrenciesForViewQueryHandler;
use Modules\Currency\Application\Query\GetCurrencyByCodeQueryHandler;
use Modules\Currency\Application\Query\GetUserCurrencyQueryHandler;
use Modules\Currency\Application\Service\CurrencyPreferenceService;
use Modules\Currency\Application\Service\CurrencyViewService;
use Modules\Currency\Domain\Model\Currency;
use Modules\Currency\Domain\Repository\CurrencyPreferenceRepositoryInterface;
use Modules\Currency\Domain\Repository\CurrencyQueryRepositoryInterface;
use Modules\Currency\Domain\Repository\CurrencyRepositoryInterface;
use Modules\Currency\Domain\Service\CurrencyCacheInterface;
use Modules\Currency\Infrastructure\Cache\RequestLevelCurrencyCache;
use Modules\Currency\Infrastructure\Laravel\Console\Commands\WarmCurrencyCacheCommand;
use Modules\Currency\Infrastructure\Laravel\Middleware\SetCurrency;
use Modules\Currency\Infrastructure\Laravel\Repository\EloquentCurrencyPreferenceRepository;
use Modules\Currency\Infrastructure\Laravel\Repository\EloquentCurrencyRepository;
use Modules\Currency\Infrastructure\Laravel\View\Components\CurrencySelector;
use Modules\Currency\Infrastructure\Laravel\ViewComposer\CurrencyDropdownComposer;
use Modules\Currency\Infrastructure\Repository\CurrencyQueryRepository;

class CurrencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register repository bindings
        $this->app->bind(
            CurrencyPreferenceRepositoryInterface::class,
            EloquentCurrencyPreferenceRepository::class,
        );

        $this->app->bind(
            CurrencyRepositoryInterface::class,
            EloquentCurrencyRepository::class,
        );

        // Register CQRS query repository with cache-first strategy
        $this->app->singleton(
            CurrencyQueryRepositoryInterface::class,
            CurrencyQueryRepository::class
        );

        // Register cache as singleton (one instance per request)
        $this->app->singleton(
            CurrencyCacheInterface::class,
            RequestLevelCurrencyCache::class
        );

        // Register view service as singleton to share cache
        $this->app->singleton(CurrencyViewService::class);

        // Register service as singleton
        $this->app->singleton(CurrencyPreferenceService::class);

        // Register command handlers
        $this->app->bind(SetUserCurrencyCommandHandler::class);

        // Register query handlers
        $this->app->bind(GetUserCurrencyQueryHandler::class);
        $this->app->bind(GetAvailableCurrenciesQueryHandler::class);
        $this->app->bind(GetCurrenciesForViewQueryHandler::class);
        $this->app->bind(GetCurrencyByCodeQueryHandler::class);

        // Register config
        $this->mergeConfigFrom(
            __DIR__ . '/../../../../../config/currency.php',
            'currency',
        );
    }

    public function boot(): void
    {
        // Register Blade component
        Blade::component('currency-selector', CurrencySelector::class);

        // Register view composer for currency dropdown
        View::composer(
            'components.currency-dropdown-item',
            CurrencyDropdownComposer::class
        );

        // Register middleware
        /** @var Router $router */
        $router = $this->app->make('router');
        $router->aliasMiddleware('currency', SetCurrency::class);

        // Register views
        $this->loadViewsFrom(__DIR__ . '/../../../../../resources/views', 'currency');

        // Register migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Migration');

        // Apply middleware to web routes
        /** @var Router $router */
        $router = $this->app->make('router');
        $router->pushMiddlewareToGroup('web', SetCurrency::class);

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                WarmCurrencyCacheCommand::class,
            ]);

            // Publish config
            $this->publishes([
                __DIR__ . '/../../../../../config/currency.php' => config_path('currency.php'),
            ], 'currency-config');
        }

        // Register helper function
        if (! function_exists('current_currency')) {
            require_once __DIR__ . '/../Helper/CurrencyHelper.php';
        }
    }
}
