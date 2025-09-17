<?php

/**
 * FrankenPHP OPcache Preload Script for ACME Corp
 *
 * This script preloads frequently-used classes into OPcache memory
 * for improved performance. Preloaded files are available to all
 * worker processes without needing to be loaded from disk.
 */

// Only run if opcache is enabled
if (! function_exists('opcache_compile_file')) {
    return;
}

$basePath = dirname(__DIR__, 2);

// Autoloader must be included first
require_once $basePath.'/vendor/autoload.php';

// Function to preload a file with error handling
function preloadFile($file)
{
    try {
        if (! opcache_compile_file($file)) {
            error_log("[Preload] Failed to compile: $file");
        }
    } catch (\Throwable $e) {
        error_log("[Preload] Error compiling $file: ".$e->getMessage());
    }
}

// Function to preload directory
function preloadDirectory($directory, $pattern = '*.php')
{
    $files = glob($directory.'/'.$pattern);
    foreach ($files as $file) {
        if (is_file($file)) {
            preloadFile($file);
        }
    }

    // Recursively preload subdirectories
    $subdirs = glob($directory.'/*', GLOB_ONLYDIR);
    foreach ($subdirs as $subdir) {
        preloadDirectory($subdir, $pattern);
    }
}

// ================================================================
// Preload Laravel Framework Core Files
// ================================================================

$frameworkFiles = [
    // Core Application
    '/vendor/laravel/framework/src/Illuminate/Foundation/Application.php',
    '/vendor/laravel/framework/src/Illuminate/Container/Container.php',

    // HTTP Layer
    '/vendor/laravel/framework/src/Illuminate/Http/Request.php',
    '/vendor/laravel/framework/src/Illuminate/Http/Response.php',
    '/vendor/laravel/framework/src/Illuminate/Http/JsonResponse.php',
    '/vendor/laravel/framework/src/Illuminate/Http/RedirectResponse.php',

    // Routing
    '/vendor/laravel/framework/src/Illuminate/Routing/Router.php',
    '/vendor/laravel/framework/src/Illuminate/Routing/Route.php',
    '/vendor/laravel/framework/src/Illuminate/Routing/RouteCollection.php',
    '/vendor/laravel/framework/src/Illuminate/Routing/Controller.php',

    // Database
    '/vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php',
    '/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php',
    '/vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php',
    '/vendor/laravel/framework/src/Illuminate/Database/Connection.php',

    // Support Classes
    '/vendor/laravel/framework/src/Illuminate/Support/Collection.php',
    '/vendor/laravel/framework/src/Illuminate/Support/Str.php',
    '/vendor/laravel/framework/src/Illuminate/Support/Arr.php',
    '/vendor/laravel/framework/src/Illuminate/Support/Facades/Facade.php',

    // Middleware
    '/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php',
    '/vendor/laravel/framework/src/Illuminate/Cookie/Middleware/EncryptCookies.php',
    '/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php',
    '/vendor/laravel/framework/src/Illuminate/Foundation/Http/Middleware/VerifyCsrfToken.php',

    // Cache
    '/vendor/laravel/framework/src/Illuminate/Cache/CacheManager.php',
    '/vendor/laravel/framework/src/Illuminate/Cache/Repository.php',

    // Session
    '/vendor/laravel/framework/src/Illuminate/Session/SessionManager.php',
    '/vendor/laravel/framework/src/Illuminate/Session/Store.php',

    // Validation
    '/vendor/laravel/framework/src/Illuminate/Validation/Validator.php',
    '/vendor/laravel/framework/src/Illuminate/Validation/Factory.php',
];

foreach ($frameworkFiles as $file) {
    $fullPath = $basePath.$file;
    if (file_exists($fullPath)) {
        preloadFile($fullPath);
    }
}

// ================================================================
// Preload Application Files
// ================================================================

// Preload all models
if (is_dir($basePath.'/app/Models')) {
    preloadDirectory($basePath.'/app/Models');
}

// Preload all domain models (Hexagonal Architecture)
if (is_dir($basePath.'/modules')) {
    $domainPaths = [
        '/modules/Campaign/Domain/Model',
        '/modules/Donation/Domain/Model',
        '/modules/Employee/Domain/Model',
        '/modules/Report/Domain/Model',
    ];

    foreach ($domainPaths as $path) {
        if (is_dir($basePath.$path)) {
            preloadDirectory($basePath.$path);
        }
    }
}

// Preload HTTP controllers
if (is_dir($basePath.'/app/Http/Controllers')) {
    preloadDirectory($basePath.'/app/Http/Controllers');
}

// Preload middleware
if (is_dir($basePath.'/app/Http/Middleware')) {
    preloadDirectory($basePath.'/app/Http/Middleware');
}

// Preload form requests
if (is_dir($basePath.'/app/Http/Requests')) {
    preloadDirectory($basePath.'/app/Http/Requests');
}

// Preload service providers
if (is_dir($basePath.'/app/Providers')) {
    preloadDirectory($basePath.'/app/Providers');
}

// ================================================================
// Preload Third-Party Packages
// ================================================================

// Filament Admin Panel
$filamentFiles = [
    '/vendor/filament/filament/src/Panel.php',
    '/vendor/filament/filament/src/Resources/Resource.php',
    '/vendor/filament/filament/src/Pages/Page.php',
];

foreach ($filamentFiles as $file) {
    $fullPath = $basePath.$file;
    if (file_exists($fullPath)) {
        preloadFile($fullPath);
    }
}

// Livewire
if (is_dir($basePath.'/vendor/livewire/livewire/src')) {
    $livewireCore = [
        '/vendor/livewire/livewire/src/Component.php',
        '/vendor/livewire/livewire/src/LivewireManager.php',
    ];

    foreach ($livewireCore as $file) {
        $fullPath = $basePath.$file;
        if (file_exists($fullPath)) {
            preloadFile($fullPath);
        }
    }
}

// ================================================================
// Statistics
// ================================================================

$stats = opcache_get_status(false);
if ($stats) {
    error_log(sprintf(
        '[Preload] Completed: %d files preloaded, using %.2f MB of memory',
        $stats['opcache_statistics']['num_cached_scripts'] ?? 0,
        ($stats['memory_usage']['used_memory'] ?? 0) / 1024 / 1024
    ));
}