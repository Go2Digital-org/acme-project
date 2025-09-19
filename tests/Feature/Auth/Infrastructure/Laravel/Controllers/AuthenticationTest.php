<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('Authentication - Lightweight Database Tests', function (): void {
    describe('User Registration Logic', function (): void {
        it('creates user with valid data', function (): void {
            $user = User::factory()->create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
            ]);

            expect($user->name)->toBe('John Doe');
            expect($user->email)->toBe('john@example.com');
            expect($user->password)->not->toBe('password123'); // Should be hashed

            $this->assertDatabaseHas('users', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);
        });

        it('enforces unique email constraint', function (): void {
            User::factory()->create(['email' => 'john@example.com']);

            expect(function (): void {
                User::factory()->create(['email' => 'john@example.com']);
            })->toThrow(Exception::class);
        });

        it('hashes passwords automatically', function (): void {
            $user = User::factory()->create(['password' => 'plaintext']);

            expect($user->password)->not->toBe('plaintext');
            expect(Hash::check('plaintext', $user->password))->toBeTrue();
        });
    });

    describe('User Authentication Logic', function (): void {
        it('validates credentials correctly', function (): void {
            $user = User::factory()->create([
                'email' => 'john@example.com',
                'password' => Hash::make('password123'),
            ]);

            expect(Hash::check('password123', $user->password))->toBeTrue();
            expect(Hash::check('wrong_password', $user->password))->toBeFalse();
        });

        it('can create authentication tokens', function (): void {
            $user = User::factory()->create();
            $token = $user->createToken('auth_token');

            expect($token->plainTextToken)->toBeString();
            expect(strlen($token->plainTextToken))->toBeGreaterThan(10);
        });
    });

    describe('Multi-tenant User Management', function (): void {
        it('associates users with organizations', function (): void {
            $organization = Organization::factory()->create([
                'name' => ['en' => 'Test Organization'],
            ]);

            $user = User::factory()->create([
                'organization_id' => $organization->id,
                'email' => 'john@testorg.com',
            ]);

            expect($user->organization_id)->toBe($organization->id);
            expect($user->organization)->not->toBeNull();
            expect($user->organization->getTranslation('name', 'en'))->toBe('Test Organization');
        });

        it('supports users from different organizations', function (): void {
            $org1 = Organization::factory()->create(['name' => ['en' => 'Organization 1']]);
            $org2 = Organization::factory()->create(['name' => ['en' => 'Organization 2']]);

            $user1 = User::factory()->create([
                'email' => 'user1@org1.com',
                'organization_id' => $org1->id,
            ]);

            $user2 = User::factory()->create([
                'email' => 'user2@org2.com',
                'organization_id' => $org2->id,
            ]);

            expect($user1->organization_id)->toBe($org1->id);
            expect($user2->organization_id)->toBe($org2->id);
            expect($user1->organization_id)->not->toBe($user2->organization_id);
        });
    });
});
