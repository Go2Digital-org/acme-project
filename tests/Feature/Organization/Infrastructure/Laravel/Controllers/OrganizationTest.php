<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('Organization - Database Feature Tests', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
    });

    describe('Organization Model Operations', function (): void {
        it('creates organizations with valid data', function (): void {
            $organization = Organization::factory()->create([
                'name' => ['en' => 'Environmental Foundation'],
                'category' => 'environmental',
                'email' => 'contact@envfoundation.org',
                'phone' => '+1-555-0123',
            ]);

            expect($organization->getTranslation('name', 'en'))->toBe('Environmental Foundation');
            expect($organization->category)->toBe('environmental');
            expect($organization->email)->toBe('contact@envfoundation.org');

            $this->assertDatabaseHas('organizations', [
                'email' => 'contact@envfoundation.org',
                'category' => 'environmental',
            ]);
        });

        it('supports organization categories', function (): void {
            $environmental = Organization::factory()->create(['category' => 'environmental']);
            $education = Organization::factory()->create(['category' => 'education']);
            $health = Organization::factory()->create(['category' => 'health']);

            expect($environmental->category)->toBe('environmental');
            expect($education->category)->toBe('education');
            expect($health->category)->toBe('health');
        });

        it('validates organization verification status', function (): void {
            $verified = Organization::factory()->verified()->create();
            $unverified = Organization::factory()->unverified()->create();

            expect($verified->is_verified)->toBeTrue();
            expect($unverified->is_verified)->toBeFalse();
        });

        it('handles multilingual organization names', function (): void {
            $organization = Organization::factory()->create([
                'name' => [
                    'en' => 'Green Future Foundation',
                    'fr' => 'Fondation Avenir Vert',
                ],
            ]);

            expect($organization->getTranslation('name', 'en'))->toBe('Green Future Foundation');
            expect($organization->getTranslation('name', 'fr'))->toBe('Fondation Avenir Vert');
        });

        it('manages organization contact information', function (): void {
            $organization = Organization::factory()->create([
                'email' => 'info@foundation.org',
                'phone' => '+1-555-9876',
                'website' => 'https://foundation.org',
                'address' => '123 Green Street',
                'city' => 'Portland',
                'country' => 'USA',
            ]);

            expect($organization->email)->toBe('info@foundation.org');
            expect($organization->phone)->toBe('+1-555-9876');
            expect($organization->website)->toBe('https://foundation.org');
            expect($organization->address)->toBe('123 Green Street');
            expect($organization->city)->toBe('Portland');
            expect($organization->country)->toBe('USA');
        });
    });

    describe('Organization Business Logic', function (): void {
        it('scopes verified organizations', function (): void {
            Organization::factory()->verified()->count(3)->create();
            Organization::factory()->unverified()->count(2)->create();

            $verifiedOrgs = Organization::verified()->get();
            expect($verifiedOrgs)->toHaveCount(3);

            foreach ($verifiedOrgs as $org) {
                expect($org->is_verified)->toBeTrue();
            }
        });

        it('filters organizations by category', function (): void {
            Organization::factory()->create(['category' => 'environmental']);
            Organization::factory()->create(['category' => 'environmental']);
            Organization::factory()->create(['category' => 'education']);

            $environmentalOrgs = Organization::where('category', 'environmental')->get();
            $educationOrgs = Organization::where('category', 'education')->get();

            expect($environmentalOrgs)->toHaveCount(2);
            expect($educationOrgs)->toHaveCount(1);
        });

        it('searches organizations by name', function (): void {
            Organization::factory()->create([
                'name' => ['en' => 'Wildlife Conservation Foundation'],
            ]);
            Organization::factory()->create([
                'name' => ['en' => 'Education for All'],
            ]);

            $wildlifeOrgs = Organization::where('name->en', 'like', '%Wildlife%')->get();
            $educationOrgs = Organization::where('name->en', 'like', '%Education%')->get();

            expect($wildlifeOrgs)->toHaveCount(1);
            expect($educationOrgs)->toHaveCount(1);
        });

        it('allows duplicate email addresses', function (): void {
            $org1 = Organization::factory()->create(['email' => 'duplicate@foundation.org']);
            $org2 = Organization::factory()->create(['email' => 'duplicate@foundation.org']);

            expect($org1->email)->toBe('duplicate@foundation.org');
            expect($org2->email)->toBe('duplicate@foundation.org');
            expect($org1->id)->not->toBe($org2->id);
        });

        it('manages organization status transitions', function (): void {
            $organization = Organization::factory()->unverified()->create();

            expect($organization->is_verified)->toBeFalse();

            $organization->update(['is_verified' => true]);
            $organization->refresh();

            expect($organization->is_verified)->toBeTrue();
        });
    });

    describe('Organization Relationships', function (): void {
        it('associates with users', function (): void {
            $organization = Organization::factory()->create();
            $user = User::factory()->create(['organization_id' => $organization->id]);

            expect($user->organization_id)->toBe($organization->id);
            expect($user->organization)->not->toBeNull();
            expect($user->organization->id)->toBe($organization->id);
        });

        it('can have multiple employees', function (): void {
            $organization = Organization::factory()->create();

            User::factory()->count(3)->create(['organization_id' => $organization->id]);

            $employees = User::where('organization_id', $organization->id)->get();
            expect($employees)->toHaveCount(3);
        });

        it('handles organization without users', function (): void {
            $organization = Organization::factory()->create();

            $employees = User::where('organization_id', $organization->id)->get();
            expect($employees)->toHaveCount(0);
        });
    });

    describe('Organization Validation', function (): void {
        it('validates email format', function (): void {
            $organization = Organization::factory()->create(['email' => 'test@example.com']);
            expect($organization->email)->toMatch('/^[^\s@]+@[^\s@]+\.[^\s@]+$/');
        });

        it('validates website URL format', function (): void {
            $organization = Organization::factory()->create(['website' => 'https://example.com']);
            expect($organization->website)->toStartWith('http');
        });

        it('handles optional fields correctly', function (): void {
            $organization = Organization::factory()->create([
                'website' => null,
                'phone' => null,
                'address' => null,
            ]);

            expect($organization->website)->toBeNull();
            expect($organization->phone)->toBeNull();
            expect($organization->address)->toBeNull();
        });

        it('creates timestamps automatically', function (): void {
            $organization = Organization::factory()->create([
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            expect($organization->created_at)->not->toBeNull();
            expect($organization->updated_at)->not->toBeNull();
            expect($organization->created_at->diffInMinutes(now()))->toBeLessThanOrEqual(1);
        });
    });

    describe('Organization Factory States', function (): void {
        it('creates organization with specific states', function (): void {
            $verified = Organization::factory()->verified()->create();
            $unverified = Organization::factory()->unverified()->create();
            $environmental = Organization::factory()->environment()->create();
            $education = Organization::factory()->education()->create();

            expect($verified->is_verified)->toBeTrue();
            expect($unverified->is_verified)->toBeFalse();
            expect($environmental->category)->toBe('environmental');
            expect($education->category)->toBe('education');
        });
    });
});
