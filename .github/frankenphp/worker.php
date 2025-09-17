<?php

/**
 * FrankenPHP Worker Script for ACME Corp
 *
 * This script runs in worker mode for high-performance request handling.
 * Workers are long-lived processes that handle multiple requests.
 */

// Ensure we're running in FrankenPHP worker mode
if (! function_exists('frankenphp_handle_request')) {
    exit('This script must be run with FrankenPHP in worker mode');
}

// Bootstrap Laravel once per worker
require __DIR__.'/../../vendor/autoload.php';
$app = require_once __DIR__.'/../../bootstrap/app.php';

// Initialize the kernel once
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Worker statistics
$requestCount = 0;
$startTime = time();
$maxRequests = $_ENV['FRANKENPHP_MAX_REQUESTS'] ?? 1000; // Restart worker after N requests
$maxLifetime = $_ENV['FRANKENPHP_MAX_LIFETIME'] ?? 3600; // Restart worker after N seconds

// Handle requests in the worker loop
while ($request = frankenphp_handle_request()) {
    try {
        // Increment request counter
        $requestCount++;

        // Create Laravel request from FrankenPHP request
        $laravelRequest = Illuminate\Http\Request::capture();

        // Handle the request
        $response = $kernel->handle($laravelRequest);

        // Send the response
        $response->send();

        // Terminate the kernel (runs terminate middleware)
        $kernel->terminate($laravelRequest, $response);

        // Clean up request-specific variables
        unset($laravelRequest, $response);

        // Garbage collection every 50 requests
        if ($requestCount % 50 === 0) {
            gc_collect_cycles();
        }

        // Check if worker should restart
        $lifetime = time() - $startTime;
        if ($requestCount >= $maxRequests || $lifetime >= $maxLifetime) {
            error_log(sprintf(
                '[FrankenPHP Worker] Gracefully restarting after %d requests and %d seconds',
                $requestCount,
                $lifetime
            ));
            break; // Exit the loop, FrankenPHP will spawn a new worker
        }

    } catch (\Throwable $e) {
        // Log the error
        error_log('[FrankenPHP Worker Error] '.$e->getMessage());
        error_log('[FrankenPHP Worker Error] '.$e->getTraceAsString());

        // Send error response
        http_response_code(500);
        echo 'Internal Server Error';

        // In case of fatal errors, restart the worker
        if ($e instanceof \Error) {
            error_log('[FrankenPHP Worker] Fatal error encountered, restarting worker');
            break;
        }
    }
}

// Log worker shutdown
error_log(sprintf(
    '[FrankenPHP Worker] Shutting down after %d requests and %d seconds',
    $requestCount,
    time() - $startTime
));