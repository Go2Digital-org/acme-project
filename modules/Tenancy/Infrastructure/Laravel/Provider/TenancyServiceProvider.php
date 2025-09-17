<?php

declare(strict_types=1);

namespace Modules\Tenancy\Infrastructure\Laravel\Provider;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Log;
use Modules\Organization\Domain\Event\OrganizationCreatedEvent;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Infrastructure\Laravel\Job\ProvisionOrganizationTenantJob;
use Modules\Tenancy\Domain\Model\Tenant;
use Modules\Tenancy\Domain\Repository\TenantRepositoryInterface;
use Modules\Tenancy\Infrastructure\Database\TenantDatabaseManager;
use Modules\Tenancy\Infrastructure\Laravel\Command\CreateTenantCommand;
use Modules\Tenancy\Infrastructure\Laravel\Command\DeleteTenantCommand;
use Modules\Tenancy\Infrastructure\Laravel\Command\ListTenantsCommand;
use Modules\Tenancy\Infrastructure\Laravel\Command\MigrateTenantCommand;
use Modules\Tenancy\Infrastructure\Laravel\Command\ProvisionTenantCommand;
use Modules\Tenancy\Infrastructure\Laravel\Middleware\InitializeTenancyByDomain;
use Modules\Tenancy\Infrastructure\Laravel\Middleware\InitializeTenancyBySubdomain;
use Modules\Tenancy\Infrastructure\Laravel\Middleware\PreventAccessFromCentralDomains;
use Modules\Tenancy\Infrastructure\Laravel\Repository\TenantEloquentRepository;
use Modules\Tenancy\Infrastructure\Meilisearch\TenantSearchIndexManager;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Events\DatabaseCreated;
use Stancl\Tenancy\Events\DatabaseMigrated;
use Stancl\Tenancy\Events\RevertedToCentralContext;
use Stancl\Tenancy\Events\TenancyBootstrapped;
use Stancl\Tenancy\Events\TenancyEnded;
use Stancl\Tenancy\Events\TenancyInitialized;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Listeners\BootstrapTenancy;
use Stancl\Tenancy\Listeners\RevertToCentralContext;
use Stancl\Tenancy\Middleware as StanclMiddleware;

/**
 * Tenancy Service Provider.
 *
 * Registers all tenancy-related services, middleware, and event listeners.
 * Follows hexagonal architecture by keeping all infrastructure concerns in the module.
 */
class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // No need to merge config - we use the main config/tenancy.php file
        // This avoids conflicts and confusion from having duplicate configs

        // Bind tenant model
        config(['tenancy.tenant_model' => Organization::class]);

        // Register infrastructure services
        $this->app->singleton(TenantDatabaseManager::class);
        $this->app->singleton(TenantSearchIndexManager::class);

        // Register custom middleware aliases
        $this->app->singleton(InitializeTenancyByDomain::class);
        $this->app->singleton(InitializeTenancyBySubdomain::class);
        $this->app->singleton(PreventAccessFromCentralDomains::class);

        // Bind repository interfaces
        $this->app->bind(
            TenantRepositoryInterface::class,
            TenantEloquentRepository::class,
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->bootMigrations();
        $this->bootRoutes();
        $this->bootMiddleware();
        $this->bootEvents();
        $this->bootCommands();
        // $this->publishConfig(); // Not needed - using main config file
    }

    /**
     * Boot migrations.
     */
    protected function bootMigrations(): void
    {
        // Load central migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Migration/Central');

        // Register tenant migration paths
        $this->app->booted(function (): void {
            $tenantMigrationPaths = [
                'modules/User/Infrastructure/Laravel/Migration/Tenant',
                'modules/Campaign/Infrastructure/Laravel/Migration/Tenant',
                'modules/Donation/Infrastructure/Laravel/Migration/Tenant',
                'modules/Notification/Infrastructure/Laravel/Migration/Tenant',
                'modules/Category/Infrastructure/Laravel/Migration/Tenant',
                'modules/Currency/Infrastructure/Laravel/Migration/Tenant',
                'modules/Search/Infrastructure/Laravel/Migration/Tenant',
            ];

            foreach ($tenantMigrationPaths as $path) {
                $fullPath = base_path($path);
                if (is_dir($fullPath)) {
                    config(['tenancy.migration_paths' => array_merge(
                        config('tenancy.migration_paths', []),
                        [$fullPath]
                    )]);
                }
            }
        });
    }

    /**
     * Boot routes.
     */
    protected function bootRoutes(): void
    {
        // Don't override default Laravel routing
        // The central routes are already loaded by RouteServiceProvider
        // We only need to set up tenant routes for subdomains
    }

    /**
     * Boot middleware.
     */
    protected function bootMiddleware(): void
    {
        // Register middleware aliases
        /** @var Router $router */
        $router = $this->app->make('router');

        // Use our custom middleware classes
        $router->aliasMiddleware('tenant.domain', InitializeTenancyByDomain::class);
        $router->aliasMiddleware('tenant.subdomain', InitializeTenancyBySubdomain::class);
        $router->aliasMiddleware('central', PreventAccessFromCentralDomains::class);

        // Also keep Stancl middleware available
        $router->aliasMiddleware('tenant', StanclMiddleware\InitializeTenancyByDomain::class);
        $router->aliasMiddleware('tenant.path', StanclMiddleware\InitializeTenancyByPath::class);
        $router->aliasMiddleware('tenant.request', StanclMiddleware\InitializeTenancyByRequestData::class);

        // Make tenancy middleware highest priority
        $this->makeTenancyMiddlewareHighestPriority();
    }

    /**
     * Boot event listeners.
     */
    protected function bootEvents(): void
    {
        // Organization/Tenant lifecycle events
        Event::listen(
            OrganizationCreatedEvent::class,
            function ($event): void {
                // Dispatch provisioning job when organization is created
                if ($event->organization->subdomain) {
                    ProvisionOrganizationTenantJob::dispatch(
                        $event->organization,
                        $event->organization->tenant_data['admin'] ?? []
                    );
                }
            }
        );

        // Stancl/Tenancy events for tenancy initialization
        Event::listen(TenancyInitialized::class, BootstrapTenancy::class);

        Event::listen(TenancyEnded::class, RevertToCentralContext::class);

        // Database events
        Event::listen(DatabaseCreated::class, function ($event): void {
            Log::info('Tenant database created', [
                'tenant' => $event->tenant->id,
                'database' => $event->tenant->database,
            ]);
        });

        Event::listen(DatabaseMigrated::class, function ($event): void {
            Log::info('Tenant database migrated', [
                'tenant' => $event->tenant->id,
            ]);
        });

        // Configure Scout to use tenant prefix when in tenant context
        Event::listen(TenancyBootstrapped::class, function ($event): void {
            if ($event->tenancy->tenant) {
                $tenantId = (string) $event->tenancy->tenant->getTenantKey();
                $prefix = 'tenant_' . str_replace('-', '_', $tenantId) . '_';
                config(['scout.prefix' => $prefix]);
            }
        });

        Event::listen(RevertedToCentralContext::class, function (): void {
            config(['scout.prefix' => '']);
        });
    }

    /**
     * Boot commands.
     */
    protected function bootCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateTenantCommand::class,
                MigrateTenantCommand::class,
                ProvisionTenantCommand::class,
                ListTenantsCommand::class,
                DeleteTenantCommand::class,
            ]);
        }
    }

    /**
     * Publish configuration.
     */
    protected function publishConfig(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../Config/tenancy.php' => config_path('tenancy.php'),
            ], 'tenancy-config');
        }
    }

    /**
     * Make tenancy middleware highest priority.
     */
    protected function makeTenancyMiddlewareHighestPriority(): void
    {
        $tenancyMiddleware = [
            PreventAccessFromCentralDomains::class,
            InitializeTenancyByDomain::class,
            InitializeTenancyBySubdomain::class,
            StanclMiddleware\InitializeTenancyByDomain::class,
            StanclMiddleware\InitializeTenancyBySubdomain::class,
            StanclMiddleware\InitializeTenancyByDomainOrSubdomain::class,
            StanclMiddleware\InitializeTenancyByPath::class,
            StanclMiddleware\InitializeTenancyByRequestData::class,
        ];

        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            if (method_exists($kernel, 'prependToMiddlewarePriority')) {
                $kernel->prependToMiddlewarePriority($middleware);
            }
        }
    }
}
