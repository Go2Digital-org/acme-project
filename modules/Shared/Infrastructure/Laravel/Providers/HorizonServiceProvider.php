<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();
    }

    protected function gate(): void
    {
        Gate::define('viewHorizon', fn ($user = null) => $user->hasRole('super_admin'));
    }
}
