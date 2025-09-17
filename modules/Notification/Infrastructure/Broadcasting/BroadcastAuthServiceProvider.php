<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Broadcasting;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for broadcasting authentication.
 *
 * Handles WebSocket channel authentication for real-time notifications.
 */
class BroadcastAuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('testing')) {
            return;
        }

        Broadcast::routes(['middleware' => ['auth:sanctum']]);

        // Load channel definitions
        require base_path('routes/channels.php');
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        //
    }
}
