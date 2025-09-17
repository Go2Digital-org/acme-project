<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiPerformanceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Pass through - performance monitoring can be added later
        return $next($request);
    }
}
