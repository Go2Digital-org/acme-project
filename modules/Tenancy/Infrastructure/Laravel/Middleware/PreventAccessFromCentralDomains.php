<?php

declare(strict_types=1);

namespace Modules\Tenancy\Infrastructure\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Prevent Access From Central Domains Middleware.
 *
 * Ensures that tenant routes cannot be accessed from central domains.
 * This prevents accessing tenant-specific resources from the main domain.
 */
class PreventAccessFromCentralDomains
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // Check if current domain is a central domain
        // Check if we're trying to access tenant routes
        if ($this->isCentralDomain($host) && $this->isAccessingTenantRoute($request)) {
            throw new NotFoundHttpException(
                'This resource is not available on the central domain.'
            );
        }

        // Also prevent access to central-only routes from tenant domains
        if ($this->isTenantDomain($host) && $this->isAccessingCentralRoute($request)) {
            // Redirect to central domain for admin access
            return $this->redirectToCentralDomain($request);
        }

        return $next($request);
    }

    /**
     * Check if domain is a central domain.
     */
    protected function isCentralDomain(string $domain): bool
    {
        $centralDomains = config('tenancy.central_domains', []);

        return in_array($domain, $centralDomains, true);
    }

    /**
     * Check if domain is a tenant domain.
     */
    protected function isTenantDomain(string $domain): bool
    {
        // If tenancy is initialized, it's a tenant domain
        /** @var Tenancy $tenancy */
        $tenancy = app(Tenancy::class);

        return $tenancy->initialized;
    }

    /**
     * Check if accessing tenant-specific routes.
     */
    protected function isAccessingTenantRoute(Request $request): bool
    {
        $request->path();

        // Define patterns for tenant-only routes
        $tenantPatterns = [
            'dashboard/*',
            'campaigns/*',
            'donations/*',
            'my-campaigns/*',
            'profile/*',
        ];

        foreach ($tenantPatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if accessing central-only routes.
     */
    protected function isAccessingCentralRoute(Request $request): bool
    {
        $request->path();

        // Define patterns for central-only routes
        $centralPatterns = [
            'admin',
            'admin/*',
            'organizations',
            'organizations/*',
            'system/*',
        ];

        foreach ($centralPatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redirect to central domain.
     */
    protected function redirectToCentralDomain(Request $request): Response
    {
        $centralDomain = config('tenancy.central_domains.0');

        if (! $centralDomain) {
            throw new NotFoundHttpException(
                'This resource is not available on tenant domains.'
            );
        }

        $url = $request->secure() ? 'https://' : 'http://';
        $url .= $centralDomain;
        $url .= '/' . $request->path();

        // Preserve query string
        if ($request->getQueryString()) {
            $url .= '?' . $request->getQueryString();
        }

        return redirect()->to($url)
            ->with('info', 'Please access the admin panel from the main domain.');
    }
}
