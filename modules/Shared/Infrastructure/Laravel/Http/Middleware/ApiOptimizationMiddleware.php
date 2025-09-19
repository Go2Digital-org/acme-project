<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ApiOptimizationMiddleware
{
    /**
     * Handle an incoming request with API optimizations.
     *
     * @param  Closure(Request): SymfonyResponse  $next
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        // Start timing the request
        $startTime = microtime(true);

        // Add request ID for tracking
        $requestId = uniqid('req_', true);
        $request->headers->set('X-Request-ID', $requestId);

        // Check for conditional requests before processing
        if ($this->handleConditionalRequest()) {
            return response()->json([], 304, [
                'X-Request-ID' => $requestId,
                'X-Cache-Status' => 'not-modified',
            ]);
        }

        // Process the request
        $response = $next($request);

        // Add optimization headers to response
        $this->addOptimizationHeaders($response, $request, $startTime, $requestId);

        return $response;
    }

    /**
     * Handle conditional requests (If-None-Match, If-Modified-Since).
     */
    private function handleConditionalRequest(): bool
    {
        // This is a basic implementation - the actual ETag checking
        // would be done in individual controllers with specific data
        return false;
    }

    /**
     * Add optimization and performance headers to the response.
     */
    private function addOptimizationHeaders(SymfonyResponse $response, Request $request, float $startTime, string $requestId): void
    {
        if (! $response instanceof JsonResponse) {
            return;
        }

        $endTime = microtime(true);
        $processingTime = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds

        $headers = [
            'X-Request-ID' => $requestId,
            'X-Response-Time' => $processingTime . 'ms',
            'X-API-Version' => 'v1',
            'X-Rate-Limit-Limit' => $this->getRateLimit($request),
            'X-Rate-Limit-Remaining' => $this->getRemainingRequests($request),
            'X-Rate-Limit-Reset' => $this->getRateLimitReset(),
        ];

        // Add compression hint
        if ($this->shouldCompress($request)) {
            $headers['X-Compression-Hint'] = 'gzip';
        }

        // Add query optimization hints
        if ($request->has(['page', 'per_page', 'fields', 'include'])) {
            $headers['X-Query-Optimized'] = 'true';
        }

        foreach ($headers as $key => $value) {
            /** @phpstan-ignore-next-line notIdentical.alwaysTrue */
            $response->header($key, (string) $value);
        }
    }

    /**
     * Get rate limit for the current request.
     */
    private function getRateLimit(Request $request): int
    {
        // Base rate limit
        $baseLimit = 100;

        // Adjust based on endpoint
        if ($request->is('api/*/search*')) {
            return 60; // Lower limit for search endpoints
        }

        if ($request->is('api/*/dashboard*')) {
            return 30; // Lower limit for dashboard endpoints
        }

        return $baseLimit;
    }

    /**
     * Get remaining requests for rate limiting.
     */
    private function getRemainingRequests(Request $request): int
    {
        $userId = auth()->id() ?? $request->ip();
        $key = "rate_limit:{$userId}:" . now()->format('Y-m-d:H:i');

        $limit = $this->getRateLimit($request);
        $used = (int) Cache::get($key, 0);

        return max(0, $limit - $used);
    }

    /**
     * Get rate limit reset time.
     */
    private function getRateLimitReset(): int
    {
        return (int) now()->addMinute()->timestamp;
    }

    /**
     * Determine if response should be compressed.
     */
    private function shouldCompress(Request $request): bool
    {
        $acceptEncoding = $request->header('Accept-Encoding', '');

        return str_contains($acceptEncoding, 'gzip');
    }
}
