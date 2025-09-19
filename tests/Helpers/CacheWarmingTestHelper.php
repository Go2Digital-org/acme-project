<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Modules\CacheWarming\Domain\Model\CacheMetric;
use Modules\CacheWarming\Domain\Model\CacheWarmingJob;
use Modules\CacheWarming\Domain\ValueObject\JobStatus;
use Modules\CacheWarming\Domain\ValueObject\MetricType;

class CacheWarmingTestHelper
{
    /**
     * Create a set of test cache warming jobs with realistic data
     *
     * @return array<int, CacheWarmingJob>
     */
    public static function createTestJobsScenario(array $config = []): array
    {
        $defaults = [
            'running_count' => 2,
            'completed_count' => 5,
            'failed_count' => 1,
            'pending_count' => 1,
        ];

        $config = array_merge($defaults, $config);
        $jobs = [];

        // Create running jobs
        for ($i = 0; $i < $config['running_count']; $i++) {
            $jobs[] = CacheWarmingJob::factory()->running()->create([
                'job_type' => fake()->randomElement(['campaigns', 'donations', 'users']),
            ]);
        }

        // Create completed jobs
        for ($i = 0; $i < $config['completed_count']; $i++) {
            $jobs[] = CacheWarmingJob::factory()->completed()->create();
        }

        // Create failed jobs
        for ($i = 0; $i < $config['failed_count']; $i++) {
            $jobs[] = CacheWarmingJob::factory()->failed()->create();
        }

        // Create pending jobs
        for ($i = 0; $i < $config['pending_count']; $i++) {
            $jobs[] = CacheWarmingJob::factory()->pending()->create();
        }

        return $jobs;
    }

    /**
     * Create cache metrics for testing with various states
     *
     * @return array<int, CacheMetric>
     */
    public static function createTestMetricsScenario(array $config = []): array
    {
        $defaults = [
            'campaigns_count' => 50,
            'donations_count' => 75,
            'users_count' => 30,
            'organizations_count' => 15,
            'analytics_count' => 25,
            'expired_percentage' => 0.2, // 20% expired
        ];

        $config = array_merge($defaults, $config);
        $metrics = [];

        foreach (['campaigns', 'donations', 'users', 'organizations', 'analytics'] as $type) {
            $count = $config["{$type}_count"];
            $expiredCount = intval($count * $config['expired_percentage']);
            $activeCount = $count - $expiredCount;

            // Create active metrics
            for ($i = 0; $i < $activeCount; $i++) {
                $factory = match ($type) {
                    'campaigns' => CacheMetric::factory()->forCampaigns(),
                    'donations' => CacheMetric::factory()->forDonations(),
                    'users' => CacheMetric::factory()->forUsers(),
                    'organizations' => CacheMetric::factory()->forOrganizations(),
                    'analytics' => CacheMetric::factory()->forAnalytics(),
                };

                $metrics[] = $factory->create();
            }

            // Create expired metrics
            for ($i = 0; $i < $expiredCount; $i++) {
                $factory = match ($type) {
                    'campaigns' => CacheMetric::factory()->forCampaigns()->expired(),
                    'donations' => CacheMetric::factory()->forDonations()->expired(),
                    'users' => CacheMetric::factory()->forUsers()->expired(),
                    'organizations' => CacheMetric::factory()->forOrganizations()->expired(),
                    'analytics' => CacheMetric::factory()->forAnalytics()->expired(),
                };

                $metrics[] = $factory->create();
            }
        }

        return $metrics;
    }

    /**
     * Create massive dataset for performance testing
     *
     * @return array<string, mixed>
     */
    public static function createMassiveDataset(int $jobCount = 10000, int $metricCount = 50000): array
    {
        set_time_limit(300); // 5 minutes for large dataset creation

        $jobs = [];
        $metrics = [];

        // Create jobs in chunks for memory efficiency
        $chunkSize = 1000;
        for ($i = 0; $i < $jobCount; $i += $chunkSize) {
            $remaining = min($chunkSize, $jobCount - $i);

            $chunk = CacheWarmingJob::factory()
                ->count($remaining)
                ->state(function () {
                    return [
                        'status' => fake()->randomElement([
                            JobStatus::COMPLETED->value,
                            JobStatus::FAILED->value,
                            JobStatus::RUNNING->value,
                        ]),
                        'job_type' => fake()->randomElement(['campaigns', 'donations', 'users', 'analytics']),
                    ];
                })
                ->create();

            $jobs = array_merge($jobs, $chunk->toArray());
        }

        // Create metrics in chunks
        for ($i = 0; $i < $metricCount; $i += $chunkSize) {
            $remaining = min($chunkSize, $metricCount - $i);

            $chunk = CacheMetric::factory()
                ->count($remaining)
                ->state(function () {
                    return [
                        'metric_type' => fake()->randomElement([
                            MetricType::CAMPAIGNS->value,
                            MetricType::DONATIONS->value,
                            MetricType::USERS->value,
                            MetricType::ANALYTICS->value,
                        ]),
                    ];
                })
                ->create();

            $metrics = array_merge($metrics, $chunk->toArray());
        }

        return [
            'jobs' => $jobs,
            'metrics' => $metrics,
            'summary' => [
                'jobs_created' => count($jobs),
                'metrics_created' => count($metrics),
                'total_records' => count($jobs) + count($metrics),
            ],
        ];
    }

    /**
     * Simulate cache warming execution with realistic timing
     *
     * @return array<string, mixed>
     */
    public static function simulateCacheWarmingExecution(CacheWarmingJob $job): array
    {
        $startTime = microtime(true);

        // Simulate job starting
        $job->start();
        $job->save();

        $totalChunks = $job->total_chunks;
        $recordsPerChunk = fake()->numberBetween(50, 200);

        // Simulate processing chunks
        for ($chunk = 1; $chunk <= $totalChunks; $chunk++) {
            // Simulate processing time per chunk
            usleep(fake()->numberBetween(10000, 50000)); // 10-50ms per chunk

            $recordsProcessed = $chunk * $recordsPerChunk;
            $job->updateProgress($chunk, $totalChunks, $recordsProcessed);

            if ($chunk % 10 === 0) {
                $job->save(); // Save progress every 10 chunks
            }
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $memoryPeak = memory_get_peak_usage(true) / 1024 / 1024;

        // Complete the job
        $job->complete($totalChunks * $recordsPerChunk, $executionTime, $memoryPeak);
        $job->save();

        return [
            'execution_time' => $executionTime,
            'memory_peak_mb' => $memoryPeak,
            'records_processed' => $job->records_processed,
            'chunks_processed' => $job->chunks_processed,
        ];
    }

    /**
     * Generate realistic cache data for testing
     *
     * @return array<string, mixed>
     */
    public static function generateCacheTestData(int $keyCount = 100): array
    {
        $data = [];
        $prefixes = ['campaigns', 'donations', 'users', 'organizations', 'analytics'];

        for ($i = 0; $i < $keyCount; $i++) {
            $prefix = fake()->randomElement($prefixes);
            $key = "{$prefix}:test:data:{$i}";

            $value = [
                'id' => $i,
                'timestamp' => now()->timestamp,
                'data' => fake()->words(fake()->numberBetween(10, 50), true),
                'metrics' => [
                    'count' => fake()->numberBetween(1, 1000),
                    'value' => fake()->randomFloat(2, 1, 10000),
                ],
                'metadata' => [
                    'generated' => true,
                    'test_id' => fake()->uuid,
                ],
            ];

            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * Warm cache with test data and track performance
     *
     * @return array<string, mixed>
     */
    public static function warmCacheWithTestData(array $data, int $ttl = 3600): array
    {
        $startTime = microtime(true);
        $keysSet = 0;
        $errors = [];

        try {
            foreach ($data as $key => $value) {
                if (Cache::put($key, $value, $ttl)) {
                    $keysSet++;
                } else {
                    $errors[] = "Failed to set key: {$key}";
                }
            }
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        return [
            'keys_set' => $keysSet,
            'total_keys' => count($data),
            'execution_time' => $executionTime,
            'keys_per_second' => $keysSet > 0 ? $keysSet / $executionTime : 0,
            'errors' => $errors,
            'success_rate' => count($data) > 0 ? ($keysSet / count($data)) * 100 : 0,
        ];
    }

    /**
     * Clear all cache warming test data
     */
    public static function clearCacheWarmingTestData(): void
    {
        // Clear database tables
        CacheWarmingJob::query()->delete();
        CacheMetric::query()->delete();

        // Clear cache
        Cache::flush();

        // Clear Redis if available
        try {
            Redis::flushdb();
        } catch (\Exception $e) {
            // Redis might not be available in all test environments
        }

        // Clear queue jobs
        Queue::fake();
    }

    /**
     * Benchmark cache warming performance
     *
     * @return array<int, array<string, mixed>>
     */
    public static function benchmarkCacheWarmingPerformance(array $config = []): array
    {
        $defaults = [
            'key_counts' => [100, 500, 1000, 5000],
            'iterations' => 3,
            'ttl' => 3600,
        ];

        $config = array_merge($defaults, $config);
        $results = [];

        foreach ($config['key_counts'] as $keyCount) {
            $iterationResults = [];

            for ($i = 0; $i < $config['iterations']; $i++) {
                Cache::flush(); // Clear cache before each iteration

                $testData = self::generateCacheTestData($keyCount);
                $result = self::warmCacheWithTestData($testData, $config['ttl']);
                $iterationResults[] = $result;
            }

            // Calculate averages
            $avgExecutionTime = array_sum(array_column($iterationResults, 'execution_time')) / count($iterationResults);
            $avgKeysPerSecond = array_sum(array_column($iterationResults, 'keys_per_second')) / count($iterationResults);
            $avgSuccessRate = array_sum(array_column($iterationResults, 'success_rate')) / count($iterationResults);

            $results[$keyCount] = [
                'key_count' => $keyCount,
                'iterations' => $config['iterations'],
                'avg_execution_time' => $avgExecutionTime,
                'avg_keys_per_second' => $avgKeysPerSecond,
                'avg_success_rate' => $avgSuccessRate,
                'raw_results' => $iterationResults,
            ];
        }

        return $results;
    }

    /**
     * Create test scenario with specific job distribution
     *
     * @return array<int, CacheWarmingJob>
     */
    public static function createJobDistributionScenario(array $distribution): array
    {
        $jobs = [];

        foreach ($distribution as $jobType => $count) {
            for ($i = 0; $i < $count; $i++) {
                $jobs[] = CacheWarmingJob::factory()
                    ->forJobType($jobType)
                    ->create([
                        'status' => fake()->randomElement([
                            JobStatus::COMPLETED->value,
                            JobStatus::RUNNING->value,
                            JobStatus::FAILED->value,
                        ]),
                    ]);
            }
        }

        return $jobs;
    }

    /**
     * Assert cache warming job state is valid
     */
    public static function assertJobStateValid(CacheWarmingJob $job): void
    {
        $status = JobStatus::from($job->status);

        switch ($status) {
            case JobStatus::PENDING:
                assert($job->started_at === null, 'Pending job should not have started_at');
                assert($job->completed_at === null, 'Pending job should not have completed_at');
                assert($job->chunks_processed === 0, 'Pending job should have 0 chunks processed');
                assert($job->records_processed === 0, 'Pending job should have 0 records processed');
                break;

            case JobStatus::RUNNING:
                assert($job->started_at !== null, 'Running job should have started_at');
                assert($job->completed_at === null, 'Running job should not have completed_at');
                assert($job->chunks_processed < $job->total_chunks, 'Running job should have processed less than total chunks');
                break;

            case JobStatus::COMPLETED:
                assert($job->started_at !== null, 'Completed job should have started_at');
                assert($job->completed_at !== null, 'Completed job should have completed_at');
                assert($job->chunks_processed === $job->total_chunks, 'Completed job should have processed all chunks');
                assert($job->execution_time_seconds > 0, 'Completed job should have execution time');
                break;

            case JobStatus::FAILED:
                assert($job->started_at !== null, 'Failed job should have started_at');
                assert($job->completed_at !== null, 'Failed job should have completed_at');
                assert($job->error_details !== null, 'Failed job should have error details');
                break;

            case JobStatus::CANCELLED:
                assert($job->completed_at !== null, 'Cancelled job should have completed_at');
                assert($job->error_details !== null, 'Cancelled job should have error details');
                break;
        }
    }

    /**
     * Generate performance test report
     */
    public static function generatePerformanceReport(array $benchmarkResults): string
    {
        $report = "Cache Warming Performance Report\n";
        $report .= "================================\n\n";
        $report .= sprintf("Generated: %s\n", now()->toDateTimeString());
        $report .= sprintf("Test Environment: %s\n", app()->environment());
        $report .= "\n";

        foreach ($benchmarkResults as $keyCount => $results) {
            $report .= sprintf("Key Count: %d\n", $keyCount);
            $report .= sprintf("Average Execution Time: %.3f seconds\n", $results['avg_execution_time']);
            $report .= sprintf("Average Keys/Second: %.1f\n", $results['avg_keys_per_second']);
            $report .= sprintf("Average Success Rate: %.1f%%\n", $results['avg_success_rate']);
            $report .= "\n";
        }

        // Performance recommendations
        $report .= "Recommendations:\n";
        $report .= "================\n";

        $fastestResult = collect($benchmarkResults)->sortBy('avg_execution_time')->first();
        $slowestResult = collect($benchmarkResults)->sortByDesc('avg_execution_time')->first();

        if ($fastestResult['avg_keys_per_second'] > 1000) {
            $report .= "✓ Good performance: Achieving > 1000 keys/second\n";
        } else {
            $report .= "⚠ Consider optimization: Performance below 1000 keys/second\n";
        }

        if ($slowestResult['avg_execution_time'] > 10) {
            $report .= "⚠ High latency detected for large datasets\n";
            $report .= "  Consider implementing chunked processing\n";
        }

        return $report;
    }

    /**
     * Mock external dependencies for testing
     */
    public static function mockExternalDependencies(): void
    {
        // Mock Redis connection issues
        Redis::shouldReceive('connection')->andThrow(new \RedisException('Connection failed'));

        // Mock database timeouts
        DB::shouldReceive('table')->with('large_table')->andThrow(new \Exception('Query timeout'));

        // Mock queue failures
        Queue::shouldReceive('push')->andThrow(new \Exception('Queue backend unavailable'));
    }

    /**
     * Restore normal external dependencies
     */
    public static function restoreExternalDependencies(): void
    {
        \Mockery::resetContainer();
    }
}
