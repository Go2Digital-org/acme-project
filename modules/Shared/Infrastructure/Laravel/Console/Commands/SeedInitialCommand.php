<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Category\Infrastructure\Laravel\Seeder\DefaultCategoriesSeeder;
use Modules\Currency\Infrastructure\Laravel\Seeder\DefaultCurrenciesSeeder;
use Modules\Donation\Infrastructure\Laravel\Seeder\DefaultPaymentGatewaySeeder;
use Modules\Shared\Infrastructure\Laravel\Seeder\DefaultPagesSeeder;
use Modules\Shared\Infrastructure\Laravel\Seeder\DefaultSocialMediaSeeder;
use Modules\Shared\Infrastructure\Laravel\Seeder\TenantRolesAndPermissionsSeeder;
use Modules\User\Infrastructure\Laravel\Seeder\AdminUserSeeder;
use Modules\User\Infrastructure\Laravel\Seeder\TenantShieldSeeder;

/**
 * Initial seed command for essential platform data.
 *
 * This command provides lightweight seeding for CI/testing environments
 * and initial platform setup. It seeds only the essential data needed
 * for the platform to function, without demo data.
 *
 * Following hexagonal architecture, this command resides in the Shared
 * module's Infrastructure layer as it coordinates multiple domain seeders.
 */
final class SeedInitialCommand extends Command
{
    protected $signature = 'acme:seed-initial 
                            {--force : Force seeding in production}
                            {--clean : Clean database before seeding}';

    protected $description = 'Seed initial essential data for ACME Corp CSR Platform';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Running seeders in production requires --force flag');

            return Command::FAILURE;
        }

        $this->info('Starting initial data seeding for ACME Corp CSR Platform...');

        if ($this->option('clean')) {
            $this->cleanDatabase();
        }

        $this->seedEssentialData();

        $this->info('Initial data seeding completed successfully.');

        return Command::SUCCESS;
    }

    private function seedEssentialData(): void
    {
        $this->info('Seeding essential platform data...');

        // 1. Seed roles and permissions first (required for super admin)
        $this->callSeeder(TenantRolesAndPermissionsSeeder::class, 'Roles and permissions');

        // 2. Generate Filament Shield permissions for proper admin panel access
        $this->callSeeder(TenantShieldSeeder::class, 'Shield permissions');

        // 3. Seed payment gateways (before other financial data)
        $this->callSeeder(DefaultPaymentGatewaySeeder::class, 'Payment gateways');

        // 4. Seed default categories
        $this->callSeeder(DefaultCategoriesSeeder::class, 'Default categories');

        // 5. Seed default currencies
        $this->callSeeder(DefaultCurrenciesSeeder::class, 'Default currencies');

        // 6. Seed default pages
        $this->callSeeder(DefaultPagesSeeder::class, 'Default pages');

        // 7. Seed default social media links
        $this->callSeeder(DefaultSocialMediaSeeder::class, 'Social media links');

        // 8. Seed Admin User
        $this->callSeeder(AdminUserSeeder::class, 'Admin user seeder');

        // 9. Clear cache after seeding
        Artisan::call('optimize:clear');
        $this->info('  ✓ Cache cleared');
    }

    private function callSeeder(string $seederClass, string $description): void
    {
        $this->info("  Seeding {$description}...");

        try {
            // Use Laravel's call method to properly set up command output
            $this->call('db:seed', [
                '--class' => $seederClass,
                '--force' => true,
            ]);
            $this->info("  ✓ {$description} seeded successfully");
        } catch (Exception $e) {
            $this->error("  ✗ Failed to seed {$description}: " . $e->getMessage());
            throw $e;
        }
    }

    private function cleanDatabase(): void
    {
        $this->info('Cleaning database...');

        // Disable foreign key constraints
        Schema::disableForeignKeyConstraints();

        // Tables to truncate in reverse dependency order
        $tables = [
            'payments',
            'donations',
            'campaigns',
            'bookmarks',
            'social_media',
            'pages',
            'payment_gateways',
            'currency_payment_gateway',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'password_reset_tokens',
            'personal_access_tokens',
            'users',
            'organizations',
            'categories',
            'currencies',
            'permissions',
            'roles',
            'failed_jobs',
            'jobs',
            'job_progress',
            'notifications',
            'audits',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->info("  Truncated table: {$table}");
            }
        }

        // Re-enable foreign key constraints
        Schema::enableForeignKeyConstraints();

        $this->info('Database cleaned successfully.');
    }
}
