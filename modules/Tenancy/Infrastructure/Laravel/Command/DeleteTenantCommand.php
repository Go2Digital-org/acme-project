<?php

declare(strict_types=1);

namespace Modules\Tenancy\Infrastructure\Laravel\Command;

use Exception;
use Illuminate\Console\Command;
use Modules\Organization\Domain\Model\Organization;
use Modules\Tenancy\Infrastructure\Database\TenantDatabaseManager;
use Modules\Tenancy\Infrastructure\Meilisearch\TenantSearchIndexManager;

/**
 * Delete Tenant Command.
 *
 * Delete a tenant and optionally its database.
 */
class DeleteTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:delete 
        {tenant : Tenant ID or subdomain}
        {--with-database : Also drop the tenant database}
        {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a tenant organization';

    /**
     * Execute the console command.
     */
    public function handle(
        TenantDatabaseManager $dbManager,
        TenantSearchIndexManager $searchManager
    ): int {
        $tenantIdentifier = $this->argument('tenant');
        $tenant = $this->findTenant($tenantIdentifier);

        if (! $tenant instanceof Organization) {
            $this->error("Tenant not found: {$tenantIdentifier}");

            return Command::FAILURE;
        }

        $this->warn('⚠️  WARNING: This will delete the tenant organization!');
        $this->newLine();

        $this->table(
            ['Property', 'Value'],
            [
                ['ID', $tenant->id],
                ['Name', $tenant->getName()],
                ['Subdomain', $tenant->subdomain ?: 'N/A'],
                ['Database', $tenant->database ?: 'N/A'],
                ['Status', $tenant->provisioning_status],
                ['Created', $tenant->created_at?->format('Y-m-d H:i')],
            ]
        );

        if ($this->option('with-database')) {
            $this->warn('The tenant database will also be PERMANENTLY DELETED!');
        }

        if (! $this->option('force')) {
            $confirm = $this->confirm('Are you absolutely sure you want to delete this tenant?');

            if (! $confirm) {
                $this->info('Operation cancelled.');

                return Command::FAILURE;
            }

            // Double confirmation for database deletion
            if ($this->option('with-database')) {
                $confirmInput = $this->ask('Type "DELETE" to confirm database deletion:');

                if ($confirmInput !== 'DELETE') {
                    $this->info('Operation cancelled.');

                    return Command::FAILURE;
                }
            }
        }

        try {
            $this->info('Deleting tenant...');

            // Delete search indexes
            $this->info('  Removing search indexes...');
            $searchManager->deleteTenantIndexes($tenant);

            // Drop database if requested
            if ($this->option('with-database') && $tenant->database) {
                $this->info('  Dropping database...');
                $dbManager->dropDatabase($tenant);
            }

            // Delete domains
            $this->info('  Removing domains...');
            $tenant->domains()->delete();

            // Delete the organization
            $this->info('  Deleting organization record...');
            $tenant->delete();

            $this->info('✅ Tenant deleted successfully!');

        } catch (Exception $e) {
            $this->error('Failed to delete tenant: ' . $e->getMessage());

            return Command::FAILURE;
        }

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
}
