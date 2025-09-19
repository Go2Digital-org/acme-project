<?php

declare(strict_types=1);

namespace Tests\Integration\Shared\Application\Service;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Modules\Shared\Application\Service\CacheService;
use Psr\Log\LoggerInterface;
use Tests\Integration\IntegrationTestCase;

/**
 * Lightweight tests for CacheService core functionality.
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

    /** @test */
    public function it_remembers_data_with_correct_ttl(): void
    {
        $key = 'test:key';
        $data = ['test' => 'data'];
        $callback = fn () => $data;

        // Test default medium TTL
        $this->mockCache->shouldReceive('remember')
            ->once()
            ->with($key, 1800, $callback)
            ->andReturn($data);

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->remember($key, $callback);
        $this->assertSame($data, $result);
    }

    /** @test */
    public function it_handles_cache_invalidation(): void
    {
        Cache::shouldReceive('tags')->once()->andReturnSelf();
        Cache::shouldReceive('flush')->once();
        $this->mockLogger->shouldReceive('info')->once();

        $this->cacheService->invalidateOrganization(123);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_provides_cache_statistics(): void
    {
        $stats = $this->cacheService->getCacheStats();

        $this->assertArrayHasKey('cache_driver', $stats);
        $this->assertArrayHasKey('ttl_configuration', $stats);
    }
}
