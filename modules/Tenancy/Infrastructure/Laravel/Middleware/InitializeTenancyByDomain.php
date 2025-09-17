<?php

declare(strict_types=1);

namespace Modules\Tenancy\Infrastructure\Laravel\Middleware;

use Closure;
use DB;
use Illuminate\Http\Request;
use Log;
use Modules\Organization\Domain\Model\Organization;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;
use Stancl\Tenancy\Middleware\IdentificationMiddleware;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Initialize Tenancy By Domain Middleware.
 *
 * Custom middleware that initializes tenancy based on the full domain.
 * Extends Stancl's base identification middleware.
 */
class InitializeTenancyByDomain extends IdentificationMiddleware
{
    /**
     * The tenant resolver.
     */
    public static string $tenantParameterName = 'tenant';

    /**
     * Handle an incoming request.
     *
     * @throws TenantCouldNotBeIdentifiedOnDomainException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $domain = $request->getHost();

        // Check if this is a central domain
        if ($this->isCentralDomain($domain)) {
            // Ensure we're using the main database for central domains
            DB::setDefaultConnection('mysql');
            Log::info('Central domain accessed', [
                'domain' => $domain,
                'database' => DB::connection()->getDatabaseName(),
            ]);

            return $next($request);
        }

        // Try to resolve tenant by domain
        try {
            /** @var Organization|null $tenant */
            $tenant = Organization::whereHas('domains', function ($query) use ($domain): void {
                $query->where('domain', $domain);
            })->first();

            if (! $tenant) {
                throw new TenantCouldNotBeIdentifiedOnDomainException($domain);
            }

            // Only initialize if tenant is active
            if (! $tenant->isActive()) {
                return $this->handleInactiveTenant($request, $tenant);
            }

            // Initialize tenancy
            /** @var Tenancy $tenancy */
            $tenancy = app(Tenancy::class);
            $tenancy->initialize($tenant);

            // Switch to tenant database connection
            $tenantDb = $tenant->database ?: 'tenant_' . $tenant->subdomain;
            config(['database.connections.tenant.database' => $tenantDb]);
            DB::purge('tenant');
            DB::reconnect('tenant');
            DB::setDefaultConnection('tenant');

            // Log for debugging
            Log::info('Tenant initialized', [
                'domain' => $domain,
                'tenant_id' => $tenant->id,
                'tenant_subdomain' => $tenant->subdomain,
                'database' => $tenantDb,
                'current_db' => DB::connection()->getDatabaseName(),
            ]);

            // Add tenant to request for later use
            $request->merge([
                static::$tenantParameterName => $tenant,
            ]);

        } catch (TenantCouldNotBeIdentifiedOnDomainException $e) {
            return $this->handleFailedIdentification($request, $e);
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
     * Handle inactive tenant.
     */
    protected function handleInactiveTenant(Request $request, Organization $tenant): Response
    {
        // Return a JSON response for inactive tenant
        return response()->json([
            'error' => 'This organization is currently inactive.',
            'tenant_id' => $tenant->id,
            'status' => 'inactive',
        ], 503);
    }

    /**
     * Handle failed tenant identification.
     */
    protected function handleFailedIdentification(Request $request, TenantCouldNotBeIdentifiedOnDomainException $e): Response
    {
        // Return JSON error for unidentified tenant
        return response()->json([
            'error' => 'Tenant could not be identified on this domain.',
            'domain' => $request->getHost(),
            'message' => 'This domain is not associated with any organization.',
        ], 404);
    }
}
