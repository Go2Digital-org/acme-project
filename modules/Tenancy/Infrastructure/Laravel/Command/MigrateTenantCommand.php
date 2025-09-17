<?php

declare(strict_types=1);

namespace Modules\Tenancy\Infrastructure\Laravel\Command;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Modules\Organization\Domain\Model\Organization;

/**
 * Migrate Tenant Command.
 *
 * Run migrations for a specific tenant or all tenants.
 */
class MigrateTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate 
        {tenant? : Tenant ID or subdomain}
        {--all : Run for all active tenants}
        {--fresh : Wipe the database first}
        {--seed : Seed the database after migration}
        {--force : Force the operation in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations for tenant(s)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->migrateAllTenants();
        }

        $tenantIdentifier = $this->argument('tenant');

        if (! $tenantIdentifier) {
            $this->error('Please specify a tenant ID/subdomain or use --all flag.');

            return Command::FAILURE;
        }

        $tenant = $this->findTenant($tenantIdentifier);

        if (! $tenant instanceof Organization) {
            $this->error("Tenant not found: {$tenantIdentifier}");

            return Command::FAILURE;
        }

        return $this->migrateTenant($tenant);
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
     * Migrate a single tenant.
     */
    protected function migrateTenant(Organization $tenant): int
    {
        $this->info("Migrating tenant: {$tenant->getName()} ({$tenant->subdomain})");

        if (! $tenant->isActive()) {
            $this->warn("Tenant is not active. Status: {$tenant->provisioning_status}");

            if (! $this->confirm('Do you want to continue?')) {
                return Command::FAILURE;
            }
        }

        try {
            $tenant->run(function (): void {
                $command = $this->option('fresh') ? 'migrate:fresh' : 'migrate';

                // Get all tenant migration paths
                $migrationPaths = $this->getTenantMigrationPaths();

                foreach ($migrationPaths as $path) {
                    if (is_dir($path)) {
                        $this->info("  Running migrations from: {$path}");

                        Artisan::call($command, [
                            '--path' => str_replace(base_path() . '/', '', $path),
                            '--force' => $this->option('force'),
                        ]);

                        $this->line(Artisan::output());
                    }
                }

                if ($this->option('seed')) {
                    $this->info('  Seeding database...');
                    Artisan::call('db:seed', [
                        '--class' => 'Modules\\Shared\\Infrastructure\\Laravel\\Seeder\\TenantDatabaseSeeder',
                        '--force' => $this->option('force'),
                    ]);
                }
            });

            $this->info('✅ Tenant migrated successfully!');

        } catch (Exception $e) {
            $this->error("Migration failed: {$e->getMessage()}");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Migrate all active tenants.
     */
    protected function migrateAllTenants(): int
    {
        $tenants = Organization::where('provisioning_status', 'active')->get();

        if ($tenants->isEmpty()) {
            $this->info('No active tenants found.');

            return Command::SUCCESS;
        }

        $this->info("Found {$tenants->count()} active tenant(s) to migrate.");

        if (! $this->option('force') && ! $this->confirm('Do you want to continue?')) {
            return Command::FAILURE;
        }

        $successful = 0;
        $failed = 0;

        $this->withProgressBar($tenants, function (Organization $tenant) use (&$successful, &$failed): void {
            try {
                $result = $this->migrateTenant($tenant);

                if ($result === Command::SUCCESS) {
                    $successful++;
                } else {
                    $failed++;
                }
            } catch (Exception $e) {
                $failed++;
                $this->error("\nFailed to migrate {$tenant->subdomain}: {$e->getMessage()}");
            }
        });

        $this->newLine(2);
        $this->info('Migration complete!');
        $this->info("  Successful: {$successful}");

        if ($failed > 0) {
            $this->error("  Failed: {$failed}");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Get all tenant migration paths.
     *
     * @return array<string>
     */
    protected function getTenantMigrationPaths(): array
    {
        return [
            base_path('modules/User/Infrastructure/Laravel/Migration/Tenant'),
            base_path('modules/Campaign/Infrastructure/Laravel/Migration/Tenant'),
            base_path('modules/Donation/Infrastructure/Laravel/Migration/Tenant'),
            base_path('modules/Notification/Infrastructure/Laravel/Migration/Tenant'),
            base_path('modules/Category/Infrastructure/Laravel/Migration/Tenant'),
            base_path('modules/Currency/Infrastructure/Laravel/Migration/Tenant'),
            base_path('modules/Search/Infrastructure/Laravel/Migration/Tenant'),
            base_path('database/migrations/tenant'),
        ];
    }
}
