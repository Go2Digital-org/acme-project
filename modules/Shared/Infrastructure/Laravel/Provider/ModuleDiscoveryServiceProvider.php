<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Provider;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Application\Command\CacheModuleManifestCommandHandler;
use Modules\Shared\Application\Command\ModuleManifestCacheInterface;
use Modules\Shared\Domain\Service\ModuleDiscoveryInterface;
use Modules\Shared\Infrastructure\Laravel\Commands\CacheModulesCommand;
use Modules\Shared\Infrastructure\Laravel\Commands\ClearModulesCommand;
use Modules\Shared\Infrastructure\Laravel\Service\CachedModuleDiscoveryService;
use Modules\Shared\Infrastructure\Laravel\Service\FileModuleManifestCache;
use Modules\Shared\Infrastructure\Laravel\Service\FilesystemModuleDiscoveryService;

final class ModuleDiscoveryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register cache implementation
        $this->app->singleton(ModuleManifestCacheInterface::class, fn ($app): FileModuleManifestCache => new FileModuleManifestCache($app->basePath()));

        // Register filesystem discovery service
        $this->app->singleton(FilesystemModuleDiscoveryService::class, fn ($app): FilesystemModuleDiscoveryService => new FilesystemModuleDiscoveryService(
            $app->basePath(),
            $app->environment(),
        ));

        // Register cached discovery service as the main implementation
        $this->app->singleton(ModuleDiscoveryInterface::class, fn ($app): CachedModuleDiscoveryService => new CachedModuleDiscoveryService(
            $app->make(FilesystemModuleDiscoveryService::class),
            $app->make(ModuleManifestCacheInterface::class),
            $app->environment(),
        ));

        // Register command handler
        $this->app->singleton(CacheModuleManifestCommandHandler::class, fn ($app): CacheModuleManifestCommandHandler => new CacheModuleManifestCommandHandler(
            $app->make(FilesystemModuleDiscoveryService::class), // Always use filesystem for caching
            $app->make(ModuleManifestCacheInterface::class),
        ));

        // Register commands
        $this->commands([
            CacheModulesCommand::class,
            ClearModulesCommand::class,
        ]);
    }

    public function boot(): void
    {
        // Commands are registered in register() method
    }
}
