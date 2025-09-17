<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Modules\User\Infrastructure\Laravel\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RestoreAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:restore-admin 
                            {--email=admin@acme.com : The email of the admin user to restore}
                            {--role=super_admin : The role to assign to the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore super admin privileges for admin user (useful after Shield operations)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string $email */
        $email = $this->option('email');
        /** @var string $roleName */
        $roleName = $this->option('role');

        $this->info("🔧 Restoring admin privileges for: {$email}");

        // Clear permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Find the user
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("❌ User not found: {$email}");
            $this->line('');
            $this->line('Available admin users:');

            User::whereHas('roles')->get()->each(function (User $u): void {
                $roles = $u->roles->pluck('name')->join(', ');
                $this->line("  - {$u->email} (Roles: {$roles})");
            });

            return Command::FAILURE;
        }

        // Find or create the super_admin role
        $role = Role::firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web'],
        );

        // Give the role all permissions
        $allPermissions = Permission::all();
        $role->syncPermissions($allPermissions);

        $this->info("✅ Role '{$roleName}' configured with all {$allPermissions->count()} permissions");

        // Assign the role to the user
        $previousRoles = $user->roles->pluck('name')->toArray();
        $user->syncRoles($role);

        $this->info('📝 User roles updated:');
        $this->line('   Previous roles: ' . (empty($previousRoles) ? 'none' : implode(', ', $previousRoles)));
        $this->line("   Current role: {$roleName}");

        // Verify the user can access admin panel
        if ($user->hasPermissionTo('access_admin_panel')) {
            $this->info('✅ Verified: User can access admin panel');
        } else {
            $this->warn("⚠️  Warning: User may not have 'access_admin_panel' permission");
        }

        // Show summary
        $this->newLine();
        $this->info('🎉 Admin privileges successfully restored!');
        $this->table(
            ['Email', 'Name', 'Role', 'Permissions'],
            [[
                $user->email,
                $user->name,
                $roleName,
                $user->getAllPermissions()->count() . ' permissions',
            ]],
        );

        $this->newLine();
        $this->line('You can now log in at: ' . url('/admin'));
        $this->line("Email: {$user->email}");
        $this->line('Password: [unchanged]');

        return Command::SUCCESS;
    }
}
