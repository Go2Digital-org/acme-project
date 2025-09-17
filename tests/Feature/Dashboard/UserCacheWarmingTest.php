<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create an organization first
    $this->organization = Organization::factory()->create(['id' => 1]);

    // Seed roles and permissions required for testing
    $this->artisan('db:seed', ['--class' => \Modules\Shared\Infrastructure\Laravel\Seeder\TenantRolesAndPermissionsSeeder::class]);

    $this->user = User::factory()->create(['organization_id' => 1]);

    // Ensure user has the required role for API Platform security
    $this->user->assignRole('employee');

    Cache::flush();
    Queue::fake();

    // Set app locale for testing
    app()->setLocale('en');
});

test('cache status endpoint returns correct status', function (): void {
    // Act: Check cache status through API
    $response = $this->actingAs($this->user)
        ->getJson('/api/dashboard/cache-status');

    // Assert: Returns cache miss status
    $response->assertOk();
    $response->assertJson([
        'status' => 'miss',
        'ready' => false,
    ]);
});

test('data endpoint returns 200 when cache is cold', function (): void {
    // Act: Try to get data with cold cache through API
    $response = $this->actingAs($this->user)
        ->getJson('/api/dashboard/data');

    // Assert: Returns 200 OK (API Platform behavior)
    $response->assertStatus(200);
});

test('cache invalidation clears user cache', function (): void {
    // Arrange: Set some cache data
    $cacheKey = "user:{$this->user->id}:statistics";
    Cache::put($cacheKey, ['test' => 'data'], 1800);

    // Act: Invalidate cache through API Platform endpoint
    $response = $this->actingAs($this->user)
        ->deleteJson('/api/dashboard/cache');

    // Assert: Cache is cleared - DELETE operations return 204 No Content
    $response->assertStatus(204);
    expect(Cache::has($cacheKey))->toBeFalse();
});
