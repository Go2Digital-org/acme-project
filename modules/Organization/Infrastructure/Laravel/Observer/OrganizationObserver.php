<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Observer;

use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Infrastructure\Laravel\Job\ProvisionOrganizationTenantJob;

/**
 * Organization Observer.
 *
 * Handles automatic tenant provisioning when an organization is created.
 * This observer fires the TenantCreated event which triggers the
 * Stancl\Tenancy automatic provisioning pipeline.
 */
class OrganizationObserver
{
    /**
     * Handle the Organization "creating" event.
     */
    public function creating(Organization $organization): void
    {
        Log::info('OrganizationObserver::creating triggered', [
            'subdomain' => $organization->subdomain ?? 'not set',
        ]);
    }

    /**
     * Handle the Organization "created" event.
     *
     * When an organization is created with a subdomain, automatically
     * provision it as a tenant by dispatching a job.
     */
    public function created(Organization $organization): void
    {
        Log::info('OrganizationObserver::created triggered', [
            'organization_id' => $organization->id,
            'subdomain' => $organization->subdomain,
        ]);

        // Only provision if organization has a subdomain (indicating it should be a tenant)
        if (! $organization->subdomain) {
            Log::info('Organization created without subdomain, skipping tenant provisioning', [
                'organization_id' => $organization->id,
                'organization_name' => $organization->getName(),
            ]);

            return;
        }

        // Auto-generate database name if not set
        $databaseName = $organization->getDatabaseName();
        if (! $organization->getAttribute('database')) {
            $databaseName = 'tenant_' . str_replace('-', '_', $organization->subdomain);
        }

        // Set initial provisioning status
        $organization->update([
            'provisioning_status' => 'provisioning',
            'database' => $databaseName,
        ]);

        Log::info('Dispatching tenant provisioning job', [
            'organization_id' => $organization->id,
            'subdomain' => $organization->subdomain,
        ]);

        try {
            // Create domain record for the tenant (needed before provisioning)
            $centralDomain = config('tenancy.central_domains')[0] ?? 'acme-corp-optimy.test';
            $organization->domains()->create([
                'domain' => $organization->subdomain . '.' . $centralDomain,
            ]);

            // Extract admin data from tenant_data or use defaults
            $adminData = $organization->tenant_data['admin'] ?? null;

            if (! $adminData) {
                Log::warning('No admin data found in tenant_data, using defaults', [
                    'organization_id' => $organization->id,
                ]);

                // Fallback to organization email and generate password
                $adminData = [
                    'name' => 'Admin',
                    'email' => $organization->email ?: 'admin@' . $organization->subdomain . '.local',
                    'password' => bin2hex(random_bytes(8)),
                ];
            }

            // Dispatch the provisioning job
            ProvisionOrganizationTenantJob::dispatch(
                $organization,
                $adminData
            );

            Log::info('Tenant provisioning job dispatched', [
                'organization_id' => $organization->id,
                'subdomain' => $organization->subdomain,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to dispatch tenant provisioning job', [
                'organization_id' => $organization->id,
                'subdomain' => $organization->subdomain,
                'error' => $e->getMessage(),
            ]);

            // Update status to failed
            $organization->update([
                'provisioning_status' => 'failed',
                'provisioning_error' => 'Failed to dispatch provisioning job: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Organization "updated" event.
     */
    public function updated(Organization $organization): void
    {
        // Could handle subdomain changes or re-provisioning here if needed
    }

    /**
     * Handle the Organization "deleted" event.
     */
    public function deleted(Organization $organization): void
    {
        // TenantDeleted event will be fired automatically by the package
        // which triggers DeleteDatabase job
    }
}
