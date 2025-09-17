<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Provider;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Application\Command\SuperSeedCommandHandler;
use Modules\Shared\Infrastructure\Laravel\Console\Commands\SeedInitialCommand;
use Modules\Shared\Infrastructure\Laravel\Console\Commands\SuperSeederCommand;

class SuperSeederServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SuperSeedCommandHandler::class);
        $this->app->singleton(SuperSeederCommand::class);
        $this->app->singleton(SeedInitialCommand::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SuperSeederCommand::class,
                SeedInitialCommand::class,
            ]);
        }
    }
}
