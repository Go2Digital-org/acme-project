<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Laravel\Seeder;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Tenant Shield Seeder.
 *
 * This seeder ensures Filament Shield permissions are properly set up
 * in tenant databases for admin panel access to work correctly.
 * It runs ONLY in tenant context, never in central database.
 */
class TenantShieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Setting up Filament Shield permissions for tenant...');

        // Generate all Filament resource permissions
        $this->generateFilamentResourcePermissions();

        // Ensure super_admin role has all permissions
        $superAdminRole = Role::where('name', 'super_admin')
            ->where('guard_name', 'web')
            ->first();

        if ($superAdminRole) {
            // Give super_admin ALL permissions (including newly generated Shield ones)
            $superAdminRole->syncPermissions(Permission::all());
            $this->command->info('Super admin role updated with all ' . Permission::count() . ' permissions.');
        } else {
            $this->command->warn('Super admin role not found. Creating it...');
            $superAdminRole = Role::create([
                'name' => 'super_admin',
                'guard_name' => 'web',
            ]);
            $superAdminRole->syncPermissions(Permission::all());
            $this->command->info('Super admin role created with all ' . Permission::count() . ' permissions.');
        }

        // Update other admin roles with appropriate permissions
        $this->updateAdminRolePermissions();
        $this->updateModeratorRolePermissions();
        $this->updateEmployeeRolePermissions();

        $this->command->info('Tenant Shield configuration completed.');
    }

    /**
     * Generate all Filament resource permissions.
     */
    private function generateFilamentResourcePermissions(): void
    {
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
            'notification' => 'Notification',
            'bookmark' => 'Bookmark',
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
            'export',
            'import',
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

        // Add widget permissions
        $widgets = [
            'stats_overview',
            'campaign_chart',
            'donation_chart',
            'recent_donations',
            'recent_campaigns',
            'activity_log',
            'system_health',
        ];

        foreach ($widgets as $widget) {
            Permission::firstOrCreate([
                'name' => "widget_{$widget}",
                'guard_name' => 'web',
            ]);
        }

        // Add page permissions
        $pages = [
            'dashboard',
            'reports',
            'settings',
            'audit_logs',
            'backups',
            'system_info',
        ];

        foreach ($pages as $page) {
            Permission::firstOrCreate([
                'name' => "page_{$page}",
                'guard_name' => 'web',
            ]);
        }

        $this->command->info('Generated Filament resource permissions.');
    }

    /**
     * Update admin role permissions.
     */
    private function updateAdminRolePermissions(): void
    {
        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            // Get all Shield-generated permissions that admin should have
            $adminPermissions = Permission::where(function ($query): void {
                $query->where('name', 'like', 'view_%')
                    ->orWhere('name', 'like', 'create_%')
                    ->orWhere('name', 'like', 'update_%')
                    ->orWhere('name', 'like', 'delete_%')
                    ->orWhere('name', 'like', 'restore_%')
                    ->orWhere('name', 'like', 'export_%')
                    ->orWhere('name', 'like', 'import_%')
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
            $this->command->info('Admin role updated with management permissions.');
        }
    }

    /**
     * Update moderator role permissions.
     */
    private function updateModeratorRolePermissions(): void
    {
        $moderatorRole = Role::where('name', 'moderator')->first();

        if ($moderatorRole) {
            $moderatorPermissions = Permission::where(function ($query): void {
                $query->where('name', 'like', 'view_%')
                    ->orWhere('name', 'moderate_campaigns')
                    ->orWhere('name', 'access_admin_panel')
                    ->orWhere('name', 'page_dashboard')
                    ->orWhere('name', 'widget_stats_overview')
                    ->orWhere('name', 'widget_campaign_chart');
            })->pluck('name');

            $moderatorRole->syncPermissions($moderatorPermissions);
            $this->command->info('Moderator role updated with view and moderation permissions.');
        }
    }

    /**
     * Update employee role permissions.
     */
    private function updateEmployeeRolePermissions(): void
    {
        $employeeRole = Role::where('name', 'employee')->first();

        if ($employeeRole) {
            $employeePermissions = Permission::whereIn('name', [
                'view_campaign',
                'view_any_campaign',
                'create_campaign',
                'update_campaign', // Allow employees to update their own campaigns
                'view_donation',
                'view_any_donation',
                'create_donation',
                'view_organization',
                'view_any_organization',
                'view_category',
                'view_any_category',
            ])->pluck('name');

            $employeeRole->syncPermissions($employeePermissions);
            $this->command->info('Employee role updated with basic permissions.');
        }
    }
}
