<?php

declare(strict_types=1);

namespace App\Providers;

use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Support\ServiceProvider;
use Modules\Admin\Infrastructure\Laravel\Provider\FilamentPanelServiceProvider;
use Modules\Shared\Domain\Service\ModuleDiscoveryInterface;
use Modules\Shared\Infrastructure\Laravel\Provider\HexagonalFactoryServiceProvider;
use Modules\Shared\Infrastructure\Laravel\Provider\HexagonalMigrationServiceProvider;
use Modules\Shared\Infrastructure\Laravel\Provider\ModuleDiscoveryServiceProvider;

/**
 * Minimal Application Service Provider.
 *
 * Auto-discovers and registers all module providers.
 * The only file needed in /app directory.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register module discovery service first
        $this->app->register(ModuleDiscoveryServiceProvider::class);

        // Register factory resolver before modules are loaded
        $this->app->register(HexagonalFactoryServiceProvider::class);

        // Register migration loader for hexagonal modules
        $this->app->register(HexagonalMigrationServiceProvider::class);

        $this->discoverModuleServiceProviders();
    }

    public function boot(): void
    {
        // Register API Platform handlers after all dependencies are loaded
        $this->registerApiPlatformHandlers();
    }

    /**
     * Discover and register all service providers from modules using cached manifest.
     */
    private function discoverModuleServiceProviders(): void
    {
        /** @var ModuleDiscoveryInterface $discovery */
        $discovery = $this->app->make(ModuleDiscoveryInterface::class);
        $manifest = $discovery->discover();

        // Register FilamentPanelServiceProvider FIRST to ensure Filament is available
        // This prevents race conditions in parallel testing where other providers
        // might try to use Filament before it's registered
        $this->app->register(FilamentPanelServiceProvider::class);

        // Register all discovered service providers
        foreach ($manifest->getServiceProviders() as $providerClass) {
            $this->app->register($providerClass);
        }
    }

    /**
     * Register API Platform handlers from all modules using cached manifest.
     */
    private function registerApiPlatformHandlers(): void
    {
        /** @var ModuleDiscoveryInterface $discovery */
        $discovery = $this->app->make(ModuleDiscoveryInterface::class);
        $manifest = $discovery->discover();

        // Register all API processors
        foreach ($manifest->getApiProcessors() as $processorClass) {
            $this->app->singleton($processorClass);
            $this->app->tag($processorClass, [ProcessorInterface::class]);
        }

        // Register all API providers
        foreach ($manifest->getApiProviders() as $providerClass) {
            $this->app->singleton($providerClass);
            $this->app->tag($providerClass, [ProviderInterface::class]);
        }
    }
}
