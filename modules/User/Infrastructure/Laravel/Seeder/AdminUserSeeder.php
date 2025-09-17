<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Laravel\Seeder;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\User\Infrastructure\Laravel\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',

            // Campaign management
            'view_campaigns',
            'create_campaigns',
            'edit_campaigns',
            'delete_campaigns',
            'publish_campaigns',
            'moderate_campaigns',

            // Donation management
            'view_donations',
            'create_donations',
            'edit_donations',
            'delete_donations',
            'refund_donations',

            // Organization management
            'view_organizations',
            'create_organizations',
            'edit_organizations',
            'delete_organizations',
            'verify_organizations',

            // Admin functions
            'access_admin_panel',
            'manage_settings',
            'view_reports',
            'export_data',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $moderatorRole = Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'web']);
        $employeeRole = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);

        // Assign permissions to roles
        $superAdminRole->givePermissionTo(Permission::all());

        $adminRole->givePermissionTo([
            'view_users', 'create_users', 'edit_users',
            'view_campaigns', 'create_campaigns', 'edit_campaigns', 'delete_campaigns', 'publish_campaigns', 'moderate_campaigns',
            'view_donations', 'create_donations', 'edit_donations', 'refund_donations',
            'view_organizations', 'create_organizations', 'edit_organizations', 'verify_organizations',
            'access_admin_panel', 'view_reports', 'export_data',
        ]);

        $moderatorRole->givePermissionTo([
            'view_campaigns', 'moderate_campaigns',
            'view_donations',
            'view_organizations',
            'access_admin_panel',
        ]);

        $employeeRole->givePermissionTo([
            'view_campaigns', 'create_campaigns',
            'view_donations', 'create_donations',
            'view_organizations',
        ]);

        // Create admin users
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@acme.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $admin = User::firstOrCreate(
            ['email' => 'manager@acme.com'],
            [
                'name' => 'CSR Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $moderator = User::firstOrCreate(
            ['email' => 'moderator@acme.com'],
            [
                'name' => 'Campaign Moderator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $employee = User::firstOrCreate(
            ['email' => 'employee@acme.com'],
            [
                'name' => 'ACME Employee',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        // Assign roles to users (preserve existing super_admin if Shield is active)
        if (! $superAdmin->hasRole('super_admin')) {
            $superAdmin->syncRoles($superAdminRole);
        }

        if (! $admin->hasRole('admin')) {
            $admin->syncRoles($adminRole);
        }

        if (! $moderator->hasRole('moderator')) {
            $moderator->syncRoles($moderatorRole);
        }

        if (! $employee->hasRole('employee')) {
            $employee->syncRoles($employeeRole);
        }

        $this->command->info('Admin users created successfully!');
        $this->command->line('');
        $this->command->line('Login Credentials:');
        $this->command->line('==================');
        $this->command->line('Super Admin (Full Access):');
        $this->command->line('  Email: admin@acme.com');
        $this->command->line('  Password: password');
        $this->command->line('');
        $this->command->line('CSR Manager:');
        $this->command->line('  Email: manager@acme.com');
        $this->command->line('  Password: password');
        $this->command->line('');
        $this->command->line('Campaign Moderator:');
        $this->command->line('  Email: moderator@acme.com');
        $this->command->line('  Password: password');
        $this->command->line('');
        $this->command->line('Employee:');
        $this->command->line('  Email: employee@acme.com');
        $this->command->line('  Password: password');
    }
}
