<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Seeder;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Modules\Category\Infrastructure\Laravel\Seeder\DefaultCategoriesSeeder;
use Modules\Currency\Infrastructure\Laravel\Seeder\DefaultCurrenciesSeeder;
use Modules\Donation\Infrastructure\Laravel\Seeder\DefaultPaymentGatewaySeeder;
use Modules\Shared\Infrastructure\Laravel\Seeder\DefaultPagesSeeder;
use Modules\Shared\Infrastructure\Laravel\Seeder\DefaultSocialMediaSeeder;
use Modules\Shared\Infrastructure\Laravel\Seeder\TenantRolesAndPermissionsSeeder;
use Modules\User\Infrastructure\Laravel\Seeder\TenantShieldSeeder;

/**
 * Tenant Database Seeder.
 *
 * This seeder is automatically run by Stancl\Tenancy when a new tenant is created.
 * It sets up all the initial data needed for a tenant to function properly.
 *
 * Following hexagonal architecture, this seeder resides in the Organization
 * module's Infrastructure layer as it handles tenant creation concerns.
 */
class TenantDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed roles and permissions first (required for super admin)
        $this->call(TenantRolesAndPermissionsSeeder::class);

        // 2. Generate Filament Shield permissions for proper admin panel access
        $this->call(TenantShieldSeeder::class);

        // 3. Seed payment gateways (before other financial data)
        $this->call(DefaultPaymentGatewaySeeder::class);

        // 4. Seed default categories
        $this->call(DefaultCategoriesSeeder::class);

        // 5. Seed default currencies
        $this->call(DefaultCurrenciesSeeder::class);

        // 6. Seed default pages
        $this->call(DefaultPagesSeeder::class);

        // 7. Seed default social media links
        $this->call(DefaultSocialMediaSeeder::class);

        // 8. Clear artisan cache after seeding
        Artisan::call('cache:clear');

        // Note: Super admin user creation is handled separately by the
        // OrganizationObserver after seeding completes, using data from
        // the Organization model's tenant_data field
    }
}
