<?php

declare(strict_types=1);

namespace Modules\Tenancy\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Infrastructure\Laravel\Job\ProvisionOrganizationTenantJob;

/**
 * Provision Tenant Command.
 *
 * Manually provision or re-provision a tenant.
 */
class ProvisionTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:provision 
        {tenant : Tenant ID or subdomain}
        {--retry : Retry failed provisioning}
        {--force : Force re-provisioning even if already active}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provision or re-provision a tenant';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantIdentifier = $this->argument('tenant');
        $tenant = $this->findTenant($tenantIdentifier);

        if (! $tenant instanceof Organization) {
            $this->error("Tenant not found: {$tenantIdentifier}");

            return Command::FAILURE;
        }

        $this->info("Tenant: {$tenant->getName()} ({$tenant->subdomain})");
        $this->info("Current status: {$tenant->provisioning_status}");

        // Check if already active
        if ($tenant->isActive() && ! $this->option('force')) {
            $this->warn('Tenant is already active.');

            if (! $this->confirm('Do you want to re-provision?')) {
                return Command::FAILURE;
            }
        }

        // Check if failed and retrying
        if ($tenant->hasFailed() && $this->option('retry')) {
            $this->info('Retrying failed provisioning...');

            // Reset status to pending
            $tenant->update([
                'provisioning_status' => 'pending',
                'provisioning_error' => null,
            ]);
        }

        // Get admin data
        $adminData = $this->getAdminData($tenant);

        if (! $adminData) {
            $this->error('Admin data not found. Please provide admin details:');

            $adminData = [
                'name' => $this->ask('Admin name') ?: 'Admin',
                'email' => $this->ask('Admin email') ?: 'admin@example.com',
                'password' => $this->secret('Admin password') ?: 'password',
            ];

            // Store admin data
            $tenant->setAdminData($adminData);
        }

        // Ensure we have the required structure
        $adminData = [
            'name' => (string) ($adminData['name'] ?? 'Admin'),
            'email' => (string) ($adminData['email'] ?? 'admin@example.com'),
            'password' => (string) ($adminData['password'] ?? 'password'),
        ];

        // Dispatch provisioning job
        $this->info('Dispatching provisioning job...');

        ProvisionOrganizationTenantJob::dispatch($tenant, $adminData)
            ->onQueue('tenant-provisioning');

        $this->info('✅ Provisioning job dispatched!');
        $this->info('Monitor progress with: php artisan tenant:status ' . $tenant->id);

        return Command::SUCCESS;
    }

    /**
     * Find tenant by ID or subdomain.
     */
    protected function findTenant(string $identifier): ?Organization
    {
        // Try by ID first
        $tenant = Organization::find($identifier);

        if (! $tenant) {
            // Try by subdomain
            return Organization::where('subdomain', $identifier)->first();
        }

        return $tenant;
    }

    /**
     * Get admin data for tenant.
     *
     * @return array<string, mixed>|null
     */
    protected function getAdminData(Organization $tenant): ?array
    {
        $adminData = $tenant->getAdminData();

        if ($adminData && isset($adminData['email'])) {
            // We have email but need to reset password
            if (! isset($adminData['password'])) {
                $this->warn('Admin exists but password needs to be reset.');
                $adminData['password'] = $this->secret('New admin password');
            }

            return $adminData;
        }

        return null;
    }
}
