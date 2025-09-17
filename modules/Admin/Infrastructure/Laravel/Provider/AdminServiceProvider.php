<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Laravel\Provider;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Modules\Admin\Application\Command\ClearCacheCommandHandler;
use Modules\Admin\Application\Command\ToggleMaintenanceModeCommandHandler;
use Modules\Admin\Application\Command\UpdateAdminSettingsCommandHandler;
use Modules\Admin\Application\Event\AdminSettingsUpdatedEvent;
use Modules\Admin\Application\Event\CacheClearedEvent;
use Modules\Admin\Application\Event\MaintenanceModeToggledEvent;
use Modules\Admin\Application\Query\GetAdminDashboardQueryHandler;
use Modules\Admin\Application\Query\GetAdminSettingsQueryHandler;
use Modules\Admin\Application\Query\GetSystemStatusQueryHandler;
use Modules\Admin\Domain\Repository\AdminSettingsRepositoryInterface;
use Modules\Admin\Infrastructure\Laravel\Command\CreateSampleNotificationsCommand;
use Modules\Admin\Infrastructure\Laravel\Repository\AdminSettingsEloquentRepository;
use Modules\Shared\Infrastructure\Laravel\Notifications\MaintenanceModeEnabledNotification;
use Modules\User\Infrastructure\Laravel\Models\User;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository interfaces
        $this->app->bind(
            AdminSettingsRepositoryInterface::class,
            AdminSettingsEloquentRepository::class
        );

        // Register command handlers
        $this->app->singleton(UpdateAdminSettingsCommandHandler::class);
        $this->app->singleton(ToggleMaintenanceModeCommandHandler::class);
        $this->app->singleton(ClearCacheCommandHandler::class);

        // Register query handlers
        $this->app->singleton(GetAdminDashboardQueryHandler::class);
        $this->app->singleton(GetSystemStatusQueryHandler::class);
        $this->app->singleton(GetAdminSettingsQueryHandler::class);

        // Register commands for console
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateSampleNotificationsCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Migration');

        // Register event listeners
        $this->registerEventListeners();
    }

    private function registerEventListeners(): void
    {
        $events = $this->app->make('events');

        // Listen to admin settings updated event
        $events->listen(
            AdminSettingsUpdatedEvent::class,
            function ($event) {
                // Log the settings change
                Log::info('Admin settings updated', [
                    'settings_id' => $event->settingsId,
                    'updated_by' => $event->updatedBy,
                    'changes' => $event->changes,
                ]);
            }
        );

        // Listen to maintenance mode toggled event
        $events->listen(
            MaintenanceModeToggledEvent::class,
            function ($event) {
                // Log maintenance mode change
                Log::warning('Maintenance mode toggled', [
                    'enabled' => $event->enabled,
                    'triggered_by' => $event->triggeredBy,
                    'message' => $event->message,
                ]);

                // Send notifications to relevant users
                if ($event->enabled) {
                    // Notify administrators about maintenance mode
                    Notification::send(
                        User::role('super_admin')->get(),
                        new MaintenanceModeEnabledNotification($event->message)
                    );
                }
            }
        );

        // Listen to cache cleared event
        $events->listen(
            CacheClearedEvent::class,
            function ($event) {
                // Log cache clearing
                Log::info('Cache cleared', [
                    'cache_types' => $event->cacheTypes,
                    'triggered_by' => $event->triggeredBy,
                    'results' => $event->results,
                ]);
            }
        );
    }
}
