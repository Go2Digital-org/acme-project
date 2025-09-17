<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\User\Infrastructure\Laravel\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block access to admin routes unless user has super_admin role.
 * Returns 404 to prevent route disclosure.
 */
final class BlockAdminLoginMiddleware
{
    /**
     * Handle incoming request and block admin access for non-super-admins.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware in testing environment
        if (app()->environment('testing')) {
            return $next($request);
        }

        // Check if this is an admin route
        $path = $request->path();

        if (str_starts_with($path, 'admin')) {
            // Get authenticated user with proper typing
            /** @var User|null $user */
            $user = auth()->user();

            // Block all admin routes unless user has super_admin role
            if ($user === null || ! $user->hasRole('super_admin')) {
                // For any admin route, return immediate 404 to prevent redirects
                abort(404);
            }
        }

        return $next($request);
    }
}
