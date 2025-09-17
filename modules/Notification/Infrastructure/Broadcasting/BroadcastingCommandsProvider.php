<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Broadcasting;

use Illuminate\Support\ServiceProvider;
use Modules\Notification\Infrastructure\Broadcasting\Commands\BroadcastingDebugCommand;
use Modules\Notification\Infrastructure\Broadcasting\Commands\BroadcastingStatusCommand;
use Modules\Notification\Infrastructure\Broadcasting\Commands\TestNotificationBroadcastCommand;
use Modules\Notification\Infrastructure\Broadcasting\Service\NotificationBroadcaster;

/**
 * Service provider for broadcasting console commands.
 *
 * Registers Artisan commands for testing and debugging the
 * real-time notification broadcasting system.
 */
class BroadcastingCommandsProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                TestNotificationBroadcastCommand::class,
                BroadcastingDebugCommand::class,
                BroadcastingStatusCommand::class,
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->app->singleton(TestNotificationBroadcastCommand::class, fn ($app): TestNotificationBroadcastCommand => new TestNotificationBroadcastCommand(
            $app->make(NotificationBroadcaster::class),
        ));

        $this->app->singleton(BroadcastingDebugCommand::class, fn ($app): BroadcastingDebugCommand => new BroadcastingDebugCommand(
            $app->make(NotificationBroadcaster::class),
        ));

        $this->app->singleton(BroadcastingStatusCommand::class);
    }
}
