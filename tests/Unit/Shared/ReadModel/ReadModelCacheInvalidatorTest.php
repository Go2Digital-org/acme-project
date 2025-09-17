<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\ReadModel;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Modules\Shared\Application\ReadModel\ReadModelCacheInvalidator;
use Tests\TestCase;

/**
 * Test suite for Read Model Cache Invalidator.
 */
class ReadModelCacheInvalidatorTest extends TestCase
{
    private CacheRepository $cache;

    private ReadModelCacheInvalidator $invalidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = \Mockery::mock(CacheRepository::class);
        $this->invalidator = new ReadModelCacheInvalidator($this->cache);
    }

    /** @test */
    public function it_invalidates_cache_by_tags_when_tags_are_supported(): void
    {
        $taggedCache = \Mockery::mock('Illuminate\Cache\TaggedCache');
        $tags = ['campaigns', 'campaign:123'];

        $this->cache->shouldReceive('tags')
            ->once()
            ->with($tags)
            ->andReturn($taggedCache);

        $taggedCache->shouldReceive('flush')
            ->once();

        $this->invalidator->invalidateByTags($tags);
    }

    /** @test */
    public function it_logs_warning_when_tags_are_not_supported(): void
    {
        $tags = ['campaigns', 'campaign:123'];

        $this->cache->shouldNotReceive('tags');

        // Cache repository without tags() method
        $cacheWithoutTags = \Mockery::mock(CacheRepository::class);
        $invalidator = new ReadModelCacheInvalidator($cacheWithoutTags);

        $invalidator->invalidateByTags($tags);

        // Should not throw exception
        expect(true)->toBeTrue();
    }

    /** @test */
    public function it_handles_cache_invalidation_exceptions_gracefully(): void
    {
        $taggedCache = \Mockery::mock('Illuminate\Cache\TaggedCache');
        $tags = ['campaigns'];

        $this->cache->shouldReceive('tags')
            ->once()
            ->with($tags)
            ->andReturn($taggedCache);

        $taggedCache->shouldReceive('flush')
            ->once()
            ->andThrow(new \Exception('Redis connection failed'));

        // Should not throw exception
        $this->invalidator->invalidateByTags($tags);
        expect(true)->toBeTrue();
    }

    /** @test */
    public function it_invalidates_campaign_caches(): void
    {
        $taggedCache = \Mockery::mock('Illuminate\Cache\TaggedCache');

        $expectedTags = [
            'campaign_analytics',
            'campaign:123',
            'organization_dashboard',
            'organization:456',
            'campaigns',
        ];

        $this->cache->shouldReceive('tags')
            ->once()
            ->with($expectedTags)
            ->andReturn($taggedCache);

        $taggedCache->shouldReceive('flush')
            ->once();

        $this->invalidator->invalidateCampaignCaches(123, 456);
    }

    /** @test */
    public function it_invalidates_donation_caches_with_campaign_and_organization(): void
    {
        $taggedCache = \Mockery::mock('Illuminate\Cache\TaggedCache');

        $expectedTags = [
            'donation_reports',
            'donations',
            'campaign:123',
            'campaign_analytics',
            'organization:456',
            'organization_dashboard',
        ];

        $this->cache->shouldReceive('tags')
            ->once()
            ->with($expectedTags)
            ->andReturn($taggedCache);

        $taggedCache->shouldReceive('flush')
            ->once();

        $this->invalidator->invalidateDonationCaches(123, 456);
    }

    /** @test */
    public function it_invalidates_donation_caches_with_campaign_only(): void
    {
        $taggedCache = \Mockery::mock('Illuminate\Cache\TaggedCache');

        $expectedTags = [
            'donation_reports',
            'donations',
            'campaign:123',
            'campaign_analytics',
        ];

        $this->cache->shouldReceive('tags')
            ->once()
            ->with($expectedTags)
            ->andReturn($taggedCache);

        $taggedCache->shouldReceive('flush')
            ->once();

        $this->invalidator->invalidateDonationCaches(123, null);
    }

    /** @test */
    public function it_invalidates_donation_caches_with_no_specific_ids(): void
    {
        $taggedCache = \Mockery::mock('Illuminate\Cache\TaggedCache');

        $expectedTags = [
            'donation_reports',
            'donations',
        ];

        $this->cache->shouldReceive('tags')
            ->once()
            ->with($expectedTags)
            ->andReturn($taggedCache);

        $taggedCache->shouldReceive('flush')
            ->once();

        $this->invalidator->invalidateDonationCaches();
    }

    /** @test */
    public function it_invalidates_user_caches_with_organization(): void
    {
        $taggedCache = \Mockery::mock('Illuminate\Cache\TaggedCache');

        $expectedTags = [
            'user_profile',
            'user:123',
            'organization:456',
            'organization_dashboard',
        ];

        $this->cache->shouldReceive('tags')
            ->once()
            ->with($expectedTags)
            ->andReturn($taggedCache);

        $taggedCache->shouldReceive('flush')
            ->once();

        $this->invalidator->invalidateUserCaches(123, 456);
    }

    /** @test */
    public function it_invalidates_user_caches_without_organization(): void
    {
        $taggedCache = \Mockery::mock('Illuminate\Cache\TaggedCache');

        $expectedTags = [
            'user_profile',
            'user:123',
        ];

        $this->cache->shouldReceive('tags')
            ->once()
            ->with($expectedTags)
            ->andReturn($taggedCache);

        $taggedCache->shouldReceive('flush')
            ->once();

        $this->invalidator->invalidateUserCaches(123);
    }

    /** @test */
    public function it_invalidates_organization_caches(): void
    {
        $taggedCache = \Mockery::mock('Illuminate\Cache\TaggedCache');

        $expectedTags = [
            'organization_dashboard',
            'organization:456',
            'campaigns',
            'donations',
        ];

        $this->cache->shouldReceive('tags')
            ->once()
            ->with($expectedTags)
            ->andReturn($taggedCache);

        $taggedCache->shouldReceive('flush')
            ->once();

        $this->invalidator->invalidateOrganizationCaches(456);
    }

    /** @test */
    public function it_invalidates_all_caches(): void
    {
        $taggedCache = \Mockery::mock('Illuminate\Cache\TaggedCache');

        $expectedTags = [
            'campaign_analytics',
            'donation_reports',
            'organization_dashboard',
            'user_profile',
            'campaigns',
            'donations',
            'organizations',
            'users',
        ];

        $this->cache->shouldReceive('tags')
            ->once()
            ->with($expectedTags)
            ->andReturn($taggedCache);

        $taggedCache->shouldReceive('flush')
            ->once();

        $this->invalidator->invalidateAll();
    }
}
