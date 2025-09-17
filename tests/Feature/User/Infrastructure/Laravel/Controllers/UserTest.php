<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
        'email' => 'test@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    // Create user role if it doesn't exist and assign to user
    $userRole = Role::firstOrCreate(['name' => 'user']);
    $this->user->assignRole($userRole);
});

describe('User API Basic Tests', function (): void {
    it('displays authenticated user profile', function (): void {
        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/api/auth/user');

        $response->assertOk();

        $userData = $response->json('user');
        expect($userData['id'])->toBe($this->user->id);
        expect($userData['email'])->toBe($this->user->email);
    });

    it('requires authentication for profile access', function (): void {
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->get('/api/auth/user');

        $response->assertUnauthorized();
    });

    it('lists users collection', function (): void {
        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/api/users');

        $response->assertOk();
    });

    it('shows individual user', function (): void {
        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeaders(['Accept' => 'application/json'])
            ->get("/api/users/{$this->user->id}");

        $response->assertOk();

        $userData = $response->json();
        expect($userData['id'])->toBe($this->user->id);
    });

});
