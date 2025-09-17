<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Laravel\Provider;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Log;
use Modules\Currency\Application\Command\UpdateExchangeRatesCommandHandler;
use Modules\Currency\Infrastructure\ExchangeRate\Provider\ConfigProvider;
use Modules\Currency\Infrastructure\ExchangeRate\Provider\EcbProvider;
use Modules\Currency\Infrastructure\Laravel\Command\UpdateExchangeRatesCommand;

class ExchangeRateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register exchange rate providers
        $this->app->tag([
            EcbProvider::class,
            ConfigProvider::class,
        ], 'exchange_rate_providers');

        // Bind the providers as an array to the handler
        $this->app->when(UpdateExchangeRatesCommandHandler::class)
            ->needs('$providers')
            ->give(function ($app) {
                $providers = [];

                // Instantiate each tagged provider
                foreach ($app->tagged('exchange_rate_providers') as $providerClass) {
                    $providers[] = is_string($providerClass) ? $app->make($providerClass) : $providerClass;
                }

                return $providers;
            });

        // Register the command handler
        $this->app->singleton(UpdateExchangeRatesCommandHandler::class);
    }

    public function boot(): void
    {
        // Register the artisan command
        if ($this->app->runningInConsole()) {
            $this->commands([
                UpdateExchangeRatesCommand::class,
            ]);
        }

        // Schedule the exchange rate updates
        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);

            // Update exchange rates every hour
            $schedule->command('currency:update-rates')
                ->hourly()
                ->withoutOverlapping()
                ->runInBackground()
                ->onFailure(function (): void {
                    // Log failure or send notification
                    Log::error('Failed to update exchange rates via scheduled task');
                })
                ->appendOutputTo(storage_path('logs/exchange-rates.log'));
        });
    }
}
