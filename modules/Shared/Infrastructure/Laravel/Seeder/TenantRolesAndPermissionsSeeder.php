<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Seeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Tenant Roles and Permissions Seeder.
 *
 * Seeds the same roles and permissions structure as the central database
 * to ensure consistency across all tenant databases.
 */
class TenantRolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Setting up tenant roles and permissions...');

        // Create all permissions first
        $this->createPermissions();

        // Create roles and assign permissions
        $this->createRoles();

        $this->command->info('Tenant roles and permissions setup completed.');
    }

    /**
     * Create all permissions for the tenant.
     */
    private function createPermissions(): void
    {
        // Resource permissions
        $resources = [
            'campaign' => 'Campaign',
            'donation' => 'Donation',
            'user' => 'User',
            'organization' => 'Organization',
            'category' => 'Category',
            'page' => 'Page',
            'payment_gateway' => 'PaymentGateway',
            'currency' => 'Currency',
            'social_media' => 'SocialMedia',
            'role' => 'Role',
        ];

        $actions = [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
            'force_delete',
            'force_delete_any',
        ];

        foreach (array_keys($resources) as $key) {
            foreach ($actions as $action) {
                $permissionName = "{$action}_{$key}";
                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);
            }
        }

        // Widget permissions
        $widgets = [
            'stats_overview',
            'campaign_chart',
            'donation_chart',
            'recent_donations',
            'recent_campaigns',
        ];

        foreach ($widgets as $widget) {
            Permission::firstOrCreate([
                'name' => "widget_{$widget}",
                'guard_name' => 'web',
            ]);
        }

        // Page permissions
        $pages = [
            'dashboard',
            'reports',
            'settings',
            'audit_logs',
        ];

        foreach ($pages as $page) {
            Permission::firstOrCreate([
                'name' => "page_{$page}",
                'guard_name' => 'web',
            ]);
        }

        // Additional special permissions
        $specialPermissions = [
            'access_admin_panel',
            'view_reports',
            'export_data',
            'moderate_campaigns',
            'publish_campaigns',
            'refund_donations',
            'verify_organizations',
        ];

        foreach ($specialPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $this->command->info('Created ' . Permission::count() . ' permissions.');
    }

    /**
     * Create roles and assign permissions.
     */
    private function createRoles(): void
    {
        // Create super_admin role with all permissions
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web']
        );
        $superAdminRole->syncPermissions(Permission::all());
        $this->command->info('Created super_admin role with all permissions.');

        // Create admin role
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web']
        );
        $adminPermissions = Permission::where(function ($query): void {
            $query->where('name', 'like', 'view_%')
                ->orWhere('name', 'like', 'create_%')
                ->orWhere('name', 'like', 'update_%')
                ->orWhere('name', 'like', 'delete_%')
                ->orWhere('name', 'like', 'restore_%')
                ->orWhere('name', 'like', 'publish_%')
                ->orWhere('name', 'like', 'moderate_%')
                ->orWhere('name', 'like', 'refund_%')
                ->orWhere('name', 'like', 'verify_%')
                ->orWhere('name', 'like', 'page_%')
                ->orWhere('name', 'like', 'widget_%')
                ->orWhere('name', 'access_admin_panel')
                ->orWhere('name', 'view_reports')
                ->orWhere('name', 'export_data');
        })->pluck('name');
        $adminRole->syncPermissions($adminPermissions);
        $this->command->info('Created admin role with management permissions.');

        // Create moderator role
        $moderatorRole = Role::firstOrCreate(
            ['name' => 'moderator', 'guard_name' => 'web']
        );
        $moderatorPermissions = Permission::where(function ($query): void {
            $query->where('name', 'like', 'view_%')
                ->orWhere('name', 'moderate_campaigns')
                ->orWhere('name', 'access_admin_panel')
                ->orWhere('name', 'page_dashboard');
        })->pluck('name');
        $moderatorRole->syncPermissions($moderatorPermissions);
        $this->command->info('Created moderator role with view and moderation permissions.');

        // Create employee role
        $employeeRole = Role::firstOrCreate(
            ['name' => 'employee', 'guard_name' => 'web']
        );
        $employeePermissions = Permission::whereIn('name', [
            'view_campaign',
            'view_any_campaign',
            'create_campaign',
            'view_donation',
            'view_any_donation',
            'create_donation',
            'view_organization',
            'view_any_organization',
        ])->pluck('name');
        $employeeRole->syncPermissions($employeePermissions);
        $this->command->info('Created employee role with basic permissions.');

        $this->command->info('Successfully created ' . Role::count() . ' roles.');
    }
}
