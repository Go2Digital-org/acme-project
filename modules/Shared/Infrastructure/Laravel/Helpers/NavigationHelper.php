<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Helpers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

final class NavigationHelper
{
    /**
     * Check if current route matches any of the given patterns.
     */
    /**
     * @param  array<string, mixed>  $patterns
     */
    public static function isCurrentRoute(string|array $patterns): bool
    {
        $patterns = is_array($patterns) ? $patterns : [$patterns];
        $currentRoute = Request::route()->getName();

        if (! $currentRoute) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $currentRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get active class for route patterns.
     */
    /**
     * @param  array<string, mixed>  $patterns
     */
    public static function getActiveRouteClass(string|array $patterns, string $activeClass = 'active', string $inactiveClass = ''): string
    {
        return self::isCurrentRoute($patterns) ? $activeClass : $inactiveClass;
    }
}
