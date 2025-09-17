<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Infrastructure\Laravel\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\CacheWarming\Application\Query\GetCacheStatusQuery;
use Modules\CacheWarming\Application\Query\GetCacheStatusQueryHandler;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

final readonly class CacheWarmingMiddleware
{
    private function getSkipCookieName(): string
    {
        return config('cache-warming.skip_cookie', 'skip_cache_warming');
    }

    private function getCacheWarmingRoute(): string
    {
        return config('cache-warming.route', '/cache-warming');
    }

    /**
     * @return array<string>
     */
    private function getSkipRoutes(): array
    {
        return config('cache-warming.skip_routes', [
            '/cache-warming',
            '/api/cache-warming',
            '/health',
            '/ping',
        ]);
    }

    public function __construct(
        private GetCacheStatusQueryHandler $queryHandler
    ) {}

    public function handle(Request $request, Closure $next): BaseResponse
    {
        try {
            // Check if cache warming is enabled
            if (! config('cache-warming.enabled', false)) {
                return $next($request);
            }

            // Skip middleware for specific routes
            if ($this->shouldSkipMiddleware($request)) {
                return $next($request);
            }

            // Skip if user has the bypass cookie
            if ($this->hasSkipCookie($request)) {
                Log::info('Skip cookie detected, bypassing cache warming');

                return $next($request);
            }

            // Check cache status
            $query = new GetCacheStatusQuery;
            $cacheStatus = $this->queryHandler->handle($query);

            // If cache is empty or unhealthy, redirect to cache warming page
            if ($this->shouldRedirectToCacheWarming($cacheStatus->toArray())) {
                Log::info('Redirecting to cache warming', [
                    'warmed_count' => $cacheStatus->getWarmedCount(),
                    'total_keys' => $cacheStatus->getTotalKeys(),
                ]);

                return $this->redirectToCacheWarming($request);
            }

            return $next($request);

        } catch (Exception $e) {
            Log::error('Cache warming middleware error', [
                'error' => $e->getMessage(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);

            // On error, continue to prevent blocking the application
            return $next($request);
        }
    }

    private function shouldSkipMiddleware(Request $request): bool
    {
        $path = $request->getPathInfo();

        foreach ($this->getSkipRoutes() as $route) {
            if (str_starts_with($path, (string) $route)) {
                return true;
            }
        }

        // Skip for admin routes
        if (str_starts_with($path, '/admin')) {
            return true;
        }

        // Skip for livewire routes (used by Filament admin)
        if (str_starts_with($path, '/livewire')) {
            return true;
        }

        // Skip for authentication routes (including localized routes)
        if (str_starts_with($path, '/login') ||
            str_starts_with($path, '/logout') ||
            str_starts_with($path, '/register') ||
            preg_match('#^/[a-z]{2}/login#', $path) ||
            preg_match('#^/[a-z]{2}/logout#', $path) ||
            preg_match('#^/[a-z]{2}/register#', $path)) {
            return true;
        }

        // Skip for webhook routes
        if (str_starts_with($path, '/webhooks')) {
            return true;
        }

        // Skip for API routes (except cache warming API)
        if (str_starts_with($path, '/api/') && ! str_starts_with($path, '/api/cache-warming')) {
            return true;
        }

        // Skip for AJAX requests
        return $request->ajax();
    }

    private function hasSkipCookie(Request $request): bool
    {
        $cookieName = $this->getSkipCookieName();

        // Check Laravel's encrypted cookie first
        $cookieValue = $request->cookie($cookieName);

        // If not found, check raw cookie (set by JavaScript)
        if ($cookieValue === null) {
            $cookieValue = $_COOKIE[$cookieName] ?? null;
        }

        Log::debug('Checking skip cookie', [
            'cookie_name' => $cookieName,
            'cookie_value' => $cookieValue,
            'all_cookies' => array_keys($_COOKIE),
        ]);

        if ($cookieValue === null) {
            return false;
        }

        // Check for simple boolean values first
        if ($cookieValue === '1' || $cookieValue === 'true') {
            Log::debug('Skip cookie validated as true');

            return true;
        }

        // Cookie can be set with a timestamp (for values > 1000 to avoid conflict with "1")
        if (is_numeric($cookieValue) && (int) $cookieValue > 1000) {
            $expiresAt = (int) $cookieValue;
            $valid = time() < $expiresAt;
            Log::debug('Skip cookie timestamp check', ['expires_at' => $expiresAt, 'valid' => $valid]);

            return $valid;
        }

        // Fallback boolean check
        $result = filter_var($cookieValue, FILTER_VALIDATE_BOOLEAN);
        Log::debug('Skip cookie validation result', ['result' => $result]);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $cacheStatus
     */
    private function shouldRedirectToCacheWarming(array $cacheStatus): bool
    {
        // If no keys are warmed
        if (($cacheStatus['warmed_count'] ?? 0) === 0) {
            return true;
        }

        // If more than configured threshold of cache keys are cold (missing)
        $totalKeys = $cacheStatus['total_keys'] ?? 0;
        $coldCount = $cacheStatus['cold_count'] ?? 0;
        $coldThreshold = config('cache-warming.cold_threshold', 0.7);

        if ($totalKeys > 0 && ($coldCount / $totalKeys) > $coldThreshold) {
            return true;
        }

        // If critical widget cache keys are missing
        $warmedKeys = $cacheStatus['warmed_keys'] ?? [];
        $criticalKeys = [
            'campaign_performance',
            'average_donation',
        ];

        foreach ($criticalKeys as $key) {
            if (! in_array($key, $warmedKeys, true)) {
                return true;
            }
        }

        return false;
    }

    private function redirectToCacheWarming(Request $request): BaseResponse
    {
        // For AJAX requests, return JSON response
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'cache_warming_required',
                'message' => 'Cache is being warmed. Please wait.',
                'redirect_url' => $this->getCacheWarmingRoute(),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        // For regular requests, redirect to cache warming page with return URL
        $returnUrl = $request->fullUrl();

        return redirect($this->getCacheWarmingRoute() . '?returnUrl=' . urlencode($returnUrl))
            ->with('message', 'Cache is being warmed for optimal performance. Please wait.');
    }
}
