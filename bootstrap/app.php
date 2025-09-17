<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add Sanctum's stateful middleware for API routes to support SPA authentication
        // Also add tenancy initialization for API routes to support tenant-aware cache checking
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Modules\Organization\Infrastructure\Laravel\Middleware\InitializeTenancyForNonCentralDomains::class,
            \Modules\CacheWarming\Infrastructure\Laravel\Middleware\CacheWarmingMiddleware::class,
        ]);

        // Add hybrid tenancy middleware - only initializes for non-central domains
        // Append to ensure session middleware runs first (prevents CSRF issues)
        $middleware->web(append: [
            \Modules\Organization\Infrastructure\Laravel\Middleware\InitializeTenancyForNonCentralDomains::class,
            \Modules\CacheWarming\Infrastructure\Laravel\Middleware\CacheWarmingMiddleware::class,
        ]);

        // Replace default CSRF middleware with our logging version
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
            'paypal/*',
            'webhook/*',
            'webhooks/*',
        ]);

        // Use default Laravel CSRF middleware
        // No custom CSRF middleware needed

        // Laravel Localization middleware
        $middleware->alias([
            'localize' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
            'localizationRedirect' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect' => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
            'localeCookieRedirect' => \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class,
            'localeViewPath' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,

            // API Platform middleware
            'api.locale' => \Modules\Shared\Infrastructure\Laravel\Middleware\ApiLocaleMiddleware::class,
            'api.performance' => \Modules\Shared\Infrastructure\Laravel\Middleware\ApiPerformanceMiddleware::class,

            // Admin access middleware
            'admin' => \Modules\Shared\Infrastructure\Laravel\Middleware\BlockAdminLoginMiddleware::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle API exceptions with consistent JSON responses
        $exceptions->render(function (Throwable $e, Request $request) {
            Integration::captureUnhandledException($e);

            return \Modules\Shared\Infrastructure\ApiPlatform\Exception\LocalizedApiExceptionHandler::render($request, $e);
        });
    })->create();
