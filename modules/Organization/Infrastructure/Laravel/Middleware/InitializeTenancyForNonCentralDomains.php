<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Log;
use Modules\Organization\Domain\Model\Organization;
use Stancl\Tenancy\Database\Models\Domain;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyForNonCentralDomains
{
    /**
     * Handle an incoming request.
     * Only initialize tenancy for non-central domains.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', []);

        // If this is a central domain, skip tenancy initialization
        if (in_array($host, $centralDomains)) {
            return $next($request);
        }

        // Skip tenancy initialization for certain routes that need central database access
        $excludedRoutes = [
            'admin.auth.token', // Token authentication route name
        ];

        if ($request->routeIs($excludedRoutes) || $request->is('*/auth/token')) {
            return $next($request);
        }

        // Extract subdomain from host
        $parts = explode('.', $host);
        if (count($parts) < 2) {
            abort(404, 'Invalid domain format');
        }

        $subdomain = $parts[0];

        // Find the organization by subdomain
        $organization = Organization::where('subdomain', $subdomain)->first();

        if (! $organization) {
            abort(404, 'Tenant not found for subdomain: ' . $subdomain);
        }

        // Make sure the organization is active
        if ($organization->provisioning_status !== 'active') {
            abort(503, 'Tenant is not active');
        }

        // Set the subdomain as the primary identifier to avoid domain lookup issues
        // The tenancy package expects a string ID, so we ensure subdomain is used
        if (! $organization->subdomain) {
            abort(500, 'Organization does not have a subdomain configured');
        }

        try {
            // Initialize tenancy with the organization
            // This will trigger the TenancyInitialized event and switch database context
            tenancy()->initialize($organization);
        } catch (Exception $e) {
            Log::error('Failed to initialize tenancy', [
                'subdomain' => $subdomain,
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500, 'Failed to initialize tenant context: ' . $e->getMessage());
        }

        return $next($request);
    }
}
