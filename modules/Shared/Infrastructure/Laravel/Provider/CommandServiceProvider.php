<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Provider;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\ServiceProvider;
use Modules\Shared\Application\Bus\CommandBus;
use Modules\Shared\Application\Bus\CommandBusInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Modules\Shared\Infrastructure\Laravel\Console\Commands\MeilisearchConfigureCommand;
use Modules\Shared\Infrastructure\Laravel\Console\Commands\PostDeployCommand;
use Modules\Shared\Infrastructure\Laravel\Console\Commands\QueueMonitoringCommand;
use Modules\Shared\Infrastructure\Laravel\Console\Commands\ScoutImportAsync;
use Modules\Shared\Infrastructure\Laravel\Console\Commands\ScoutIndexMonitor;

final class CommandServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register console commands
        /** @var array<int, string> $commands */
        $commands = [
            MeilisearchConfigureCommand::class,
            PostDeployCommand::class,
            QueueMonitoringCommand::class,
            ScoutImportAsync::class,
            ScoutIndexMonitor::class,
        ];
        $this->commands($commands);

        // Register the enhanced CommandBus
        $this->registerCommandBus();
    }

    public function boot(): void
    {
        // Commands are registered in register() method
    }

    /**
     * Register the enhanced CommandBus with automatic transaction and event handling.
     */
    private function registerCommandBus(): void
    {
        $this->app->singleton(CommandBusInterface::class, fn ($app): CommandBus => new CommandBus(
            container: $app,
            eventDispatcher: $app->make(EventDispatcher::class),
            commandToHandlerMap: $this->getCommandToHandlerMap()
        ));

        // Bind the legacy CommandBusInterface to the same instance for backward compatibility
        // This allows existing code to continue working while new code can use the enhanced version
        /** @var class-string $legacyInterface */
        $legacyInterface = \Modules\Shared\Application\Command\CommandBusInterface::class;
        $this->app->alias(CommandBusInterface::class, $legacyInterface);
    }

    /**
     * Get explicit command to handler mappings.
     *
     * This allows for custom mappings that don't follow the standard convention.
     * Most commands will use the automatic convention-based mapping.
     *
     * @return array<class-string<CommandInterface>, class-string<CommandHandlerInterface>>
     */
    private function getCommandToHandlerMap(): array
    {
        return [
            // Add any explicit command -> handler mappings here if needed
            // Example:
            // \Modules\CustomModule\Application\Command\SpecialCommand::class => \Modules\CustomModule\Application\Command\CustomHandler::class,
        ];
    }
}
