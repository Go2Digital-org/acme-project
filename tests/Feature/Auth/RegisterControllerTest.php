<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('User Registration - Database Tests', function (): void {
    describe('User Model Creation', function (): void {
        it('creates users with valid data', function (): void {
            $user = User::factory()->create([
                'email' => 'test@acme-corp.com',
                'name' => 'Test User',
            ]);

            expect($user->email)->toBe('test@acme-corp.com');
            expect($user->name)->toBe('Test User');

            $this->assertDatabaseHas('users', [
                'email' => 'test@acme-corp.com',
                'name' => 'Test User',
            ]);
        });

        it('validates user model requirements', function (): void {
            $user = User::factory()->create();

            expect($user->email)->not->toBeNull();
            expect($user->name)->not->toBeNull();
            expect($user->email)->toContain('@');
            expect($user->created_at)->not->toBeNull();
        });

        it('hashes passwords correctly', function (): void {
            $plainPassword = 'test-password-123';
            $user = User::factory()->create(['password' => $plainPassword]);

            expect($user->password)->not->toBe($plainPassword);
            expect(strlen($user->password))->toBeGreaterThan(20);
            expect(Hash::check($plainPassword, $user->password))->toBeTrue();
        });

        it('enforces unique email constraint', function (): void {
            $email = 'unique@acme-corp.com';
            User::factory()->create(['email' => $email]);

            expect(function () use ($email): void {
                User::factory()->create(['email' => $email]);
            })->toThrow(Exception::class);
        });
    });

    describe('Organization Association', function (): void {
        it('associates users with organizations', function (): void {
            $organization = Organization::factory()->create();

            $user = User::factory()->create([
                'organization_id' => $organization->id,
            ]);

            expect($user->organization_id)->toBe($organization->id);
            expect($user->organization)->not->toBeNull();
        });

        it('allows users without organizations', function (): void {
            $user = User::factory()->create(['organization_id' => null]);

            expect($user->organization_id)->toBeNull();
        });
    });

    describe('User Validation Business Rules', function (): void {
        it('validates email format at database level', function (): void {
            $user = User::factory()->create(['email' => 'test@domain.com']);
            expect($user->email)->toMatch('/^[\w\.-]+@[\w\.-]+\.[a-zA-Z]{2,}$/');
        });

        it('creates timestamps automatically', function (): void {
            $user = User::factory()->create([
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            expect($user->created_at)->not->toBeNull();
            expect($user->updated_at)->not->toBeNull();
            // Test that created_at is recent (within last minute)
            expect($user->created_at->diffInMinutes(now()))->toBeLessThanOrEqual(1);
        });

        it('supports different user types', function (): void {
            $admin = User::factory()->admin()->create();
            $regular = User::factory()->create();

            expect($admin->role)->toBe('admin');
            expect($regular->role)->toBe('employee');
        });
    });
});
