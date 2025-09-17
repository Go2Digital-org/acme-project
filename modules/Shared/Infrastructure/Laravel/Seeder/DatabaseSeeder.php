<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Seeder;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Campaign\Infrastructure\Laravel\Seeder\CampaignSeeder;
use Modules\Category\Infrastructure\Laravel\Seeder\CategorySeeder;
use Modules\Currency\Infrastructure\Laravel\Seeder\CurrencySeeder;
use Modules\Donation\Infrastructure\Laravel\Seeder\DonationSeeder;
use Modules\Donation\Infrastructure\Laravel\Seeder\PaymentGatewaySeeder;
use Modules\Organization\Infrastructure\Laravel\Seeder\OrganizationSeeder;
use Modules\User\Infrastructure\Laravel\Seeder\AdminUserSeeder;
use Modules\User\Infrastructure\Laravel\Seeder\ShieldAdminSeeder;
use Modules\User\Infrastructure\Laravel\Seeder\UserSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Starting ACME Corp CSR Platform database seeding...');

        // Clean database before seeding
        $this->cleanDatabase();

        // Seed in dependency order - all seeders now in hexagonal modules
        $this->call([
            // Core admin users first
            AdminUserSeeder::class,

            // Payment gateway configurations (required for donations)
            PaymentGatewaySeeder::class,

            // Categories (needed before campaigns)
            CategorySeeder::class,

            // Currencies (needed for donations and user preferences)
            CurrencySeeder::class,

            // Hex architecture seeders for CSR platform
            // \Modules\Organization\Infrastructure\Laravel\Seeder\AcmeCorpOrganizationSeeder::class, // Temporarily disabled due to database field conflict
            OrganizationSeeder::class,
            UserSeeder::class,
            CampaignSeeder::class,
            DonationSeeder::class,

            // Content pages and social media seeders temporarily disabled due to missing classes

            // Shield compatibility - ensures admin@acme.com keeps super_admin role
            ShieldAdminSeeder::class,
        ]);

        $this->command->info('ACME Corp CSR Platform database seeding complete.');
        $this->command->info('Ready for enterprise demonstration with rich demo data.');
    }

    /**
     * Clean the database before seeding.
     */
    private function cleanDatabase(): void
    {
        $this->command->info('Cleaning database...');

        // Disable foreign key constraints
        Schema::disableForeignKeyConstraints();

        // Tables to truncate in reverse dependency order
        $tables = [
            'payments',
            'donations',
            'campaign_updates',
            'campaigns',
            'bookmarks',
            'social_media',
            'pages',
            'payment_gateways',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'password_resets',
            'password_reset_tokens',
            'personal_access_tokens',
            'users',
            'organizations',
            'categories',
            'permissions',
            'roles',
            'failed_jobs',
            'jobs',
            'job_progress',
            'notifications',
            'audit_logs',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->command->info("  Truncated table: {$table}");
            }
        }

        // Re-enable foreign key constraints
        Schema::enableForeignKeyConstraints();

        $this->command->info('Database cleaned successfully.');
    }
}
