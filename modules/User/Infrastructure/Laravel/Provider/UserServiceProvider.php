<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Laravel\Provider;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Shared\Domain\Contract\UserInterface;
use Modules\User\Domain\Repository\UserRepositoryInterface;
use Modules\User\Infrastructure\Laravel\Command\RestoreAdminCommand;
use Modules\User\Infrastructure\Laravel\Command\SetupSuperAdminCommand;
use Modules\User\Infrastructure\Laravel\Models\User;
use Modules\User\Infrastructure\Laravel\Policies\UserPolicy;
use Modules\User\Infrastructure\Laravel\Repository\UserEloquentRepository;

/**
 * User Service Provider.
 *
 * Registers User domain services and bindings.
 */
class UserServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            UserRepositoryInterface::class,
            UserEloquentRepository::class,
        );

        // Interface bindings
        $this->app->bind(
            UserInterface::class,
            User::class,
        );

        // Register commands for console
        if ($this->app->runningInConsole()) {
            $this->commands([
                RestoreAdminCommand::class,
                SetupSuperAdminCommand::class,
            ]);
        }

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(User::class, UserPolicy::class);

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Migration');
    }
}
