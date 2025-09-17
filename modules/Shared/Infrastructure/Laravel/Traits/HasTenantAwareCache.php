<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Traits;

trait HasTenantAwareCache
{
    /**
     * Format cache key with tenant-aware prefix following DRY principle
     */
    protected static function formatCacheKey(string $baseKey): string
    {
        $cachePrefix = 'acme_cache:';

        // Add tenant context to cache key if we're in a tenant context
        if (function_exists('tenant') && tenant()) {
            $tenantKey = tenant()->getTenantKey();

            return $cachePrefix . "tenant_{$tenantKey}:" . $baseKey;
        }

        // Central/global cache key (non-tenant context)
        return $cachePrefix . 'central:' . $baseKey;
    }
}
