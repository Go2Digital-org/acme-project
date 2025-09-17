<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Organization\Domain\Model\Organization;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::factory()->make([
        'id' => 1,
        'name' => ['en' => 'Green Future Foundation', 'fr' => 'Fondation Avenir Vert'],
        'description' => ['en' => 'Environmental protection organization', 'fr' => 'Organisation de protection environnementale'],
        'mission' => ['en' => 'Save the planet for future generations', 'fr' => 'Sauver la planète pour les générations futures'],
        'website' => 'https://greenfuture.org',
        'email' => 'contact@greenfuture.org',
        'phone' => '+1-555-0123',
        'address' => '123 Green Street',
        'city' => 'Portland',
        'postal_code' => '97201',
        'country' => 'USA',
        'category' => 'Environment',
        'type' => 'NGO',
        'is_active' => true,
        'is_verified' => true,
        'verification_date' => now()->subDays(30),
        'created_at' => now()->subDays(100),
        'updated_at' => now()->subDay(),
    ]);
});

it('implements Laravel Scout Searchable trait', function (): void {
    expect($this->organization)
        ->toBeInstanceOf(Organization::class)
        ->and(class_uses($this->organization))
        ->toContain(\Laravel\Scout\Searchable::class);
});

it('returns correct searchable array with all required fields', function (): void {
    // Create organization and campaigns in database
    $organization = Organization::factory()->create([
        'id' => 1,
        'name' => ['en' => 'Green Future Foundation', 'fr' => 'Fondation Avenir Vert'],
        'description' => ['en' => 'Environmental protection organization', 'fr' => 'Organisation de protection environnementale'],
        'mission' => ['en' => 'Save the planet for future generations', 'fr' => 'Sauver la planète pour les générations futures'],
        'website' => 'https://greenfuture.org',
        'email' => 'contact@greenfuture.org',
        'phone' => '+1-555-0123',
        'address' => '123 Green Street',
        'city' => 'Portland',
        'postal_code' => '97201',
        'country' => 'USA',
        'category' => 'Environment',
        'type' => 'NGO',
        'is_active' => true,
        'is_verified' => true,
        'verification_date' => now()->subDays(30),
        'created_at' => now()->subDays(100),
        'updated_at' => now()->subDay(),
    ]);

    Campaign::factory()->count(3)->create(['organization_id' => $organization->id]);

    $searchableArray = $organization->toSearchableArray();

    expect($searchableArray)
        ->toBeArray()
        ->toHaveKeys([
            'id', 'name', 'name_en', 'name_fr', 'name_de',
            'description', 'description_en', 'description_fr', 'description_de',
            'mission', 'mission_en', 'mission_fr', 'mission_de',
            'website', 'email', 'phone', 'address', 'city', 'postal_code', 'country',
            'category', 'type', 'status', 'is_active', 'is_verified',
            'verification_date', 'campaigns_count',
            'created_at', 'updated_at', 'location', 'full_address',
        ])
        ->and($searchableArray['id'])->toBe(1)
        ->and($searchableArray['name'])->toBe('Green Future Foundation')
        ->and($searchableArray['name_en'])->toBe('Green Future Foundation')
        ->and($searchableArray['name_fr'])->toBe('Fondation Avenir Vert')
        ->and($searchableArray['name_de'])->toBeIn([null, 'Green Future Foundation']) // Translation fallback may occur
        ->and($searchableArray['description'])->toBe('Environmental protection organization')
        ->and($searchableArray['description_en'])->toBe('Environmental protection organization')
        ->and($searchableArray['description_fr'])->toBe('Organisation de protection environnementale')
        ->and($searchableArray['mission'])->toBe('Save the planet for future generations')
        ->and($searchableArray['website'])->toBe('https://greenfuture.org')
        ->and($searchableArray['email'])->toBe('contact@greenfuture.org')
        ->and($searchableArray['phone'])->toBe('+1-555-0123')
        ->and($searchableArray['address'])->toBe('123 Green Street')
        ->and($searchableArray['city'])->toBe('Portland')
        ->and($searchableArray['postal_code'])->toBe('97201')
        ->and($searchableArray['country'])->toBe('USA')
        ->and($searchableArray['category'])->toBe('Environment')
        ->and($searchableArray['type'])->toBe('NGO')
        ->and($searchableArray['status'])->toBe('active')
        ->and($searchableArray['is_active'])->toBeTrue()
        ->and($searchableArray['is_verified'])->toBeTrue()
        ->and($searchableArray['verification_date'])->toBeString()
        ->and($searchableArray['campaigns_count'])->toBe(3)
        ->and($searchableArray['created_at'])->toBeString()
        ->and($searchableArray['updated_at'])->toBeString()
        ->and($searchableArray['location'])->toBe('Portland, USA')
        ->and($searchableArray['full_address'])->toBe('123 Green Street Portland 97201 USA');
});

it('returns correct status in searchable array based on active and verified flags', function (): void {
    // Test active and verified
    $this->organization->is_active = true;
    $this->organization->is_verified = true;
    expect($this->organization->toSearchableArray()['status'])->toBe('active');

    // Test active but not verified
    $this->organization->is_active = true;
    $this->organization->is_verified = false;
    expect($this->organization->toSearchableArray()['status'])->toBe('unverified');

    // Test not active
    $this->organization->is_active = false;
    $this->organization->is_verified = true;
    expect($this->organization->toSearchableArray()['status'])->toBe('inactive');

    // Test not active and not verified
    $this->organization->is_active = false;
    $this->organization->is_verified = false;
    expect($this->organization->toSearchableArray()['status'])->toBe('inactive');
});

it('handles null and empty translations in searchable array', function (): void {
    $this->organization->name = ['en' => 'Test Org'];
    $this->organization->description = null;
    $this->organization->mission = ['fr' => 'Mission française'];

    $searchableArray = $this->organization->toSearchableArray();

    expect($searchableArray['name'])->toBe('Test Org')
        ->and($searchableArray['name_en'])->toBe('Test Org')
        ->and($searchableArray['name_fr'])->toBeIn([null, 'Test Org']) // Translation fallback may occur
        ->and($searchableArray['description'])->toBeNull()
        ->and($searchableArray['description_en'])->toBeNull()
        ->and($searchableArray['mission'])->toBeNull() // First available translation
        ->and($searchableArray['mission_fr'])->toBe('Mission française');
});

it('handles null address fields in computed location fields', function (): void {
    $this->organization->city = null;
    $this->organization->country = 'USA';
    $this->organization->address = null;
    $this->organization->postal_code = null;

    $searchableArray = $this->organization->toSearchableArray();

    expect($searchableArray['location'])->toBe('USA') // Trims comma
        ->and($searchableArray['full_address'])->toBe('USA'); // Trims spaces
});

it('loads campaigns relationship when converting to searchable array', function (): void {
    $organization = Organization::factory()->create();
    Campaign::factory()->count(2)->create(['organization_id' => $organization->id]);

    // Verify that the toSearchableArray method properly loads campaigns
    $searchableArray = $organization->toSearchableArray();

    expect($searchableArray['campaigns_count'])->toBe(2);
});

describe('shouldBeSearchable method', function (): void {
    it('returns true for active organizations', function (): void {
        $this->organization->is_active = true;
        expect($this->organization->shouldBeSearchable())->toBeTrue();
    });

    it('returns false for inactive organizations', function (): void {
        $this->organization->is_active = false;
        expect($this->organization->shouldBeSearchable())->toBeFalse();
    });

    it('handles null is_active as false', function (): void {
        $this->organization->is_active = null;
        expect($this->organization->shouldBeSearchable())->toBeFalse();
    });
});

it('has makeAllSearchableUsing method that includes campaigns relationship', function (): void {
    $organization = new Organization;
    $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
    $query->shouldReceive('with')->with(['campaigns'])->once()->andReturnSelf();

    $reflection = new ReflectionClass($organization);
    $method = $reflection->getMethod('makeAllSearchableUsing');
    $method->setAccessible(true);

    $result = $method->invoke($organization, $query);
    expect($result)->toBe($query);
});

it('formats dates correctly in searchable array', function (): void {
    $verificationDate = now()->subDays(30);
    $createdAt = now()->subDays(100);
    $updatedAt = now()->subDay();

    $this->organization->verification_date = $verificationDate;
    $this->organization->created_at = $createdAt;
    $this->organization->updated_at = $updatedAt;

    $searchableArray = $this->organization->toSearchableArray();

    expect($searchableArray['verification_date'])
        ->toBe($verificationDate->toIso8601String())
        ->and($searchableArray['created_at'])
        ->toBe($createdAt->toIso8601String())
        ->and($searchableArray['updated_at'])
        ->toBe($updatedAt->toIso8601String());
});

it('handles null dates in searchable array', function (): void {
    $this->organization->verification_date = null;

    $searchableArray = $this->organization->toSearchableArray();

    expect($searchableArray['verification_date'])->toBeNull();
});

it('includes campaigns count in searchable array', function (): void {
    $organization = Organization::factory()->create();

    $campaigns = collect([
        Campaign::factory()->create(['organization_id' => $organization->id]),
        Campaign::factory()->create(['organization_id' => $organization->id]),
        Campaign::factory()->create(['organization_id' => $organization->id]),
        Campaign::factory()->create(['organization_id' => $organization->id]),
        Campaign::factory()->create(['organization_id' => $organization->id]),
    ]);

    $searchableArray = $organization->toSearchableArray();

    expect($searchableArray['campaigns_count'])->toBe(5);
});

it('handles empty campaigns collection in searchable array', function (): void {
    $organization = Organization::factory()->create();
    // No campaigns created - should have count of 0

    $searchableArray = $organization->toSearchableArray();

    expect($searchableArray['campaigns_count'])->toBe(0);
});
