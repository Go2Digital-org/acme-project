<?php

declare(strict_types=1);

namespace Modules\Localization\Infrastructure\Laravel\Provider;

use Illuminate\Support\ServiceProvider;

final class FilamentTranslationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // The FilamentTranslateField package configuration is handled
        // directly in the form components, not globally
        // Default locales are set when using Translate::make()->locales()
    }
}
