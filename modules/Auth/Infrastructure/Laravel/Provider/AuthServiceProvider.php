<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Provider;

use Exception;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Application\Command\UpdateAvatarCommandHandler;
use Modules\Auth\Infrastructure\Filament\Resources\RoleResource;
use Modules\Auth\Infrastructure\Laravel\Listener\AuthEventSubscriber;
use Modules\Auth\Infrastructure\Laravel\Policies\RolePolicy;
use Spatie\Permission\Models\Role;

final class AuthServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    protected array $policies = [
        Role::class => RolePolicy::class,
    ];

    public function register(): void
    {
        // Register command handlers
        $this->app->bind(UpdateAvatarCommandHandler::class);
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Migration');

        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Register authentication event listeners
        $subscriber = $this->app->make(AuthEventSubscriber::class);

        foreach ($subscriber->subscribe(Event::getFacadeRoot()) as $event => $method) {
            Event::listen($event, [$subscriber, $method]);
        }

        // Register Filament Resources - moved to boot() for better service ordering
        // Check if Filament is available (bound in the container) before using it
        if (class_exists(Filament::class) && $this->app->bound('filament')) {
            try {
                Filament::serving(function (): void {
                    Filament::registerResources([
                        RoleResource::class,
                    ]);
                });
            } catch (Exception $e) {
                // Silently ignore in test environment if Filament is not yet available
                // This handles race conditions in parallel testing
                if (! app()->environment('testing')) {
                    throw $e;
                }
            }
        }
    }
}
