<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Provider;

use Illuminate\Support\ServiceProvider;
use Modules\Organization\Application\Command\ActivateOrganizationCommandHandler;
use Modules\Organization\Application\Command\CreateOrganizationCommandHandler;
use Modules\Organization\Application\Command\DeactivateOrganizationCommandHandler;
use Modules\Organization\Application\Command\ImpersonateUserCommandHandler;
use Modules\Organization\Application\Command\UpdateOrganizationCommandHandler;
use Modules\Organization\Application\Command\VerifyOrganizationCommandHandler;
use Modules\Organization\Application\Query\FindOrganizationByIdQueryHandler;
use Modules\Organization\Application\Query\GetOrganizationDashboardQueryHandler;
use Modules\Organization\Application\Query\ListActiveOrganizationsQueryHandler;
use Modules\Organization\Application\Query\ListOrganizationsQueryHandler;
use Modules\Organization\Application\Query\ListPendingVerificationOrganizationsQueryHandler;
use Modules\Organization\Application\Query\SearchOrganizationsQueryHandler;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;
use Modules\Organization\Domain\Service\AdminUserResolver;
use Modules\Organization\Domain\Service\ImpersonationService;
use Modules\Organization\Infrastructure\ApiPlatform\Handler\Processor\CreateOrganizationProcessor;
use Modules\Organization\Infrastructure\ApiPlatform\Handler\Processor\UpdateOrganizationProcessor;
use Modules\Organization\Infrastructure\ApiPlatform\Handler\Processor\VerifyOrganizationProcessor;
use Modules\Organization\Infrastructure\ApiPlatform\Handler\Provider\OrganizationCollectionProvider;
use Modules\Organization\Infrastructure\ApiPlatform\Handler\Provider\OrganizationItemProvider;
use Modules\Organization\Infrastructure\Laravel\Observer\OrganizationObserver;
use Modules\Organization\Infrastructure\Laravel\Repository\OrganizationEloquentRepository;
use Modules\Shared\Domain\Contract\OrganizationInterface;
use Stancl\Tenancy\DatabaseConfig;

class OrganizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository binding
        $this->app->bind(
            OrganizationRepositoryInterface::class,
            OrganizationEloquentRepository::class,
        );

        // Interface bindings
        $this->app->bind(
            OrganizationInterface::class,
            Organization::class,
        );

        // Register impersonation services
        $this->app->singleton(ImpersonationService::class);
        $this->app->singleton(ImpersonateUserCommandHandler::class);
        $this->app->singleton(AdminUserResolver::class);

        // Register command handlers
        $this->app->bind(CreateOrganizationCommandHandler::class);
        $this->app->bind(UpdateOrganizationCommandHandler::class);
        $this->app->bind(VerifyOrganizationCommandHandler::class);
        $this->app->bind(ActivateOrganizationCommandHandler::class);
        $this->app->bind(DeactivateOrganizationCommandHandler::class);

        // Register query handlers
        $this->app->bind(SearchOrganizationsQueryHandler::class);
        $this->app->bind(FindOrganizationByIdQueryHandler::class);
        $this->app->bind(ListOrganizationsQueryHandler::class);
        $this->app->bind(ListActiveOrganizationsQueryHandler::class);
        $this->app->bind(ListPendingVerificationOrganizationsQueryHandler::class);
        $this->app->bind(GetOrganizationDashboardQueryHandler::class);

        // Register API Platform handlers
        $this->app->bind(OrganizationCollectionProvider::class);
        $this->app->bind(OrganizationItemProvider::class);
        $this->app->bind(CreateOrganizationProcessor::class);
        $this->app->bind(UpdateOrganizationProcessor::class);
        $this->app->bind(VerifyOrganizationProcessor::class);
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Migration');

        // Register model observer for automatic tenant provisioning
        // Use booted to ensure all services are ready
        $this->app->booted(function (): void {
            Organization::observe(OrganizationObserver::class);
        });

        // Override the tenancy database name generator to use our custom database names
        DatabaseConfig::$databaseNameGenerator = function ($tenant): string {
            if ($tenant instanceof Organization) {
                return $tenant->getDatabaseName();
            }

            // Fallback to default behavior for other tenant types
            return config('tenancy.database.prefix') . $tenant->getTenantKey() . config('tenancy.database.suffix');
        };
    }
}
