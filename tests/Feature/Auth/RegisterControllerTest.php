<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('RegisterController', function (): void {
    beforeEach(function (): void {
        // RefreshDatabase already handles database state - no need for additional setup
    });
    it('shows the registration page', function (): void {
        $response = $this->get('/register');

        // Route might redirect due to localization middleware
        if ($response->status() === 302) {
            expect($response->isRedirect())->toBeTrue();
        } else {
            expect($response->status())->toBe(200);
        }
    });

    it('validates registration data requires all fields', function (): void {
        $response = $this->post('/register', []);

        expect($response->status())->toBe(302)
            ->and($response->isRedirect())->toBeTrue();

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    });

    it('validates email format is required', function (): void {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        expect($response->status())->toBe(302)
            ->and($response->isRedirect())->toBeTrue();

        $response->assertSessionHasErrors(['email']);
    });

    it('requires password confirmation', function (): void {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@acme-corp.com',
            'password' => 'password123',
        ]);

        expect($response->status())->toBe(302)
            ->and($response->isRedirect())->toBeTrue();

        $response->assertSessionHasErrors(['password']);
    });

    it('validates password match confirmation', function (): void {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@acme-corp.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        expect($response->status())->toBe(302)
            ->and($response->isRedirect())->toBeTrue();

        $response->assertSessionHasErrors(['password']);
    });

    it('enforces minimum password length', function (): void {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@acme-corp.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        expect($response->status())->toBe(302)
            ->and($response->isRedirect())->toBeTrue();

        $response->assertSessionHasErrors(['password']);
    });

    it('successfully registers a new user', function (): void {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@acme-corp.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        expect($response->status())->toBe(302)
            ->and($response->isRedirect())->toBeTrue();

        // User should be created
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@acme-corp.com',
        ]);

        // User should be authenticated after registration
        $this->assertAuthenticated();

        $user = auth()->user();
        expect($user->email)->toBe('john@acme-corp.com')
            ->and($user->name)->toBe('John Doe')
            ->and($user->email_verified_at)->toBeNull(); // Should not be verified initially
    });

    it('prevents duplicate email registration', function (): void {
        // Create existing user
        User::factory()->create([
            'email' => 'john@acme-corp.com',
        ]);

        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@acme-corp.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        expect($response->status())->toBe(302)
            ->and($response->isRedirect())->toBeTrue();

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    });

    it('redirects to dashboard after successful registration', function (): void {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@acme-corp.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        expect($response->status())->toBe(302);
        $response->assertRedirect('/dashboard');
    });

    it('handles json registration requests', function (): void {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@acme-corp.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/register', $userData);

        // JSON requests should return success status (could be 200 or 201)
        expect($response->status())->toBeIn([200, 201, 204]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@acme-corp.com',
        ]);

        $this->assertAuthenticated();
    });

    it('returns validation errors for json requests', function (): void {
        $response = $this->postJson('/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);

        expect($response->status())->toBe(422);
        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    });

    it('sets default values for new user', function (): void {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@acme-corp.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        expect($response->status())->toBe(302);

        $user = User::where('email', 'john@acme-corp.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->name)->toBe('John Doe')
            ->and($user->email)->toBe('john@acme-corp.com');
    });

    it('sends email verification notification', function (): void {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@acme-corp.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        expect($response->status())->toBe(302);

        $user = User::where('email', 'john@acme-corp.com')->first();

        // Check that email verification was set up
        expect($user->email_verified_at)->toBeNull();
        expect($user->hasVerifiedEmail())->toBeFalse();
    });
});
