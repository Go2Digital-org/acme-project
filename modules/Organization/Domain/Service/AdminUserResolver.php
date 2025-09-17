<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Service;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Service for resolving the appropriate admin user for tenant operations.
 *
 * This service handles finding or creating admin users with proper Spatie roles,
 * ensuring consistency between the legacy role field and modern Spatie permissions.
 */
final class AdminUserResolver
{
    /**
     * Resolve the admin user for a given organization.
     *
     * Tries multiple strategies:
     * 1. Find by email from tenant_data
     * 2. Find any user with super_admin Spatie role
     * 3. Find by legacy role field
     * 4. Create new admin if none exists
     *
     * @param  Organization  $organization  The tenant organization
     * @return User The resolved admin user
     */
    public function resolveAdminUser(Organization $organization): User
    {
        Log::info('Resolving admin user for organization', [
            'organization_id' => $organization->id,
            'subdomain' => $organization->subdomain,
        ]);

        // Strategy 1: Try to find by email from tenant_data
        $adminData = $organization->getAdminData();
        if ($adminData && isset($adminData['email'])) {
            $user = User::where('email', $adminData['email'])->first();

            if ($user) {
                Log::info('Found admin user by tenant_data email', [
                    'user_id' => $user->id,
                    'email' => $adminData['email'],
                ]);

                $this->ensureSpatieRole($user);

                return $user;
            }
        }

        // Strategy 2: Find any user with super_admin Spatie role
        $user = User::role('super_admin')->first();
        if ($user) {
            Log::info('Found admin user with super_admin Spatie role', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return $user;
        }

        // Strategy 3: Find by legacy role field
        $user = User::where('role', 'super_admin')->first();
        if ($user) {
            Log::info('Found admin user by legacy role field', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            $this->ensureSpatieRole($user);

            return $user;
        }

        // Strategy 4: Create new admin if none exists
        Log::warning('No admin user found, creating new one', [
            'organization_id' => $organization->id,
        ]);

        return $this->createAdminUser($organization);
    }

    /**
     * Ensure the user has the super_admin Spatie role.
     *
     * This handles legacy users that might have the role field
     * but not the proper Spatie role assignment.
     */
    private function ensureSpatieRole(User $user): void
    {
        if (! $user->hasRole('super_admin')) {
            Log::info('User missing super_admin Spatie role, assigning it', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            $role = Role::where('name', 'super_admin')
                ->where('guard_name', 'web')
                ->first();

            if (! $role) {
                Log::error('super_admin role not found in database', [
                    'user_id' => $user->id,
                ]);

                return;
            }

            $user->assignRole($role);

            Log::info('Assigned super_admin Spatie role to user', [
                'user_id' => $user->id,
                'role_id' => $role->id,
            ]);
        }
    }

    /**
     * Create a new admin user for the organization.
     *
     * Uses tenant_data if available, otherwise generates defaults.
     * Ensures both legacy role field and Spatie role are set.
     */
    private function createAdminUser(Organization $organization): User
    {
        $adminData = $organization->getAdminData();

        // Prepare user data
        $userData = [
            'name' => $adminData['name'] ?? 'Super Admin',
            'email' => $adminData['email'] ?? 'admin@' . $organization->subdomain . '.test',
            'password' => Hash::make($adminData['password'] ?? Str::random(16)),
            'email_verified_at' => now(),
            'status' => 'active',
            'role' => 'super_admin', // Legacy field for compatibility
        ];

        Log::info('Creating new admin user', [
            'organization_id' => $organization->id,
            'email' => $userData['email'],
        ]);

        $user = User::create($userData);

        // Assign Spatie role
        $this->ensureSpatieRole($user);

        Log::info('Admin user created successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'has_spatie_role' => $user->hasRole('super_admin'),
        ]);

        return $user;
    }
}
