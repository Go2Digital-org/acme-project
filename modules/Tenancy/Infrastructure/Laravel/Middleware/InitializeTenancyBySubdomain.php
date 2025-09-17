<?php

declare(strict_types=1);

namespace Modules\Tenancy\Infrastructure\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Log;
use Modules\Organization\Domain\Model\Organization;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;
use Stancl\Tenancy\Middleware\IdentificationMiddleware;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Initialize Tenancy By Subdomain Middleware.
 *
 * Custom middleware that initializes tenancy based on subdomain.
 * Extracts subdomain from the request and finds matching organization.
 */
class InitializeTenancyBySubdomain extends IdentificationMiddleware
{
    /**
     * The tenant parameter name.
     */
    public static string $tenantParameterName = 'tenant';

    /**
     * Handle an incoming request.
     *
     * @throws TenantCouldNotBeIdentifiedOnDomainException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);

        if (! $subdomain) {
            // No subdomain, continue without tenancy
            return $next($request);
        }

        try {
            /** @var Organization|null $tenant */
            $tenant = Organization::where('subdomain', $subdomain)->first();

            if (! $tenant) {
                // Try to find by domain
                $tenant = Organization::whereHas('domains', function ($query) use ($host): void {
                    $query->where('domain', $host);
                })->first();
            }

            if (! $tenant) {
                throw new TenantCouldNotBeIdentifiedOnDomainException($host);
            }

            // Check tenant status
            if (! $tenant->isActive()) {
                return $this->handleInactiveTenant($request, $tenant);
            }

            // Initialize tenancy
            /** @var Tenancy $tenancy */
            $tenancy = app(Tenancy::class);
            $tenancy->initialize($tenant);

            // Add tenant to request
            $request->merge([
                static::$tenantParameterName => $tenant,
                'subdomain' => $subdomain,
            ]);

        } catch (TenantCouldNotBeIdentifiedOnDomainException $e) {
            return $this->handleFailedIdentification($request, $subdomain, $e);
        }

        return $next($request);
    }

    /**
     * Extract subdomain from host.
     */
    protected function extractSubdomain(string $host): ?string
    {
        $centralDomains = config('tenancy.central_domains', []);

        foreach ($centralDomains as $centralDomain) {
            if ($host === $centralDomain) {
                return null; // This is a central domain, no subdomain
            }

            // Check if host ends with central domain
            $suffix = '.' . $centralDomain;
            if (str_ends_with($host, $suffix)) {
                // Extract subdomain
                $subdomain = substr($host, 0, -strlen($suffix));

                // Validate subdomain (no dots, valid characters)
                if (! str_contains($subdomain, '.') && preg_match('/^[a-z0-9-]+$/', $subdomain)) {
                    return $subdomain;
                }
            }
        }

        return null;
    }

    /**
     * Handle inactive tenant.
     */
    protected function handleInactiveTenant(Request $request, Organization $tenant): Response
    {
        // Check tenant status for specific message
        $message = match ($tenant->provisioning_status) {
            'pending' => 'This organization is being set up. Please try again later.',
            'provisioning' => 'This organization is being provisioned. Please try again in a few minutes.',
            'failed' => 'This organization setup failed. Please contact support.',
            'suspended' => 'This organization has been suspended.',
            default => 'This organization is currently inactive.',
        };

        return response()->view('errors.tenant-inactive', [
            'tenant' => $tenant,
            'message' => $message,
        ], 503);
    }

    /**
     * Handle failed tenant identification.
     */
    protected function handleFailedIdentification(
        Request $request,
        ?string $subdomain,
        TenantCouldNotBeIdentifiedOnDomainException $e
    ): Response {
        // Log the failed attempt
        Log::warning('Tenant identification failed', [
            'subdomain' => $subdomain,
            'host' => $request->getHost(),
            'ip' => $request->ip(),
        ]);

        // Redirect to central domain
        $centralDomain = config('tenancy.central_domains.0');

        if ($centralDomain) {
            return redirect()->to('https://' . $centralDomain)
                ->with('error', 'Organization "' . $subdomain . '" not found.');
        }

        return response()->view('errors.tenant-not-found', [
            'subdomain' => $subdomain,
            'domain' => method_exists($e, 'getDomain') ? $e->getDomain() : $request->getHost(),
        ], 404);
    }
}
