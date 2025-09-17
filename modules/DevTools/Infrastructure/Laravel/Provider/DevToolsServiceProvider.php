<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Provider;

use Illuminate\Support\ServiceProvider;
use Modules\DevTools\Domain\Service\CodeGeneratorService;
use Modules\DevTools\Infrastructure\Laravel\Command\AddHexCommandCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\AddHexDomainStructureCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\AddHexEloquentRepositoryCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\AddHexEventCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\AddHexFactoryCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\AddHexFindQueryCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\AddHexFormRequestCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\AddHexMenuCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\AddHexMigrationCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\AddHexModelCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\AddHexRepositoryCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\AddHexResourceCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\AddHexSeederCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\AddHexServiceProviderCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\AddProcessorToDomainCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\AddProviderToDomainCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\CreateDomainStructureCommand;
use Modules\DevTools\Infrastructure\Laravel\Command\ValidateDomainCommand;

final class DevToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register the code generator service
        $this->app->singleton(function ($app): CodeGeneratorService {
            $stubsPath = base_path('modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal');
            $modulesPath = base_path('modules');

            return new CodeGeneratorService($stubsPath, $modulesPath);
        });

        // Register console commands when running in console
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }
    }

    public function boot(): void
    {
        // Boot logic if needed
    }

    /**
     * Register console commands.
     */
    private function registerCommands(): void
    {
        // Register core domain generator commands
        $coreCommands = [
            CreateDomainStructureCommand::class,
            ValidateDomainCommand::class,
            AddHexDomainStructureCommand::class,
            AddHexMenuCommand::class,
            AddHexCommandCommand::class,
            AddHexEventCommand::class,
            AddHexFindQueryCommand::class,
            AddHexRepositoryCommand::class,
            AddHexEloquentRepositoryCommand::class,
            AddHexFormRequestCommand::class,
            AddHexServiceProviderCommand::class,
            // API Platform commands
            AddProcessorToDomainCommand::class,
            AddProviderToDomainCommand::class,
            AddHexResourceCommand::class,
            // Database commands
            AddHexModelCommand::class,
            AddHexMigrationCommand::class,
            AddHexSeederCommand::class,
            AddHexFactoryCommand::class,
        ];

        $this->commands($coreCommands);

        // Register Hex commands from the Hex subdirectory
        $commandsPath = __DIR__ . '/../Command/Hex';

        if (! is_dir($commandsPath)) {
            return;
        }

        $commands = [];
        $files = glob($commandsPath . '/*.php');

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $className = 'Modules\\DevTools\\Infrastructure\\Laravel\\Command\\Hex\\' .
                        basename($file, '.php');

            if (class_exists($className)) {
                $commands[] = $className;
            }
        }

        if ($commands !== []) {
            $this->commands($commands);
        }
    }
}
