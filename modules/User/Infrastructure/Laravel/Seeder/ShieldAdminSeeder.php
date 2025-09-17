<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Laravel\Seeder;

use Illuminate\Database\Seeder;
use Modules\User\Infrastructure\Laravel\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * This seeder ensures Shield compatibility with our custom admin users.
 * Run this after shield:setup to restore proper permissions.
 */
class ShieldAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Skip in test environment to prevent permission conflicts
        if (app()->environment('testing', 'test')) {
            $this->command->info('Skipping ShieldAdminSeeder in test environment');

            return;
        }

        // Clear cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Setting up Filament Shield permissions...');

        // Generate all Filament resource permissions
        $this->generateFilamentResourcePermissions();

        // Ensure super_admin role exists and has all permissions
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web'],
        );

        // Give super_admin ALL permissions (including Shield-generated ones)
        $superAdminRole->syncPermissions(Permission::all());

        // Ensure admin@acme.com always has super_admin role
        $superAdmin = User::where('email', 'admin@acme.com')->first();

        if (! $superAdmin) {
            $this->command->warn('User admin@acme.com not found. Run AdminUserSeeder first.');

            return;
        }

        // Use syncRoles to ensure only super_admin role is assigned
        $superAdmin->syncRoles($superAdminRole);

        $this->command->info('Super Admin role restored for admin@acme.com');
        $this->command->info('  - Role: super_admin');
        $this->command->info('  - Permissions: All (' . Permission::count() . ' permissions)');

        // Ensure other admin roles have proper permissions
        $this->ensureAdminRolePermissions();
        $this->ensureModeratorRolePermissions();
        $this->ensureEmployeeRolePermissions();

        $this->command->info('Shield admin configuration completed.');
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

        // Add widget and page permissions
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

        // Add page permissions
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

        $this->command->info('Generated Filament resource permissions.');
    }

    /**
     * Ensure admin role has appropriate permissions.
     */
    private function ensureAdminRolePermissions(): void
    {
        $adminRole = Role::where('name', 'admin')->first();

        if (! $adminRole) {
            return;
        }

        // Get all Shield-generated permissions that admin should have
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
    }

    /**
     * Ensure moderator role has appropriate permissions.
     */
    private function ensureModeratorRolePermissions(): void
    {
        $moderatorRole = Role::where('name', 'moderator')->first();

        if (! $moderatorRole) {
            return;
        }

        $moderatorPermissions = Permission::where(function ($query): void {
            $query->where('name', 'like', 'view_%')
                ->orWhere('name', 'moderate_campaigns')
                ->orWhere('name', 'access_admin_panel')
                ->orWhere('name', 'page_dashboard');
        })->pluck('name');

        $moderatorRole->syncPermissions($moderatorPermissions);
    }

    /**
     * Ensure employee role has appropriate permissions.
     */
    private function ensureEmployeeRolePermissions(): void
    {
        $employeeRole = Role::where('name', 'employee')->first();

        if ($employeeRole) {
            $employeePermissions = Permission::whereIn('name', [
                'view_campaigns',
                'create_campaigns',
                'view_donations',
                'create_donations',
                'view_organizations',
            ])->pluck('name');

            // Also add any Shield-generated view permissions for basic resources
            $viewPermissions = Permission::where('name', 'like', 'view_%')
                ->whereIn('name', [
                    'view_campaign',
                    'view_any_campaign',
                    'view_donation',
                    'view_any_donation',
                    'view_organization',
                    'view_any_organization',
                ])
                ->pluck('name');

            $employeeRole->syncPermissions($employeePermissions->merge($viewPermissions));
        }
    }
}
