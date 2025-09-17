<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Laravel\Provider;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Export\Application\Handler\CancelExportCommandHandler;
use Modules\Export\Application\Handler\CleanupExpiredExportsCommandHandler;
use Modules\Export\Application\Handler\RequestDonationExportCommandHandler;
use Modules\Export\Application\QueryHandler\GetExportDownloadUrlQueryHandler;
use Modules\Export\Application\QueryHandler\GetExportStatusQueryHandler;
use Modules\Export\Application\QueryHandler\GetUserExportsQueryHandler;
use Modules\Export\Application\Service\ExportNotificationService;
use Modules\Export\Application\Service\ExportProgressService;
use Modules\Export\Domain\Event\ExportCompleted;
use Modules\Export\Domain\Event\ExportFailed;
use Modules\Export\Domain\Event\ExportRequested;
use Modules\Export\Domain\Repository\ExportJobRepositoryInterface;
use Modules\Export\Infrastructure\Export\ExportStorageService;
use Modules\Export\Infrastructure\Laravel\Jobs\ProcessDonationExportJob;
use Modules\Export\Infrastructure\Laravel\Model\ExportJobEloquent;
use Modules\Export\Infrastructure\Laravel\Repository\EloquentExportJobRepository;
use Modules\User\Domain\Repository\UserRepositoryInterface;

/**
 * Service provider for Export module infrastructure layer.
 * Binds domain interfaces to Laravel-specific implementations.
 */
class ExportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
        $this->registerRepositories();
        $this->registerServices();
        $this->registerHandlers();
    }

    public function boot(): void
    {
        $this->loadMigrations();
        $this->loadViews();
        $this->registerCommands();
        $this->scheduleJobs();
        $this->registerQueue();
        $this->registerEventListeners();
        $this->registerMiddleware();
        $this->registerValidationRules();
        $this->registerViewComposers();
        $this->registerBladeDirectives();
    }

    /**
     * Register repository bindings
     */
    private function registerRepositories(): void
    {
        $this->app->bind(
            ExportJobRepositoryInterface::class,
            EloquentExportJobRepository::class
        );

        // Bind the Eloquent model for dependency injection
        $this->app->bind(ExportJobEloquent::class, fn (): ExportJobEloquent => new ExportJobEloquent);
    }

    /**
     * Register service bindings
     */
    private function registerServices(): void
    {
        // Export Storage Service
        $this->app->singleton(ExportStorageService::class, fn (): ExportStorageService => new ExportStorageService(
            storageDisk: config('export.storage.disk', 'local')
        ));

        // Export Progress Service
        $this->app->singleton(ExportProgressService::class, fn ($app): ExportProgressService => new ExportProgressService(
            repository: $app->make(ExportJobRepositoryInterface::class)
        ));

        // Export Notification Service
        $this->app->singleton(fn ($app): ExportNotificationService => new ExportNotificationService(
            exportRepository: $app->make(ExportJobRepositoryInterface::class),
            userRepository: $app->make(UserRepositoryInterface::class)
        ));
    }

    /**
     * Register command and query handlers
     */
    private function registerHandlers(): void
    {
        // Command Handlers
        $this->app->bind(RequestDonationExportCommandHandler::class);
        $this->app->bind(CancelExportCommandHandler::class);
        $this->app->bind(CleanupExpiredExportsCommandHandler::class);

        // Query Handlers
        $this->app->bind(GetExportStatusQueryHandler::class);
        $this->app->bind(GetExportDownloadUrlQueryHandler::class);
        $this->app->bind(GetUserExportsQueryHandler::class);
    }

    /**
     * Load migrations
     */
    private function loadMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $migrationPath = __DIR__ . '/../../../../../../database/migrations';

            if (is_dir($migrationPath)) {
                $this->loadMigrationsFrom($migrationPath);
            }
        }
    }

    /**
     * Load views
     */
    private function loadViews(): void
    {
        // Register view namespace for export module
        $this->loadViewsFrom(resource_path('views/exports'), 'export');

        // Publish views if needed
        if ($this->app->runningInConsole()) {
            $this->publishes([
                resource_path('views/exports') => resource_path('views/vendor/export'),
            ], 'export-views');
        }
    }

    /**
     * Register console commands
     */
    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            // Commands will be registered when created
            // $this->commands([
            //     \Modules\Export\Infrastructure\Laravel\Console\ProcessExportsCommand::class,
            //     \Modules\Export\Infrastructure\Laravel\Console\CleanupExpiredExportsCommand::class,
            //     \Modules\Export\Infrastructure\Laravel\Console\ExportStatsCommand::class,
            // ]);
        }
    }

    /**
     * Schedule recurring jobs
     */
    private function scheduleJobs(): void
    {
        if ($this->app->runningInConsole()) {
            $this->app->resolving(Schedule::class, function (Schedule $schedule): void {
                // Scheduled tasks will be enabled when commands are created
                // // Clean up expired exports daily at 2 AM
                // $schedule->command('export:cleanup-expired')
                //     ->dailyAt('02:00')
                //     ->name('export-cleanup-expired')
                //     ->withoutOverlapping()
                //     ->runInBackground();

                // Clean up old temporary files every 6 hours
                $schedule->call(function (): void {
                    $storageService = app(ExportStorageService::class);
                    $deletedCount = $storageService->cleanupOldTempFiles();

                    if ($deletedCount > 0) {
                        logger("Cleaned up {$deletedCount} old temporary export files");
                    }
                })
                    ->everyFourHours()
                    ->name('export-cleanup-temp-files')
                    ->withoutOverlapping();
            });
        }
    }

    /**
     * Register queue configurations
     */
    private function registerQueue(): void
    {
        // Register the export queue
        $this->app->booted(function (): void {
            // Set up queue configuration for export jobs
            config([
                'queue.connections.database.queue' => 'exports',
                'horizon.environments.production.exports' => [
                    'connection' => 'redis',
                    'queue' => ['exports', 'default'],
                    'balance' => 'auto',
                    'minProcesses' => 1,
                    'maxProcesses' => 5,
                    'tries' => 3,
                    'timeout' => 3600, // 1 hour timeout
                ],
            ]);
        });
    }

    /**
     * Register config files
     */
    private function registerConfig(): void
    {
        $configPath = __DIR__ . '/../config/export.php';

        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'export');

            if ($this->app->runningInConsole()) {
                $this->publishes([
                    $configPath => config_path('export.php'),
                ], 'export-config');
            }
        }
    }

    /**
     * Register event listeners
     */
    private function registerEventListeners(): void
    {
        // Listen to export domain events
        $this->app->make('events')->listen(
            ExportRequested::class,
            function ($event): void {
                // Dispatch the processing job
                ProcessDonationExportJob::dispatch(
                    exportId: $event->exportId->toString(),
                    filters: $event->filters,
                    format: $event->format->value,
                    userId: $event->userId,
                    organizationId: $event->organizationId
                );
            }
        );

        $this->app->make('events')->listen(
            ExportCompleted::class,
            function ($event): void {
                // Send notification when export completes
                $notificationService = $this->app->make(ExportNotificationService::class);
                $notificationService->sendCompletionNotification(
                    $event->exportId,
                    $event->filePath,
                    $event->fileSize,
                    $event->recordsExported
                );
            }
        );

        $this->app->make('events')->listen(
            ExportFailed::class,
            function ($event): void {
                // Send notification when export fails
                $notificationService = $this->app->make(ExportNotificationService::class);
                $notificationService->sendFailureNotification(
                    $event->exportId,
                    $event->errorMessage,
                    $event->processedRecords
                );
            }
        );
    }

    /**
     * Register middleware
     */
    private function registerMiddleware(): void
    {
        // Register export-specific middleware
        // TODO: Create middleware classes when needed
        // $router = $this->app['router'];
        // $router->aliasMiddleware('export.throttle', \Modules\Export\Infrastructure\Laravel\Middleware\ExportThrottleMiddleware::class);
        // $router->aliasMiddleware('export.auth', \Modules\Export\Infrastructure\Laravel\Middleware\ExportAuthMiddleware::class);
    }

    /**
     * Register view composers
     */
    private function registerViewComposers(): void
    {
        // Register view composers for export-related views
        // TODO: Create view composer when needed
        // if ($this->app->bound('view')) {
        //     $this->app['view']->composer(
        //         'export.*',
        //         \Modules\Export\Infrastructure\Laravel\View\ExportViewComposer::class
        //     );
        // }
    }

    /**
     * Register validation rules
     */
    private function registerValidationRules(): void
    {
        $this->app->make('validator')->extend('export_format', fn ($attribute, $value, $parameters, $validator): bool => in_array(strtolower((string) $value), ['csv', 'excel', 'pdf']));

        $this->app->make('validator')->extend('export_filters', fn ($attribute, $value, $parameters, $validator): bool =>
            // Validate export filter structure
            is_array($value));
    }

    /**
     * Register blade directives
     */
    private function registerBladeDirectives(): void
    {
        if ($this->app->bound('blade.compiler')) {
            // @exportProgress directive
            $this->app->make('blade.compiler')->directive('exportProgress', fn ($expression): string => "<?php echo app('Modules\\Export\\Application\\Service\\ExportProgressService')->getProgress({$expression}); ?>");
        }
    }

    /**
     * Get the services provided by the provider
     *
     * @return array<int, class-string>
     */
    public function provides(): array
    {
        return [
            ExportJobRepositoryInterface::class,
            ExportStorageService::class,
            ExportProgressService::class,
            ExportNotificationService::class,
            RequestDonationExportCommandHandler::class,
            CancelExportCommandHandler::class,
            CleanupExpiredExportsCommandHandler::class,
            GetExportStatusQueryHandler::class,
            GetExportDownloadUrlQueryHandler::class,
            GetUserExportsQueryHandler::class,
        ];
    }
}
