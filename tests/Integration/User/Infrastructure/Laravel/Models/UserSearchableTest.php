<?php

declare(strict_types=1);

use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

beforeEach(function (): void {
    $this->organization = Organization::factory()->make([
        'id' => 1,
        'name' => ['en' => 'Test Organization'],
    ]);

    $this->user = User::factory()->make([
        'id' => 1,
        'name' => 'John Doe',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'status' => 'active',
        'job_title' => 'Software Engineer',
        'department' => 'IT',
        'manager_email' => 'manager@example.com',
        'phone' => '+1-555-0123',
        'address' => '123 Main St',
        'user_id' => 'emp001',
        'hire_date' => now()->subYears(2),
        'preferred_language' => 'en',
        'timezone' => 'America/New_York',
        'organization_id' => 1,
        'mfa_enabled' => true,
        'email_verified_at' => now()->subDays(30),
        'last_login_at' => now()->subHours(2),
        'created_at' => now()->subDays(100),
        'updated_at' => now()->subDay(),
    ]);
});

it('implements Laravel Scout Searchable trait', function (): void {
    expect($this->user)
        ->toBeInstanceOf(User::class)
        ->and(class_uses($this->user))
        ->toContain(\Laravel\Scout\Searchable::class);
});

it('returns correct searchable array with all required fields', function (): void {
    $this->user->setRelation('organization', $this->organization);

    $searchableArray = $this->user->toSearchableArray();

    expect($searchableArray)
        ->toBeArray()
        ->toHaveKeys([
            'id', 'name', 'first_name', 'last_name', 'full_name', 'email',
            'status', 'role', 'job_title', 'department', 'manager_email',
            'phone', 'address', 'user_id', 'hire_date', 'preferred_language',
            'timezone', 'organization_id', 'organization_name', 'is_active',
            'account_locked', 'email_verified', 'mfa_enabled',
            'email_verified_at', 'last_login_at', 'created_at', 'updated_at',
        ])
        ->and($searchableArray['id'])->toBe(1)
        ->and($searchableArray['name'])->toBe('John Doe')
        ->and($searchableArray['first_name'])->toBe('John')
        ->and($searchableArray['last_name'])->toBe('Doe')
        ->and($searchableArray['full_name'])->toBe('John Doe')
        ->and($searchableArray['email'])->toBe('john.doe@example.com')
        ->and($searchableArray['status'])->toBe('active')
        ->and($searchableArray['job_title'])->toBe('Software Engineer')
        ->and($searchableArray['department'])->toBe('IT')
        ->and($searchableArray['manager_email'])->toBe('manager@example.com')
        ->and($searchableArray['phone'])->toBe('+1-555-0123')
        ->and($searchableArray['address'])->toBe('123 Main St')
        ->and($searchableArray['user_id'])->toBe('emp001')
        ->and($searchableArray['preferred_language'])->toBe('en')
        ->and($searchableArray['timezone'])->toBe('America/New_York')
        ->and($searchableArray['organization_id'])->toBe(1)
        ->and($searchableArray['organization_name'])->toBe('Test Organization')
        ->and($searchableArray['mfa_enabled'])->toBeTrue()
        ->and($searchableArray['email_verified'])->toBeTrue()
        ->and($searchableArray['email_verified_at'])->toBeString()
        ->and($searchableArray['last_login_at'])->toBeString()
        ->and($searchableArray['created_at'])->toBeString()
        ->and($searchableArray['updated_at'])->toBeString();
});

it('handles null organization in searchable array', function (): void {
    $this->user->setRelation('organization', null);

    $searchableArray = $this->user->toSearchableArray();

    expect($searchableArray['organization_name'])->toBeNull();
});

it('formats dates correctly in searchable array', function (): void {
    $emailVerifiedAt = now()->subDays(30);
    $lastLoginAt = now()->subHours(2);
    $createdAt = now()->subDays(100);
    $updatedAt = now()->subDay();

    $this->user->email_verified_at = $emailVerifiedAt;
    $this->user->last_login_at = $lastLoginAt;
    $this->user->created_at = $createdAt;
    $this->user->updated_at = $updatedAt;

    $searchableArray = $this->user->toSearchableArray();

    expect($searchableArray['email_verified_at'])
        ->toBe($emailVerifiedAt->toIso8601String())
        ->and($searchableArray['last_login_at'])
        ->toBe($lastLoginAt->toIso8601String())
        ->and($searchableArray['created_at'])
        ->toBe($createdAt->toIso8601String())
        ->and($searchableArray['updated_at'])
        ->toBe($updatedAt->toIso8601String());
});

it('handles null dates in searchable array', function (): void {
    $this->user->email_verified_at = null;
    $this->user->last_login_at = null;

    $searchableArray = $this->user->toSearchableArray();

    expect($searchableArray['email_verified_at'])->toBeNull()
        ->and($searchableArray['last_login_at'])->toBeNull();
});

it('correctly sets email_verified flag based on email_verified_at', function (): void {
    // Test with verified email
    $this->user->email_verified_at = now();
    expect($this->user->toSearchableArray()['email_verified'])->toBeTrue();

    // Test with unverified email
    $this->user->email_verified_at = null;
    expect($this->user->toSearchableArray()['email_verified'])->toBeFalse();
});

it('loads organization relationship when converting to searchable array', function (): void {
    $user = User::factory()->create();

    // Ensure organization is not loaded initially
    expect($user->relationLoaded('organization'))->toBeFalse();

    // Call toSearchableArray - should work without errors and handle organization
    $searchableArray = $user->toSearchableArray();

    // Verify it includes organization data and that the method works
    expect($searchableArray)->toHaveKey('organization_name')
        ->and($searchableArray)->toHaveKey('organization_id');
});

describe('shouldBeSearchable method', function (): void {
    it('returns true for active users with non-anonymized data', function (): void {
        $this->user->status = 'active';

        // Mock isPersonalDataAnonymized to return false
        $spy = Mockery::spy($this->user);
        $spy->shouldReceive('isPersonalDataAnonymized')->andReturn(false);

        expect($spy->shouldBeSearchable())->toBeTrue();
    });

    it('returns false for inactive users', function (): void {
        $this->user->status = 'inactive';

        expect($this->user->shouldBeSearchable())->toBeFalse();
    });

    it('returns false for active users with anonymized data', function (): void {
        $this->user->status = 'active';
        $this->user->personal_data_anonymized = true;

        expect($this->user->shouldBeSearchable())->toBeFalse();
    });

    it('returns false for pending status', function (): void {
        $this->user->status = 'pending';

        expect($this->user->shouldBeSearchable())->toBeFalse();
    });

    it('returns false for suspended status', function (): void {
        $this->user->status = 'suspended';

        expect($this->user->shouldBeSearchable())->toBeFalse();
    });
});

it('has makeAllSearchableUsing method that includes organization relationship', function (): void {
    $user = new User;
    $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
    $query->shouldReceive('with')->with(['organization'])->once()->andReturnSelf();

    $reflection = new ReflectionClass($user);
    $method = $reflection->getMethod('makeAllSearchableUsing');
    $method->setAccessible(true);

    $result = $method->invoke($user, $query);
    expect($result)->toBe($query);
});

it('includes correct role information in searchable array', function (): void {
    // Set user role to admin
    $this->user->role = 'admin';
    $this->user->setRelation('organization', $this->organization);

    $searchableArray = $this->user->toSearchableArray();

    expect($searchableArray['role'])->toBe('admin');
});

it('includes correct activity status in searchable array', function (): void {
    $this->user->setRelation('organization', $this->organization);

    // Mock isActive method
    $spy = Mockery::spy($this->user);
    $spy->shouldReceive('isActive')->andReturn(true);
    $spy->shouldReceive('isAccountLocked')->andReturn(false);
    $spy->shouldReceive('load')->andReturnSelf();
    $spy->shouldReceive('getAttribute')->withArgs(['organization'])->andReturn($this->organization);
    $spy->shouldReceive('getAttribute')->andReturnUsing(fn ($key) => $this->user->$key);

    $searchableArray = $spy->toSearchableArray();

    expect($searchableArray['is_active'])->toBeTrue()
        ->and($searchableArray['account_locked'])->toBeFalse();
});

it('handles boolean mfa_enabled field correctly', function (): void {
    $this->user->mfa_enabled = true;
    expect($this->user->toSearchableArray()['mfa_enabled'])->toBeTrue();

    $this->user->mfa_enabled = false;
    expect($this->user->toSearchableArray()['mfa_enabled'])->toBeFalse();

    $this->user->mfa_enabled = null;
    expect($this->user->toSearchableArray()['mfa_enabled'])->toBeFalse();
});
