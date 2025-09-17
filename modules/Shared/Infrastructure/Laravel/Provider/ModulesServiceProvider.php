<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Provider;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Shared\Application\Query\QueryBusInterface;
use Modules\Shared\Application\ReadModel\ReadModelCacheStrategy;
use Modules\Shared\Application\Service\FormComponentService;
use Modules\Shared\Application\Service\HomepageStatsService;
use Modules\Shared\Application\Service\SearchHighlightService;
use Modules\Shared\Application\Service\SocialSharingService;
use Modules\Shared\Domain\Repository\PageRepositoryInterface;
use Modules\Shared\Domain\Repository\SocialMediaRepositoryInterface;
use Modules\Shared\Infrastructure\Laravel\QueryBus\LaravelQueryBus;
use Modules\Shared\Infrastructure\Laravel\Repository\PageEloquentRepository;
use Modules\Shared\Infrastructure\Laravel\Repository\SocialMediaEloquentRepository;
use Modules\Shared\Infrastructure\Laravel\ViewComposer\BreadcrumbViewComposer;
use Modules\Shared\Infrastructure\Laravel\ViewComposer\FooterViewComposer;
use Modules\Shared\Infrastructure\Laravel\ViewComposer\SocialSharingViewComposer;
use Modules\Tenancy\Infrastructure\Laravel\Provider\TenancyServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerSharedServices();

        // Register Event Bus Service Provider
        $this->app->register(EventBusServiceProvider::class);

        // Register Tenancy Service Provider
        $this->app->register(TenancyServiceProvider::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
            $this->registerMigrations();
        }

        $this->registerRepositories();
        $this->registerPolicies();
        $this->registerJobs();
        $this->registerNotifications();
        $this->registerViewComposers();
    }

    private function registerSharedServices(): void
    {
        // CommandBus is now registered in CommandServiceProvider for enhanced features
        $this->app->bind(QueryBusInterface::class, LaravelQueryBus::class);
        $this->app->bind(SocialMediaRepositoryInterface::class, SocialMediaEloquentRepository::class);
        $this->app->bind(PageRepositoryInterface::class, PageEloquentRepository::class);

        // Register shared view services
        $this->registerViewServices();
    }

    private function registerViewServices(): void
    {
        // Register Social Sharing Service
        $this->app->singleton(SocialSharingService::class);

        // Register Search Highlight Service
        $this->app->singleton(SearchHighlightService::class);

        // Register Form Component Service
        $this->app->singleton(FormComponentService::class);

        // Register Homepage Stats Service
        $this->app->singleton(HomepageStatsService::class);

        // Register Read Model Cache Strategy
        $this->app->singleton(ReadModelCacheStrategy::class);
    }

    private function registerCommands(): void
    {
        $commands = $this->getCommands();

        if ($commands !== []) {
            $this->commands($commands);
        }
    }

    /**
     * @return array<int, class-string>
     */
    private function getCommands(): array
    {
        $baseNamespace = 'Modules';
        $basePath = base_path('modules');
        $commands = [];

        foreach (File::directories($basePath) as $domainDirectory) {
            $domainName = basename((string) $domainDirectory);
            $commandPath = "{$domainDirectory}/Infrastructure/Laravel/Command";

            if (! File::exists($commandPath)) {
                continue;
            }

            foreach (File::allFiles($commandPath) as $file) {
                $namespace = "{$baseNamespace}\\{$domainName}\\Infrastructure\\Laravel\\Command";
                $className = "{$namespace}\\{$file->getFilenameWithoutExtension()}";

                if (class_exists($className)) {
                    $commands[] = $className;
                }
            }
        }

        return $commands;
    }

    private function registerMigrations(): void
    {
        $migrations = $this->getMigrations();

        foreach ($migrations as $migrationPath) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }

    /**
     * @return array<int, string>
     */
    private function getMigrations(): array
    {
        $basePath = base_path('modules');
        $migrations = [];

        foreach (File::directories($basePath) as $domainDirectory) {
            $migrationPath = "{$domainDirectory}/Infrastructure/Laravel/Migration";

            if (File::exists($migrationPath)) {
                $migrations[] = $migrationPath;
            }
        }

        return $migrations;
    }

    private function registerRepositories(): void
    {
        $repositories = $this->getRepositories();

        foreach ($repositories as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    /**
     * @return array<class-string, class-string>
     */
    private function getRepositories(): array
    {
        $baseNamespace = 'Modules';
        $basePath = base_path('modules');
        $repositories = [];

        foreach (File::directories($basePath) as $domainDirectory) {
            $domainName = basename((string) $domainDirectory);

            $domainRepoPath = "{$baseNamespace}\\{$domainName}\\Domain\\Repository";
            $infraRepoPath = "{$baseNamespace}\\{$domainName}\\Infrastructure\\Laravel\\Repository";

            $interface = "{$domainRepoPath}\\{$domainName}RepositoryInterface";
            $implementation = "{$infraRepoPath}\\{$domainName}EloquentRepository";

            if (interface_exists($interface) && class_exists($implementation)) {
                $repositories[$interface] = $implementation;
            }
        }

        return $repositories;
    }

    /**
     * Auto-discover and register policies from all modules.
     */
    private function registerPolicies(): void
    {
        $basePath = base_path('modules');

        foreach (File::directories($basePath) as $domainDirectory) {
            $policyPath = "{$domainDirectory}/Infrastructure/Laravel/Policies";

            if (! File::exists($policyPath)) {
                continue;
            }

            // Policies are registered through individual module service providers
            // This method is here for future enhancement if needed
        }
    }

    /**
     * Auto-discover and register jobs from all modules.
     */
    private function registerJobs(): void
    {
        $basePath = base_path('modules');

        foreach (File::directories($basePath) as $domainDirectory) {
            $jobPath = "{$domainDirectory}/Infrastructure/Laravel/Jobs";

            if (! File::exists($jobPath)) {
                continue;
            }

            // Jobs are auto-discovered by Laravel's queue system
            // This method is here for future enhancement if needed
        }
    }

    /**
     * Auto-discover and register notifications from all modules.
     */
    private function registerNotifications(): void
    {
        $basePath = base_path('modules');

        foreach (File::directories($basePath) as $domainDirectory) {
            $notificationPath = "{$domainDirectory}/Infrastructure/Laravel/Notifications";

            if (! File::exists($notificationPath)) {
                continue;
            }

            // Notifications are auto-discovered by Laravel's notification system
            // This method is here for future enhancement if needed
        }
    }

    /**
     * Register view composers for shared functionality.
     */
    private function registerViewComposers(): void
    {
        // Register Social Sharing View Composer
        View::composer([
            'components.share-modal',
            'campaigns.show',
            'page',
            'components.social-sharing',
        ], SocialSharingViewComposer::class);

        // Register Breadcrumb View Composer
        View::composer([
            'components.breadcrumbs',
            'components.layout',
            'components.layout-enhanced',
        ], BreadcrumbViewComposer::class);

        // Register Footer View Composer
        View::composer([
            'components.footer-enhanced',
            'components.footer',
        ], FooterViewComposer::class);
    }
}
