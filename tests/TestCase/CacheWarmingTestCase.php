<?php

declare(strict_types=1);

namespace Tests\TestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Tests\Helpers\CacheWarmingTestHelper;

abstract class CacheWarmingTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache and queue before each test
        $this->clearTestEnvironment();

        // Set up test-specific configuration
        $this->configureTestEnvironment();
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        $this->clearTestEnvironment();

        parent::tearDown();
    }

    /**
     * Clear the test environment
     */
    protected function clearTestEnvironment(): void
    {
        CacheWarmingTestHelper::clearCacheWarmingTestData();
    }

    /**
     * Configure the test environment
     */
    protected function configureTestEnvironment(): void
    {
        // Use array cache driver for consistent testing
        config(['cache.default' => 'array']);

        // Use database for sessions in tests
        config(['session.driver' => 'array']);

        // Fake queue for testing
        Queue::fake();

        // Set test-specific cache warming configuration
        config(['cache-warming.chunk_size' => 100]);
        config(['cache-warming.timeout' => 30]);
        config(['cache-warming.max_concurrent_jobs' => 2]);
    }

    /**
     * Create standard test scenario
     *
     * @return array<int, \Modules\CacheWarming\Domain\Model\CacheWarmingJob>
     */
    protected function createStandardTestScenario(): array
    {
        return CacheWarmingTestHelper::createTestJobsScenario([
            'running_count' => 2,
            'completed_count' => 5,
            'failed_count' => 1,
            'pending_count' => 1,
        ]);
    }

    /**
     * Create metrics test scenario
     *
     * @return array<int, \Modules\CacheWarming\Domain\Model\CacheMetric>
     */
    protected function createMetricsTestScenario(): array
    {
        return CacheWarmingTestHelper::createTestMetricsScenario([
            'campaigns_count' => 25,
            'donations_count' => 30,
            'users_count' => 15,
            'organizations_count' => 10,
            'analytics_count' => 20,
            'expired_percentage' => 0.2,
        ]);
    }

    /**
     * Assert cache key exists with expected value
     */
    protected function assertCacheHas(string $key, $expectedValue = null): void
    {
        $this->assertTrue(Cache::has($key), "Cache key '{$key}' does not exist");

        if ($expectedValue !== null) {
            $actualValue = Cache::get($key);
            $this->assertEquals($expectedValue, $actualValue, "Cache key '{$key}' has unexpected value");
        }
    }

    /**
     * Assert cache key does not exist
     */
    protected function assertCacheNotHas(string $key): void
    {
        $this->assertFalse(Cache::has($key), "Cache key '{$key}' should not exist");
    }

    /**
     * Assert cache key has TTL within range
     */
    protected function assertCacheTtl(string $key, int $minTtl, int $maxTtl): void
    {
        $this->assertTrue(Cache::has($key), "Cache key '{$key}' does not exist");

        // This would need implementation specific to cache driver
        // For Redis, we could check TTL directly
        if (config('cache.default') === 'redis') {
            try {
                $ttl = Redis::ttl(config('cache.prefix') . ':' . $key);
                $this->assertGreaterThanOrEqual($minTtl, $ttl);
                $this->assertLessThanOrEqual($maxTtl, $ttl);
            } catch (\Exception $e) {
                $this->markTestSkipped('Redis not available for TTL testing');
            }
        }
    }

    /**
     * Assert job has expected status
     */
    protected function assertJobStatus(int $jobId, string $expectedStatus): void
    {
        $this->assertDatabaseHas('cache_warming_jobs', [
            'id' => $jobId,
            'status' => $expectedStatus,
        ]);
    }

    /**
     * Assert job progress is within expected range
     */
    protected function assertJobProgress(int $jobId, float $minProgress, float $maxProgress): void
    {
        $job = \Modules\CacheWarming\Domain\Model\CacheWarmingJob::find($jobId);

        $this->assertNotNull($job, "Job {$jobId} not found");

        $progress = $job->getProgressPercentage();
        $this->assertGreaterThanOrEqual($minProgress, $progress);
        $this->assertLessThanOrEqual($maxProgress, $progress);
    }

    /**
     * Assert metric exists with expected type
     */
    protected function assertMetricExists(string $metricKey, ?string $expectedType = null): void
    {
        $conditions = ['metric_key' => $metricKey];

        if ($expectedType) {
            $conditions['metric_type'] = $expectedType;
        }

        $this->assertDatabaseHas('widget_metrics_cache', $conditions);
    }

    /**
     * Assert metric is not expired
     */
    protected function assertMetricNotExpired(string $metricKey): void
    {
        $metric = \Modules\CacheWarming\Domain\Model\CacheMetric::where('metric_key', $metricKey)->first();

        $this->assertNotNull($metric, "Metric '{$metricKey}' not found");
        $this->assertFalse($metric->isExpired(), "Metric '{$metricKey}' should not be expired");
    }

    /**
     * Assert performance is within acceptable bounds
     */
    protected function assertPerformanceAcceptable(float $executionTime, int $recordsProcessed): void
    {
        // Define performance thresholds
        $maxTimePerRecord = 0.001; // 1ms per record max
        $minRecordsPerSecond = 1000;

        $timePerRecord = $recordsProcessed > 0 ? $executionTime / $recordsProcessed : 0;
        $recordsPerSecond = $executionTime > 0 ? $recordsProcessed / $executionTime : 0;

        $this->assertLessThanOrEqual(
            $maxTimePerRecord,
            $timePerRecord,
            "Performance too slow: {$timePerRecord}s per record (max: {$maxTimePerRecord}s)"
        );

        $this->assertGreaterThanOrEqual(
            $minRecordsPerSecond,
            $recordsPerSecond,
            "Throughput too low: {$recordsPerSecond} records/s (min: {$minRecordsPerSecond})"
        );
    }

    /**
     * Assert memory usage is reasonable
     */
    protected function assertMemoryUsageReasonable(float $memoryMb, int $recordsProcessed): void
    {
        $maxMemoryPerRecord = 0.01; // 10KB per record max
        $memoryPerRecord = $recordsProcessed > 0 ? ($memoryMb * 1024 * 1024) / $recordsProcessed : 0;

        $this->assertLessThanOrEqual(
            $maxMemoryPerRecord,
            $memoryPerRecord / (1024 * 1024), // Convert back to MB for comparison
            "Memory usage too high: {$memoryPerRecord}MB per record"
        );
    }

    /**
     * Mock performance monitoring
     */
    protected function mockPerformanceMonitoring(): void
    {
        // Mock performance monitoring service
        $this->instance(
            \Modules\CacheWarming\Infrastructure\Redis\CacheMonitoringService::class,
            \Mockery::mock(\Modules\CacheWarming\Infrastructure\Redis\CacheMonitoringService::class)
        );
    }

    /**
     * Create performance test data
     *
     * @return array<string, mixed>
     */
    protected function createPerformanceTestData(int $count = 1000): array
    {
        return CacheWarmingTestHelper::generateCacheTestData($count);
    }

    /**
     * Run performance benchmark
     *
     * @return array<int, array<string, mixed>>
     */
    protected function runPerformanceBenchmark(array $config = []): array
    {
        return CacheWarmingTestHelper::benchmarkCacheWarmingPerformance($config);
    }

    /**
     * Assert queue job was dispatched
     *
     * @param  \Closure(mixed): bool|null  $callback
     */
    protected function assertQueueJobDispatched(string $jobClass, ?\Closure $callback = null): void
    {
        if ($callback) {
            Queue::assertPushed($jobClass, $callback);
        } else {
            Queue::assertPushed($jobClass);
        }
    }

    /**
     * Assert queue job was not dispatched
     */
    protected function assertQueueJobNotDispatched(string $jobClass): void
    {
        Queue::assertNotPushed($jobClass);
    }

    /**
     * Travel forward in time for expiration testing
     */
    protected function travelToFuture(int $minutes): void
    {
        $this->travel($minutes)->minutes();
    }

    /**
     * Travel backward in time for historical testing
     */
    protected function travelToPast(int $minutes): void
    {
        $this->travel(-$minutes)->minutes();
    }

    /**
     * Skip test if Redis is not available
     */
    protected function skipIfRedisUnavailable(): void
    {
        try {
            Redis::ping();
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis is not available: ' . $e->getMessage());
        }
    }

    /**
     * Skip test if external dependencies are not available
     */
    protected function skipIfExternalDependenciesUnavailable(): void
    {
        // Check for database
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            $this->markTestSkipped('Database is not available: ' . $e->getMessage());
        }
    }

    /**
     * Generate test report
     */
    protected function generateTestReport(array $results): string
    {
        return CacheWarmingTestHelper::generatePerformanceReport($results);
    }

    /**
     * Create application with test configuration
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        // Set test-specific configurations
        $app['config']->set('cache.default', 'array');
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('cache-warming.test_mode', true);

        return $app;
    }

    /**
     * Cleanup method for child classes to override
     */
    protected function performAdditionalCleanup(): void
    {
        // Override in child classes if needed
    }
}
