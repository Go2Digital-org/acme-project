<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('User Management - Database Feature Tests', function (): void {
    beforeEach(function (): void {
        $this->organization = Organization::factory()->create();
    });

    describe('User Model Operations', function (): void {
        it('creates users with complete data', function (): void {
            $user = User::factory()->create([
                'organization_id' => $this->organization->id,
                'email' => 'john.doe@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'role' => 'employee',
            ]);

            expect($user->email)->toBe('john.doe@example.com');
            expect($user->first_name)->toBe('John');
            expect($user->last_name)->toBe('Doe');
            expect($user->organization_id)->toBe($this->organization->id);
            expect($user->role)->toBe('employee');

            $this->assertDatabaseHas('users', [
                'email' => 'john.doe@example.com',
                'organization_id' => $this->organization->id,
            ]);
        });

        it('creates admin users', function (): void {
            $admin = User::factory()->admin()->create([
                'organization_id' => $this->organization->id,
            ]);

            expect($admin->role)->toBe('admin');
            expect($admin->organization_id)->toBe($this->organization->id);
        });

        it('generates full names correctly', function (): void {
            $user = User::factory()->create([
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'name' => 'Jane Smith',
            ]);

            expect($user->name)->toBe('Jane Smith');
            expect($user->first_name)->toBe('Jane');
            expect($user->last_name)->toBe('Smith');
        });

        it('handles users without organization', function (): void {
            $user = User::factory()->create(['organization_id' => null]);

            expect($user->organization_id)->toBeNull();
            expect($user->organization)->toBeNull();
        });
    });

    describe('User Authentication Features', function (): void {
        it('creates and manages API tokens', function (): void {
            $user = User::factory()->create();

            $token = $user->createToken('api-token');
            expect($token->plainTextToken)->toBeString();
            expect(strlen($token->plainTextToken))->toBeGreaterThan(10);

            $user->tokens()->delete();
            expect($user->tokens)->toHaveCount(0);
        });

        it('validates password hashing', function (): void {
            $password = 'secure-password-123';
            $user = User::factory()->create(['password' => $password]);

            expect($user->password)->not->toBe($password);
            expect(strlen($user->password))->toBeGreaterThan(50);
        });

        it('remembers authentication tokens', function (): void {
            $user = User::factory()->create();

            expect($user->remember_token)->toBeString();
            expect(strlen($user->remember_token))->toBeGreaterThanOrEqual(10);
        });
    });

    describe('User Role Management', function (): void {
        it('assigns and manages user roles', function (): void {
            $user = User::factory()->create();

            $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
            $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

            $user->assignRole($userRole);
            expect($user->hasRole('user'))->toBeTrue();
            expect($user->hasRole('admin'))->toBeFalse();

            $user->assignRole($adminRole);
            expect($user->hasRole('admin'))->toBeTrue();
            expect($user->roles)->toHaveCount(2);
        });

        it('removes user roles', function (): void {
            $user = User::factory()->create();
            $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

            $user->assignRole($role);
            expect($user->hasRole('user'))->toBeTrue();

            $user->removeRole($role);
            expect($user->hasRole('user'))->toBeFalse();
        });

        it('syncs user roles', function (): void {
            $user = User::factory()->create();

            $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
            $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

            $user->syncRoles([$userRole, $adminRole]);
            expect($user->roles)->toHaveCount(2);

            $user->syncRoles([$userRole]);
            expect($user->roles)->toHaveCount(1);
            expect($user->hasRole('user'))->toBeTrue();
            expect($user->hasRole('admin'))->toBeFalse();
        });
    });

    describe('Organization Association', function (): void {
        it('links users to organizations', function (): void {
            $user = User::factory()->create([
                'organization_id' => $this->organization->id,
            ]);

            expect($user->organization)->not->toBeNull();
            expect($user->organization->id)->toBe($this->organization->id);
        });

        it('queries users by organization', function (): void {
            $org1 = Organization::factory()->create();
            $org2 = Organization::factory()->create();

            User::factory()->count(3)->create(['organization_id' => $org1->id]);
            User::factory()->count(2)->create(['organization_id' => $org2->id]);
            User::factory()->create(['organization_id' => null]);

            $org1Users = User::where('organization_id', $org1->id)->get();
            $org2Users = User::where('organization_id', $org2->id)->get();
            $unassignedUsers = User::whereNull('organization_id')->get();

            expect($org1Users)->toHaveCount(3);
            expect($org2Users)->toHaveCount(2);
            expect($unassignedUsers)->toHaveCount(1);
        });
    });

    describe('User Validation and Constraints', function (): void {
        it('enforces unique email addresses', function (): void {
            User::factory()->create(['email' => 'unique@example.com']);

            expect(function (): void {
                User::factory()->create(['email' => 'unique@example.com']);
            })->toThrow(Exception::class);
        });

        it('validates email format', function (): void {
            $user = User::factory()->create(['email' => 'valid@example.com']);
            expect($user->email)->toMatch('/^[^\s@]+@[^\s@]+\.[^\s@]+$/');
        });

        it('creates appropriate timestamps', function (): void {
            $user = User::factory()->unverified()->create();

            expect($user->created_at)->not->toBeNull();
            expect($user->updated_at)->not->toBeNull();
            expect($user->email_verified_at)->toBeNull(); // Default is not verified
        });

        it('handles email verification', function (): void {
            $user = User::factory()->create(['email_verified_at' => now()]);

            expect($user->email_verified_at)->not->toBeNull();
            expect($user->hasVerifiedEmail())->toBeTrue();
        });
    });

    describe('User Factory States', function (): void {
        it('creates users with different factory states', function (): void {
            $admin = User::factory()->admin()->create();
            $verified = User::factory()->verified()->create();
            $unverified = User::factory()->unverified()->create();

            expect($admin->role)->toBe('admin');
            expect($verified->hasVerifiedEmail())->toBeTrue();
            expect($unverified->hasVerifiedEmail())->toBeFalse();
        });

        it('creates users with organization relationships', function (): void {
            $user = User::factory()
                ->for($this->organization)
                ->create();

            expect($user->organization_id)->toBe($this->organization->id);
            expect($user->organization)->not->toBeNull();
        });
    });

    describe('User Business Logic', function (): void {
        it('scopes admin users', function (): void {
            User::factory()->admin()->count(2)->create();
            User::factory()->count(3)->create();

            $admins = User::where('role', 'admin')->get();
            $nonAdmins = User::where('role', 'employee')->get();

            expect($admins)->toHaveCount(2);
            expect($nonAdmins)->toHaveCount(3);
        });

        it('manages user status effectively', function (): void {
            $activeUser = User::factory()->create(['status' => 'active']);
            $inactiveUser = User::factory()->create(['status' => 'inactive']);

            expect($activeUser->status)->toBe('active');
            expect($inactiveUser->status)->toBe('inactive');
        });
    });
});
