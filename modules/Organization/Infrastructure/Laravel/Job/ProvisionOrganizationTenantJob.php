<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Job;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Modules\Category\Infrastructure\Laravel\Seeder\DefaultCategoriesSeeder;
use Modules\Currency\Infrastructure\Laravel\Seeder\DefaultCurrenciesSeeder;
use Modules\Donation\Infrastructure\Laravel\Seeder\DefaultPaymentGatewaySeeder;
use Modules\Organization\Domain\Model\Organization;
use Modules\Shared\Infrastructure\Laravel\Seeder\DefaultPagesSeeder;
use Modules\Shared\Infrastructure\Laravel\Seeder\DefaultSocialMediaSeeder;
use Modules\Shared\Infrastructure\Laravel\Seeder\TenantRolesAndPermissionsSeeder;
use Modules\Tenancy\Infrastructure\Database\TenantDatabaseManager;
use Modules\Tenancy\Infrastructure\Meilisearch\TenantSearchIndexManager;
use Modules\User\Infrastructure\Laravel\Models\User;
use Modules\User\Infrastructure\Laravel\Seeder\TenantShieldSeeder;
use Spatie\Permission\Models\Role;

/**
 * Provision Organization Tenant Job.
 *
 * Asynchronously provisions a complete tenant environment including:
 * - Database creation
 * - Migration execution
 * - Super admin user creation
 * - Meilisearch index setup
 * - Domain configuration
 */
class ProvisionOrganizationTenantJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600;

    /**
     * Create a new job instance.
     *
     * @param  array{name: string, email: string, password: string}  $adminData
     */
    public function __construct(
        public Organization $organization,
        /**
         * Admin data structure.
         *
         * @var array{name: string, email: string, password: string}
         */
        private array $adminData
    ) {
        $this->onQueue('tenant-provisioning');
    }

    /**
     * Execute the job.
     */
    public function handle(
        TenantDatabaseManager $dbManager,
        TenantSearchIndexManager $searchManager
    ): void {
        // Refresh the organization to get latest data from database
        $this->organization->refresh();

        Log::info('Starting tenant provisioning', [
            'organization_id' => $this->organization->id,
            'subdomain' => $this->organization->subdomain,
            'database' => $this->organization->getAttribute('database'),
        ]);

        try {
            // Mark as provisioning
            $this->organization->startProvisioning();

            // Step 1: Create tenant database
            $this->createDatabase($dbManager);

            // Step 2: Create domain record
            $this->createDomain();

            // Step 3: Run tenant migrations
            $this->runMigrations();

            // Step 4: Seed initial data (roles, permissions, payment gateways, currencies, categories, pages, social media)
            // This must be done BEFORE creating the super admin so roles exist
            $this->seedInitialData();

            // Step 5: Create super admin user (after roles are seeded)
            $this->createSuperAdmin();

            // Step 6: Setup Meilisearch indexes
            $this->setupSearchIndexes($searchManager);

            // Mark as successfully provisioned
            $this->organization->markAsProvisioned();

            Log::info('Tenant provisioning completed successfully', [
                'organization_id' => $this->organization->id,
                'subdomain' => $this->organization->subdomain,
            ]);

        } catch (Exception $e) {
            Log::error('Tenant provisioning failed', [
                'organization_id' => $this->organization->id,
                'subdomain' => $this->organization->subdomain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->organization->markAsFailed($e->getMessage());

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Create tenant database.
     */
    private function createDatabase(TenantDatabaseManager $dbManager): void
    {
        Log::info('Creating tenant database', [
            'organization_id' => $this->organization->id,
            'database' => $this->organization->database,
        ]);

        $dbManager->createDatabase($this->organization);

        // Generate secure database credentials using subdomain or ID
        $identifier = $this->organization->subdomain ?: 'org_' . $this->organization->id;
        $dbUser = 'tenant_' . str_replace('-', '_', substr($identifier, 0, 16));
        $dbPassword = bin2hex(random_bytes(16));

        // Get database name
        $databaseName = $this->organization->getAttribute('database') ?: $this->organization->getDatabaseName();

        // Create database user with limited privileges
        DB::statement("CREATE USER IF NOT EXISTS '{$dbUser}'@'%' IDENTIFIED BY '{$dbPassword}'");
        DB::statement("GRANT ALL PRIVILEGES ON `{$databaseName}`.* TO '{$dbUser}'@'%'");
        DB::statement('FLUSH PRIVILEGES');

        // Store credentials
        $this->organization->update([
            'database_user' => $dbUser,
            'database_password' => encrypt($dbPassword),
        ]);
    }

    /**
     * Create domain record for the tenant.
     */
    private function createDomain(): void
    {
        // Check if domain already exists (might have been created in observer)
        $domainName = $this->organization->subdomain . '.' . config('tenancy.central_domains.0');

        if ($this->organization->domains()->where('domain', $domainName)->exists()) {
            Log::info('Tenant domain already exists', [
                'organization_id' => $this->organization->id,
                'subdomain' => $this->organization->subdomain,
                'domain' => $domainName,
            ]);

            return;
        }

        Log::info('Creating tenant domain', [
            'organization_id' => $this->organization->id,
            'subdomain' => $this->organization->subdomain,
        ]);

        $this->organization->domains()->create([
            'domain' => $domainName,
        ]);
    }

    /**
     * Run tenant-specific migrations.
     */
    private function runMigrations(): void
    {
        Log::info('Running tenant migrations', [
            'organization_id' => $this->organization->id,
        ]);

        $this->organization->run(function (): void {
            // Run migrations from the modules migration path
            $migrationPath = 'modules/Shared/Infrastructure/Laravel/Migration';

            Log::info('Running migrations from path', ['path' => $migrationPath]);

            // Run migrations on the tenant database connection
            // The tenant context should already be initialized, but we specify the database for clarity
            Artisan::call('migrate', [
                '--path' => $migrationPath,
                '--database' => 'tenant',
                '--force' => true,
            ]);

            Log::info('Migration output', ['output' => Artisan::output()]);
        });
    }

    /**
     * Create super admin user for the tenant.
     */
    private function createSuperAdmin(): void
    {
        Log::info('Creating super admin user', [
            'organization_id' => $this->organization->id,
            'admin_email' => $this->adminData['email'],
        ]);

        $this->organization->run(function (): void {
            // Ensure we have a password, generate one if missing
            $password = $this->adminData['password'] ?? bin2hex(random_bytes(8));

            // Check if ANY admin users already exist in the tenant
            try {
                $existingAdmins = User::where('role', 'super_admin')->count();

                if ($existingAdmins > 0) {
                    Log::info('Admin user(s) already exist for tenant, skipping creation', [
                        'organization_id' => $this->organization->id,
                        'existing_admin_count' => $existingAdmins,
                    ]);

                    return;
                }
            } catch (Exception $e) {
                Log::warning('Error checking for existing admin users, will attempt creation', [
                    'organization_id' => $this->organization->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Determine the email to use for the new admin
            $email = $this->adminData['email'] ?? 'admin@' . $this->organization->subdomain . '.test';

            /** @var User|null $admin */
            $admin = null;
            try {
                $admin = User::create([
                    'name' => $this->adminData['name'] ?? 'Admin',
                    'email' => $email,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'status' => 'active',
                    'role' => 'super_admin',
                ]);
            } catch (Exception $e) {
                // If creation fails due to duplicate email, try to find existing user
                if (! (str_contains($e->getMessage(), 'Duplicate entry') && str_contains($e->getMessage(), 'email_unique'))) {
                    // Re-throw non-duplicate errors
                    throw $e;
                }

                $admin = User::where('email', $email)->first();

                if (! $admin) {
                    Log::error('Failed to create or find admin user', [
                        'organization_id' => $this->organization->id,
                        'email' => $email,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }

                Log::info('Admin user already existed during creation, using existing user', [
                    'organization_id' => $this->organization->id,
                    'email' => $email,
                    'existing_user_id' => $admin->id,
                ]);
            }

            // Ensure we have a valid admin user
            if (! $admin) {
                throw new Exception('Failed to create or retrieve admin user');
            }

            // Assign super_admin role via Spatie permissions
            // The role should already exist from TenantRolesAndPermissionsSeeder
            if (class_exists(Role::class)) {
                $role = Role::where('name', 'super_admin')
                    ->where('guard_name', 'web')
                    ->first();

                if (! $role) {
                    Log::warning('super_admin role not found - seeder may not have run', [
                        'user_id' => $admin->id,
                    ]);

                    return;
                }

                $admin->assignRole($role);
                Log::info('Assigned Spatie super_admin role to user', [
                    'user_id' => $admin->id,
                    'role_id' => $role->id,
                ]);
            }

            // Store admin reference in organization
            $this->organization->setAdminData([
                'id' => $admin->id,
                'email' => $admin->email,
                'name' => $admin->name,
            ]);

            Log::info('Super admin created', [
                'organization_id' => $this->organization->id,
                'admin_id' => $admin->id,
            ]);
        });
    }

    /**
     * Setup Meilisearch indexes for the tenant.
     */
    private function setupSearchIndexes(TenantSearchIndexManager $searchManager): void
    {
        Log::info('Setting up search indexes', [
            'organization_id' => $this->organization->id,
        ]);

        $searchManager->createTenantIndexes($this->organization);
    }

    /**
     * Seed initial data for the tenant.
     */
    private function seedInitialData(): void
    {
        Log::info('Seeding initial tenant data', [
            'organization_id' => $this->organization->id,
        ]);

        $this->organization->run(function (): void {
            // Seed default roles and permissions
            Artisan::call('db:seed', [
                '--class' => TenantRolesAndPermissionsSeeder::class,
                '--force' => true,
            ]);

            // Seed Filament Shield permissions for admin panel access
            if (class_exists(TenantShieldSeeder::class)) {
                Artisan::call('db:seed', [
                    '--class' => TenantShieldSeeder::class,
                    '--force' => true,
                ]);
                Log::info('Tenant Shield permissions seeded', [
                    'organization_id' => $this->organization->id,
                ]);
            }

            // Seed default payment gateways (must be before other financial data)
            if (class_exists(DefaultPaymentGatewaySeeder::class)) {
                Artisan::call('db:seed', [
                    '--class' => DefaultPaymentGatewaySeeder::class,
                    '--force' => true,
                ]);
            }

            // Seed default categories if they exist
            if (class_exists(DefaultCategoriesSeeder::class)) {
                Artisan::call('db:seed', [
                    '--class' => DefaultCategoriesSeeder::class,
                    '--force' => true,
                ]);
            }

            // Seed default currencies
            if (class_exists(DefaultCurrenciesSeeder::class)) {
                Artisan::call('db:seed', [
                    '--class' => DefaultCurrenciesSeeder::class,
                    '--force' => true,
                ]);
            }

            // Seed default pages
            if (class_exists(DefaultPagesSeeder::class)) {
                Artisan::call('db:seed', [
                    '--class' => DefaultPagesSeeder::class,
                    '--force' => true,
                ]);
            }

            // Seed default social media links
            if (class_exists(DefaultSocialMediaSeeder::class)) {
                Artisan::call('db:seed', [
                    '--class' => DefaultSocialMediaSeeder::class,
                    '--force' => true,
                ]);
            }
        });
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Tenant provisioning job failed permanently', [
            'organization_id' => $this->organization->id,
            'subdomain' => $this->organization->subdomain,
            'error' => $exception->getMessage(),
        ]);

        $this->organization->markAsFailed(
            'Job failed after ' . $this->tries . ' attempts: ' . $exception->getMessage()
        );
    }

    /**
     * Determine the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'tenant-provisioning',
            'organization:' . $this->organization->id,
            'subdomain:' . $this->organization->subdomain,
        ];
    }
}
