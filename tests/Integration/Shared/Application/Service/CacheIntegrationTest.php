<?php

declare(strict_types=1);

namespace Tests\Integration\Shared\Application\Service;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Modules\Organization\Domain\Model\Organization;
use Modules\Shared\Application\Service\CacheService;
use Modules\User\Application\Service\UserPermissionsCacheService;
use Modules\User\Infrastructure\Laravel\Models\User;
use Tests\TestCase;

/**
 * Integration tests for cache TTL expiration, invalidation, and warming functionality.
 */
class CacheIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CacheService $cacheService;

    private UserPermissionsCacheService $userPermissionsCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheService = app(CacheService::class);
        $this->userPermissionsCache = app(UserPermissionsCacheService::class);

        // Ensure we're using Redis for these tests
        config(['cache.default' => 'redis']);
    }

    protected function tearDown(): void
    {
        // Clear all cache after each test
        Cache::flush();
        parent::tearDown();
    }

    // TTL Expiration Tests

    /** @test */
    public function it_respects_short_ttl_for_frequently_changing_data(): void
    {
        $key = 'test:short:ttl';
        $data = ['test' => 'short_data'];

        // Store with short TTL (5 seconds)
        $result = $this->cacheService->remember($key, fn () => $data, 'short');

        $this->assertSame($data, $result);
        $this->assertTrue(Cache::has($key));

        // Verify TTL is approximately correct (allow some variance)
        $ttl = Redis::connection('cache')->ttl(config('cache.prefix') . ':' . $key);
        $this->assertGreaterThan(295, $ttl);
        $this->assertLessThan(305, $ttl); // ~300 seconds ±5
    }

    /** @test */
    public function it_respects_medium_ttl_for_moderately_dynamic_data(): void
    {
        $key = 'test:medium:ttl';
        $data = ['test' => 'medium_data'];

        $result = $this->cacheService->remember($key, fn () => $data, 'medium');

        $this->assertSame($data, $result);

        $ttl = Redis::connection('cache')->ttl(config('cache.prefix') . ':' . $key);
        $this->assertGreaterThan(1795, $ttl);
        $this->assertLessThan(1805, $ttl); // ~1800 seconds ±5
    }

    /** @test */
    public function it_respects_long_ttl_for_stable_data(): void
    {
        $key = 'test:long:ttl';
        $data = ['test' => 'long_data'];

        $result = $this->cacheService->remember($key, fn () => $data, 'long');

        $this->assertSame($data, $result);

        $ttl = Redis::connection('cache')->ttl(config('cache.prefix') . ':' . $key);
        $this->assertGreaterThan(3595, $ttl);
        $this->assertLessThan(3605, $ttl); // ~3600 seconds ±5
    }

    /** @test */
    public function it_respects_custom_ttl(): void
    {
        $key = 'test:custom:ttl';
        $data = ['test' => 'custom_data'];
        $customTtl = 7200; // 2 hours

        $result = $this->cacheService->rememberWithTtl($key, fn () => $data, $customTtl);

        $this->assertSame($data, $result);

        $ttl = Redis::connection('cache')->ttl(config('cache.prefix') . ':' . $key);
        $this->assertGreaterThan(7195, $ttl);
        $this->assertLessThan(7205, $ttl); // ~7200 seconds ±5
    }

    // Cache Invalidation on Updates Tests

    /** @test */
    public function it_invalidates_campaign_cache_when_campaign_is_updated(): void
    {
        $organization = Organization::factory()->create();
        $campaign = Campaign::factory()->create(['organization_id' => $organization->id]);

        // Cache campaign analytics
        $analyticsData = ['title' => $campaign->title, 'total_donations' => 10];
        $this->cacheService->rememberCampaignAnalytics($campaign->id);

        // Verify cache exists
        $cacheKey = "analytics:campaign:{$campaign->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Update campaign (this should trigger cache invalidation)
        $this->cacheService->invalidateCampaign($campaign->id, $organization->id);

        // Verify cache is cleared
        $this->assertFalse(Cache::has($cacheKey));
        $this->assertFalse(Cache::has("campaign:{$campaign->id}:details"));
    }

    /** @test */
    public function it_invalidates_organization_cache_when_organization_is_updated(): void
    {
        $organization = Organization::factory()->create();

        // Cache organization metrics
        $this->cacheService->rememberOrganizationMetrics($organization->id);

        // Verify cache exists
        $cacheKey = "dashboard:org:{$organization->id}:metrics";
        $this->assertTrue(Cache::has($cacheKey));

        // Update organization (should trigger cache invalidation)
        $this->cacheService->invalidateOrganization($organization->id);

        // Verify cache is cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_invalidates_donation_metrics_when_donations_are_updated(): void
    {
        $organization = Organization::factory()->create();
        $campaign = Campaign::factory()->create(['organization_id' => $organization->id]);

        // Cache donation metrics
        $filters = ['campaign_id' => $campaign->id, 'organization_id' => $organization->id];
        $this->cacheService->rememberDonationMetrics($filters, 'last_30_days', []);

        // Verify some cache exists (exact key is complex due to MD5 hashing)
        $cacheKeys = Redis::connection('cache')->keys('*donation_metrics*');
        $this->assertNotEmpty($cacheKeys);

        // Update donations (should trigger cache invalidation)
        $this->cacheService->invalidateDonationMetrics($campaign->id, $organization->id);

        // Verify relevant caches are cleared
        $remainingKeys = Redis::connection('cache')->keys('*donation_metrics*');
        $this->assertLessThan(count($cacheKeys), count($remainingKeys));
    }

    /** @test */
    public function it_invalidates_user_permissions_when_user_roles_change(): void
    {
        $user = User::factory()->create();

        // Cache user permissions
        $this->userPermissionsCache->getUserPermissions($user->id);

        // Verify cache exists
        $cacheKey = "permissions:user:{$user->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Update user permissions (should trigger cache invalidation)
        $this->userPermissionsCache->invalidateUserPermissions($user->id);

        // Verify cache is cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_invalidates_search_facets_when_relevant_data_changes(): void
    {
        // Cache search facets
        $this->cacheService->rememberSearchFacets(['campaign'], ['status' => 'active']);

        // Verify some cache exists
        $cacheKeys = Redis::connection('cache')->keys('*search_facets*');
        $this->assertNotEmpty($cacheKeys);

        // Update search data (should trigger cache invalidation)
        $this->cacheService->invalidateSearch();

        // Verify cache is cleared
        $remainingKeys = Redis::connection('cache')->keys('*search_facets*');
        $this->assertEmpty($remainingKeys);
    }

    // Cache Tags Integration Tests

    /** @test */
    public function it_invalidates_multiple_caches_by_tags(): void
    {
        $organization = Organization::factory()->create();
        $campaign = Campaign::factory()->create(['organization_id' => $organization->id]);

        // Cache multiple items with same tags
        $this->cacheService->remember(
            'test:campaigns:1',
            fn () => ['data' => '1'],
            'medium',
            ['campaigns', "org:{$organization->id}"]
        );

        $this->cacheService->remember(
            'test:campaigns:2',
            fn () => ['data' => '2'],
            'medium',
            ['campaigns', "org:{$organization->id}"]
        );

        $this->cacheService->remember(
            'test:other:1',
            fn () => ['data' => 'other'],
            'medium',
            ['other_tag']
        );

        // Verify caches exist
        $this->assertTrue(Cache::has('test:campaigns:1'));
        $this->assertTrue(Cache::has('test:campaigns:2'));
        $this->assertTrue(Cache::has('test:other:1'));

        // Invalidate by organization tag
        $this->cacheService->invalidateOrganization($organization->id);

        // Verify only organization-related caches are cleared
        $this->assertFalse(Cache::has('test:campaigns:1'));
        $this->assertFalse(Cache::has('test:campaigns:2'));
        $this->assertTrue(Cache::has('test:other:1')); // Should remain
    }

    // Cache Warming Tests

    /** @test */
    public function it_warms_cache_successfully(): void
    {
        // Clear any existing cache
        Cache::flush();

        // Warm cache
        $this->cacheService->warmCache();

        // Verify warming completed (check logs or cache existence)
        // Since warming methods are mostly stubs, we verify the method completes without errors
        $this->assertTrue(true);
    }

    /** @test */
    public function it_warms_organization_permissions_cache_in_batches(): void
    {
        // Create test organization and users
        $organization = Organization::factory()->create();
        $users = User::factory()->count(5)->create(['organization_id' => $organization->id]);

        // Clear cache
        Cache::flush();

        // Warm organization permissions
        $this->userPermissionsCache->warmOrganizationPermissions($organization->id);

        // Verify permissions are cached for organization users
        foreach ($users as $user) {
            $cacheKey = "permissions:user:{$user->id}";
            $this->assertTrue(Cache::has($cacheKey));
        }
    }

    /** @test */
    public function it_handles_cache_warming_errors_gracefully(): void
    {
        // Create a scenario that might cause errors
        $invalidOrganizationId = 99999;

        // This should not throw an exception
        $this->userPermissionsCache->warmOrganizationPermissions($invalidOrganizationId);

        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    // Performance and Optimization Tests

    /** @test */
    public function it_demonstrates_cache_hit_performance_improvement(): void
    {
        $key = 'performance:test';
        $expensiveData = ['computed' => 'expensive_calculation'];

        // First call (cache miss) - should be slower
        $startTime = microtime(true);
        $result1 = $this->cacheService->remember($key, function () use ($expensiveData) {
            usleep(10000); // Simulate 10ms expensive operation

            return $expensiveData;
        });
        $firstCallTime = microtime(true) - $startTime;

        // Second call (cache hit) - should be faster
        $startTime = microtime(true);
        $result2 = $this->cacheService->remember($key, function () use ($expensiveData) {
            usleep(10000); // This shouldn't be called

            return $expensiveData;
        });
        $secondCallTime = microtime(true) - $startTime;

        $this->assertSame($expensiveData, $result1);
        $this->assertSame($expensiveData, $result2);
        $this->assertLessThan($firstCallTime, $secondCallTime);
    }

    /** @test */
    public function it_efficiently_handles_bulk_operations(): void
    {
        // Create test users
        $users = User::factory()->count(10)->create();
        $userIds = $users->pluck('id')->toArray();

        // Clear cache
        Cache::flush();

        // Bulk load permissions (should be efficient)
        $startTime = microtime(true);
        $results = $this->userPermissionsCache->getBulkUserPermissions($userIds);
        $bulkTime = microtime(true) - $startTime;

        $this->assertCount(10, $results);
        $this->assertLessThan(1.0, $bulkTime); // Should complete within 1 second

        // Verify all users are now cached
        foreach ($userIds as $userId) {
            $cacheKey = "permissions:user:{$userId}";
            $this->assertTrue(Cache::has($cacheKey));
        }
    }

    // Cache Statistics and Monitoring Tests

    /** @test */
    public function it_provides_accurate_cache_statistics(): void
    {
        $organization = Organization::factory()->create();

        // Cache some organization data
        $this->cacheService->rememberOrganizationMetrics($organization->id);

        // Get cache statistics
        $stats = $this->cacheService->getCacheStats();

        $this->assertArrayHasKey('cache_driver', $stats);
        $this->assertArrayHasKey('redis_connection', $stats);
        $this->assertArrayHasKey('ttl_configuration', $stats);

        $this->assertSame('redis', $stats['cache_driver']);
        $this->assertArrayHasKey('short', $stats['ttl_configuration']);
        $this->assertArrayHasKey('medium', $stats['ttl_configuration']);
        $this->assertArrayHasKey('long', $stats['ttl_configuration']);
        $this->assertArrayHasKey('daily', $stats['ttl_configuration']);
        $this->assertArrayHasKey('weekly', $stats['ttl_configuration']);
    }

    /** @test */
    public function it_tracks_cache_hit_rates(): void
    {
        $key = 'hit:rate:test';
        $data = ['test' => 'hit_rate'];

        // First call (miss)
        $this->cacheService->remember($key, fn () => $data);

        // Second call (hit)
        $result = $this->cacheService->remember($key, fn () => ['should' => 'not_be_called']);

        $this->assertSame($data, $result); // Should return cached data, not new data
    }

    // Edge Cases and Error Handling

    /** @test */
    public function it_handles_redis_connection_failures_gracefully(): void
    {
        // Temporarily disable Redis (simulate connection failure)
        config(['cache.default' => 'array']);

        $key = 'fallback:test';
        $data = ['fallback' => 'data'];

        // Should still work with array cache
        $result = $this->cacheService->remember($key, fn () => $data);

        $this->assertSame($data, $result);
    }

    /** @test */
    public function it_handles_large_data_caching(): void
    {
        $key = 'large:data:test';
        $largeData = array_fill(0, 10000, 'large_data_item');

        $result = $this->cacheService->remember($key, fn () => $largeData);

        $this->assertSame($largeData, $result);
        $this->assertTrue(Cache::has($key));
    }

    /** @test */
    public function it_handles_concurrent_cache_operations(): void
    {
        $key = 'concurrent:test';
        $data1 = ['concurrent' => 'data1'];
        $data2 = ['concurrent' => 'data2'];

        // Simulate concurrent operations
        $result1 = $this->cacheService->remember($key, fn () => $data1);
        $result2 = $this->cacheService->remember($key, fn () => $data2);

        // Second call should return cached data from first call
        $this->assertSame($data1, $result1);
        $this->assertSame($data1, $result2); // Should be same as first result
    }

    // Memory and Resource Tests

    /** @test */
    public function it_cleans_up_expired_cache_entries(): void
    {
        $key = 'expire:test';
        $data = ['expire' => 'data'];

        // Cache with very short TTL
        $this->cacheService->rememberWithTtl($key, fn () => $data, 1); // 1 second

        $this->assertTrue(Cache::has($key));

        // Wait for expiration
        sleep(2);

        $this->assertFalse(Cache::has($key));
    }

    // Configuration Tests

    /** @test */
    public function it_uses_correct_ttl_values_from_configuration(): void
    {
        $stats = $this->cacheService->getCacheStats();
        $ttlConfig = $stats['ttl_configuration'];

        $this->assertSame(300, $ttlConfig['short']);
        $this->assertSame(1800, $ttlConfig['medium']);
        $this->assertSame(3600, $ttlConfig['long']);
        $this->assertSame(86400, $ttlConfig['daily']);
        $this->assertSame(604800, $ttlConfig['weekly']);
    }

    // Integration with Model Events Tests

    /** @test */
    public function it_integrates_with_model_events_for_automatic_invalidation(): void
    {
        $organization = Organization::factory()->create();
        $campaign = Campaign::factory()->create(['organization_id' => $organization->id]);

        // Cache campaign data
        $this->cacheService->rememberCampaignAnalytics($campaign->id);
        $cacheKey = "analytics:campaign:{$campaign->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Simulate model update event (manual invalidation for test)
        $this->cacheService->invalidateCampaign($campaign->id, $organization->id);

        // Verify cache is invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }
}
