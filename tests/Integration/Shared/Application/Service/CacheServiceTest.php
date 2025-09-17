<?php

declare(strict_types=1);

namespace Tests\Integration\Shared\Application\Service;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Modules\Shared\Application\Service\CacheService;
use Psr\Log\LoggerInterface;
use Tests\Integration\IntegrationTestCase;

/**
 * Comprehensive tests for CacheService functionality including TTL, tags, and invalidation.
 */
class CacheServiceTest extends IntegrationTestCase
{
    private CacheService $cacheService;

    private CacheRepository $mockCache;

    private LoggerInterface $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCache = Mockery::mock(CacheRepository::class);
        $this->mockLogger = Mockery::mock(LoggerInterface::class);
        $this->cacheService = new CacheService($this->mockCache, $this->mockLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // TTL and Basic Caching Tests

    /** @test */
    public function it_remembers_data_with_default_medium_ttl(): void
    {
        $key = 'test:key';
        $data = ['test' => 'data'];
        $callback = fn () => $data;

        $this->mockCache->shouldReceive('remember')
            ->once()
            ->with($key, 1800, $callback) // Medium TTL = 1800 seconds
            ->andReturn($data);

        $this->mockLogger->shouldReceive('debug')
            ->once()
            ->with('Cache operation completed', Mockery::type('array'));

        $result = $this->cacheService->remember($key, $callback);

        $this->assertSame($data, $result);
    }

    /** @test */
    public function it_remembers_data_with_short_ttl_when_specified(): void
    {
        $key = 'test:key';
        $data = ['test' => 'data'];
        $callback = fn () => $data;

        $this->mockCache->shouldReceive('remember')
            ->once()
            ->with($key, 300, $callback) // Short TTL = 300 seconds
            ->andReturn($data);

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->remember($key, $callback, 'short');

        $this->assertSame($data, $result);
    }

    /** @test */
    public function it_remembers_data_with_long_ttl_when_specified(): void
    {
        $key = 'test:key';
        $data = ['test' => 'data'];
        $callback = fn () => $data;

        $this->mockCache->shouldReceive('remember')
            ->once()
            ->with($key, 3600, $callback) // Long TTL = 3600 seconds
            ->andReturn($data);

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->remember($key, $callback, 'long');

        $this->assertSame($data, $result);
    }

    /** @test */
    public function it_remembers_data_with_daily_ttl_when_specified(): void
    {
        $key = 'test:key';
        $data = ['test' => 'data'];
        $callback = fn () => $data;

        $this->mockCache->shouldReceive('remember')
            ->once()
            ->with($key, 86400, $callback) // Daily TTL = 86400 seconds
            ->andReturn($data);

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->remember($key, $callback, 'daily');

        $this->assertSame($data, $result);
    }

    /** @test */
    public function it_remembers_data_with_weekly_ttl_when_specified(): void
    {
        $key = 'test:key';
        $data = ['test' => 'data'];
        $callback = fn () => $data;

        $this->mockCache->shouldReceive('remember')
            ->once()
            ->with($key, 604800, $callback) // Weekly TTL = 604800 seconds
            ->andReturn($data);

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->remember($key, $callback, 'weekly');

        $this->assertSame($data, $result);
    }

    /** @test */
    public function it_remembers_data_with_custom_ttl(): void
    {
        $key = 'test:key';
        $data = ['test' => 'data'];
        $callback = fn () => $data;
        $customTtl = 7200;

        $this->mockCache->shouldReceive('remember')
            ->once()
            ->with($key, $customTtl, $callback)
            ->andReturn($data);

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->rememberWithTtl($key, $callback, $customTtl);

        $this->assertSame($data, $result);
    }

    // Cache Tags Tests

    /** @test */
    public function it_remembers_data_with_cache_tags(): void
    {
        Cache::shouldReceive('tags')
            ->once()
            ->with(['campaigns', 'org:1'])
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->with('test:key', 1800, Mockery::type('callable'))
            ->andReturn(['test' => 'data']);

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->remember(
            'test:key',
            fn () => ['test' => 'data'],
            'medium',
            ['campaigns', 'org:1']
        );

        $this->assertSame(['test' => 'data'], $result);
    }

    /** @test */
    public function it_remembers_data_with_tags_using_custom_ttl(): void
    {
        Cache::shouldReceive('tags')
            ->once()
            ->with(['campaigns'])
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->with('test:key', 600, Mockery::type('callable'))
            ->andReturn(['test' => 'data']);

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->rememberWithTtl(
            'test:key',
            fn () => ['test' => 'data'],
            600,
            ['campaigns']
        );

        $this->assertSame(['test' => 'data'], $result);
    }

    // Campaign Specific Tests

    /** @test */
    public function it_remembers_campaigns_with_organization_specific_caching(): void
    {
        $organizationId = 123;
        $expectedKey = "campaigns:org:{$organizationId}:type:active";
        $expectedTags = ['campaigns', "org:{$organizationId}"];

        Cache::shouldReceive('tags')
            ->once()
            ->with($expectedTags)
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->with($expectedKey, 1800, Mockery::type('callable'))
            ->andReturn(collect([]));

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->rememberCampaigns($organizationId);

        $this->assertInstanceOf(Collection::class, $result);
    }

    /** @test */
    public function it_remembers_campaigns_with_status_filter(): void
    {
        $organizationId = 123;
        $expectedKey = "campaigns:org:{$organizationId}:type:active:status:completed";

        Cache::shouldReceive('tags')->once()->andReturnSelf();
        Cache::shouldReceive('remember')->once()->andReturn(collect([]));
        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->rememberCampaigns($organizationId, 'active', 'completed');

        $this->assertInstanceOf(Collection::class, $result);
    }

    // Organization Metrics Tests

    /** @test */
    public function it_remembers_organization_metrics_with_proper_tags(): void
    {
        $organizationId = 456;
        $expectedKey = "dashboard:org:{$organizationId}:metrics";
        $expectedTags = ['dashboard', 'organizations', "org:{$organizationId}", 'campaigns', 'donations'];
        $expectedData = ['total_campaigns' => 10, 'total_donations' => 100];

        Cache::shouldReceive('tags')
            ->once()
            ->with($expectedTags)
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->with($expectedKey, 1800, Mockery::type('callable'))
            ->andReturn($expectedData);

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->rememberOrganizationMetrics($organizationId);

        $this->assertSame($expectedData, $result);
    }

    // Campaign Analytics Tests

    /** @test */
    public function it_remembers_campaign_analytics_with_short_ttl(): void
    {
        $campaignId = 789;
        $expectedKey = "analytics:campaign:{$campaignId}";
        $expectedTags = ['campaign_analytics', 'campaigns', 'donations', "campaign:{$campaignId}"];
        $expectedData = ['total_donations' => 50, 'total_amount' => 5000];

        Cache::shouldReceive('tags')
            ->once()
            ->with($expectedTags)
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->with($expectedKey, 300, Mockery::type('callable')) // Short TTL for analytics
            ->andReturn($expectedData);

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->rememberCampaignAnalytics($campaignId);

        $this->assertSame($expectedData, $result);
    }

    // Donation Metrics Tests

    /** @test */
    public function it_remembers_donation_metrics_with_complex_filters(): void
    {
        $filters = ['campaign_id' => 123, 'organization_id' => 456];
        $timeRange = 'last_30_days';
        $metrics = ['trends', 'segmentation'];

        $expectedTags = ['donation_metrics', 'donations', 'campaign:123', 'org:456'];

        Cache::shouldReceive('tags')
            ->once()
            ->with($expectedTags)
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->with(Mockery::pattern('/donation_metrics:last_30_days:filters:.+:metrics:.+/'), 300, Mockery::type('callable'))
            ->andReturn(['statistics' => [], 'trends' => []]);

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->rememberDonationMetrics($filters, $timeRange, $metrics);

        $this->assertArrayHasKey('statistics', $result);
        $this->assertArrayHasKey('trends', $result);
    }

    // Search Facets Tests

    /** @test */
    public function it_remembers_search_facets_with_long_ttl(): void
    {
        $entityTypes = ['campaign', 'organization'];
        $filters = ['status' => 'active'];

        Cache::shouldReceive('tags')
            ->once()
            ->with(['search_facets', 'campaigns', 'organizations'])
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->with(Mockery::pattern('/search_facets:types:campaign,organization:filters:.+/'), 3600, Mockery::type('callable'))
            ->andReturn(['facets' => [], 'stats' => []]);

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->rememberSearchFacets($entityTypes, $filters);

        $this->assertArrayHasKey('facets', $result);
        $this->assertArrayHasKey('stats', $result);
    }

    // User Permissions Tests

    /** @test */
    public function it_remembers_user_permissions_with_long_ttl(): void
    {
        $userId = 101;
        $expectedKey = "permissions:user:{$userId}";
        $expectedTags = ['permissions', 'users', "user:{$userId}"];
        $expectedData = ['roles' => ['admin'], 'permissions' => ['create_campaign']];

        Cache::shouldReceive('tags')
            ->once()
            ->with($expectedTags)
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->with($expectedKey, 3600, Mockery::type('callable'))
            ->andReturn($expectedData);

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->rememberUserPermissions($userId);

        $this->assertSame($expectedData, $result);
    }

    // Cache Invalidation Tests

    /** @test */
    public function it_invalidates_campaign_cache_with_tags(): void
    {
        $campaignId = 123;
        $organizationId = 456;

        Cache::shouldReceive('tags')
            ->once()
            ->with(['campaigns', "campaign:{$campaignId}", "org:{$organizationId}"])
            ->andReturnSelf();

        Cache::shouldReceive('flush')->once();

        $this->mockCache->shouldReceive('forget')
            ->with("analytics:campaign:{$campaignId}")
            ->once();

        $this->mockCache->shouldReceive('forget')
            ->with("campaign:{$campaignId}:details")
            ->once();

        $this->mockLogger->shouldReceive('info')
            ->once()
            ->with('Cache invalidated by tags', Mockery::type('array'));

        $this->cacheService->invalidateCampaign($campaignId, $organizationId);
    }

    /** @test */
    public function it_invalidates_organization_cache_with_multiple_tags(): void
    {
        $organizationId = 789;

        Cache::shouldReceive('tags')
            ->once()
            ->with(['organizations', "org:{$organizationId}", 'dashboard', 'campaigns'])
            ->andReturnSelf();

        Cache::shouldReceive('flush')->once();

        $this->mockLogger->shouldReceive('info')->once();

        $this->cacheService->invalidateOrganization($organizationId);
    }

    /** @test */
    public function it_invalidates_donation_metrics_with_optional_parameters(): void
    {
        $campaignId = 111;
        $organizationId = 222;

        Cache::shouldReceive('tags')
            ->once()
            ->with(['donations', 'donation_metrics', "campaign:{$campaignId}", "org:{$organizationId}"])
            ->andReturnSelf();

        Cache::shouldReceive('flush')->once();

        $this->mockLogger->shouldReceive('info')->once();

        $this->cacheService->invalidateDonationMetrics($campaignId, $organizationId);
    }

    /** @test */
    public function it_invalidates_user_permissions_cache(): void
    {
        $userId = 333;

        Cache::shouldReceive('tags')
            ->once()
            ->with(['permissions', "user:{$userId}"])
            ->andReturnSelf();

        Cache::shouldReceive('flush')->once();

        $this->mockCache->shouldReceive('forget')
            ->with("permissions:user:{$userId}")
            ->once();

        $this->mockLogger->shouldReceive('info')->once();

        $this->cacheService->invalidateUserPermissions($userId);
    }

    /** @test */
    public function it_invalidates_search_cache(): void
    {
        Cache::shouldReceive('tags')
            ->once()
            ->with(['search_facets'])
            ->andReturnSelf();

        Cache::shouldReceive('flush')->once();

        $this->mockLogger->shouldReceive('info')->once();

        $this->cacheService->invalidateSearch();
    }

    // Cache Warming Tests

    /** @test */
    public function it_warms_cache_successfully(): void
    {
        $this->mockLogger->shouldReceive('info')
            ->once()
            ->with('Starting cache warming process');

        $this->mockLogger->shouldReceive('debug')
            ->times(4)
            ->with(Mockery::type('string'));

        $this->mockLogger->shouldReceive('info')
            ->once()
            ->with('Cache warming completed successfully', Mockery::type('array'));

        $this->cacheService->warmCache();
    }

    /** @test */
    public function it_handles_cache_warming_errors_gracefully(): void
    {
        $this->mockLogger->shouldReceive('info')
            ->once()
            ->with('Starting cache warming process');

        // Simulate an exception during warming
        $this->mockLogger->shouldReceive('debug')
            ->andThrow(new \Exception('Cache warming failed'));

        $this->mockLogger->shouldReceive('error')
            ->once()
            ->with('Cache warming failed', Mockery::type('array'));

        $this->cacheService->warmCache();
    }

    // Cache Statistics Tests

    /** @test */
    public function it_returns_cache_statistics(): void
    {
        config(['cache.default' => 'redis']);
        config(['database.redis.cache.host' => 'localhost']);

        $stats = $this->cacheService->getCacheStats();

        $this->assertArrayHasKey('cache_driver', $stats);
        $this->assertArrayHasKey('redis_connection', $stats);
        $this->assertArrayHasKey('ttl_configuration', $stats);

        $this->assertSame('redis', $stats['cache_driver']);
        $this->assertSame('localhost', $stats['redis_connection']);
        $this->assertArrayHasKey('short', $stats['ttl_configuration']);
        $this->assertArrayHasKey('medium', $stats['ttl_configuration']);
        $this->assertArrayHasKey('long', $stats['ttl_configuration']);
        $this->assertArrayHasKey('daily', $stats['ttl_configuration']);
        $this->assertArrayHasKey('weekly', $stats['ttl_configuration']);
    }

    // Cache Hit Detection Tests

    /** @test */
    public function it_detects_cache_hits_without_tags(): void
    {
        $key = 'test:hit';

        $this->mockCache->shouldReceive('has')
            ->once()
            ->with($key)
            ->andReturn(true);

        $this->mockCache->shouldReceive('remember')
            ->once()
            ->andReturn(['cached' => 'data']);

        $this->mockLogger->shouldReceive('debug')
            ->once()
            ->with('Cache operation completed', Mockery::on(function ($context) {
                return $context['hit'] === true;
            }));

        $this->cacheService->remember($key, fn () => ['cached' => 'data']);
    }

    /** @test */
    public function it_detects_cache_misses_without_tags(): void
    {
        $key = 'test:miss';

        $this->mockCache->shouldReceive('has')
            ->once()
            ->with($key)
            ->andReturn(false);

        $this->mockCache->shouldReceive('remember')
            ->once()
            ->andReturn(['fresh' => 'data']);

        $this->mockLogger->shouldReceive('debug')
            ->once()
            ->with('Cache operation completed', Mockery::on(function ($context) {
                return $context['hit'] === false;
            }));

        $this->cacheService->remember($key, fn () => ['fresh' => 'data']);
    }

    /** @test */
    public function it_detects_cache_hits_with_tags(): void
    {
        $key = 'test:hit:tagged';
        $tags = ['test', 'tags'];

        Cache::shouldReceive('tags')
            ->once()
            ->with($tags)
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(['cached' => 'data']);

        Cache::shouldReceive('tags')
            ->once()
            ->with($tags)
            ->andReturnSelf();

        Cache::shouldReceive('has')
            ->once()
            ->with($key)
            ->andReturn(true);

        $this->mockLogger->shouldReceive('debug')
            ->once()
            ->with('Cache operation completed', Mockery::on(function ($context) {
                return $context['hit'] === true;
            }));

        $this->cacheService->remember($key, fn () => ['cached' => 'data'], 'medium', $tags);
    }

    // Edge Cases and Error Handling

    /** @test */
    public function it_handles_invalid_ttl_type_gracefully(): void
    {
        $key = 'test:invalid:ttl';
        $data = ['test' => 'data'];

        $this->mockCache->shouldReceive('remember')
            ->once()
            ->with($key, 1800, Mockery::type('callable')) // Falls back to medium
            ->andReturn($data);

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->remember($key, fn () => $data, 'invalid_type');

        $this->assertSame($data, $result);
    }

    /** @test */
    public function it_handles_cache_invalidation_errors_gracefully(): void
    {
        $campaignId = 123;

        Cache::shouldReceive('tags')
            ->once()
            ->andThrow(new \Exception('Redis connection failed'));

        $this->mockLogger->shouldReceive('warning')
            ->once()
            ->with('Cache invalidation by tags failed', Mockery::type('array'));

        // Should not throw exception
        $this->cacheService->invalidateCampaign($campaignId);
    }

    /** @test */
    public function it_handles_cache_hit_detection_errors_gracefully(): void
    {
        $key = 'test:error';

        $this->mockCache->shouldReceive('has')
            ->once()
            ->with($key)
            ->andThrow(new \Exception('Cache error'));

        $this->mockCache->shouldReceive('remember')
            ->once()
            ->andReturn(['data']);

        $this->mockLogger->shouldReceive('debug')
            ->once()
            ->with('Cache operation completed', Mockery::on(function ($context) {
                return $context['hit'] === false; // Should default to false on error
            }));

        $this->cacheService->remember($key, fn () => ['data']);
    }

    // Performance and Logging Tests

    /** @test */
    public function it_logs_execution_time_for_cache_operations(): void
    {
        $key = 'test:timing';

        $this->mockCache->shouldReceive('remember')
            ->once()
            ->andReturn(['data']);

        $this->mockCache->shouldReceive('has')
            ->once()
            ->andReturn(false);

        $this->mockLogger->shouldReceive('debug')
            ->once()
            ->with('Cache operation completed', Mockery::on(function ($context) {
                return isset($context['execution_time_ms']) &&
                       is_float($context['execution_time_ms']) &&
                       $context['execution_time_ms'] >= 0;
            }));

        $this->cacheService->remember($key, fn () => ['data']);
    }

    /** @test */
    public function it_logs_comprehensive_cache_context(): void
    {
        $key = 'test:context';
        $ttl = 600;
        $tags = ['test', 'context'];

        Cache::shouldReceive('tags')->once()->andReturnSelf();
        Cache::shouldReceive('remember')->once()->andReturn(['data']);
        Cache::shouldReceive('tags')->once()->andReturnSelf();
        Cache::shouldReceive('has')->once()->andReturn(true);

        $this->mockLogger->shouldReceive('debug')
            ->once()
            ->with('Cache operation completed', Mockery::on(function ($context) use ($key, $ttl, $tags) {
                return $context['key'] === $key &&
                       $context['ttl'] === $ttl &&
                       $context['tags'] === $tags &&
                       isset($context['execution_time_ms']) &&
                       isset($context['hit']);
            }));

        $this->cacheService->rememberWithTtl($key, fn () => ['data'], $ttl, $tags);
    }
}
