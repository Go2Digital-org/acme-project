<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Job monitoring system for tracking queue health and performance
 */
final class JobMonitoringJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public int $tries = 1; // Don't retry monitoring jobs

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        private readonly string $monitoringType = 'general',
        /** @var array<string, mixed> */
        private readonly array $options = []
    ) {
        $this->onQueue('monitoring');
    }

    public function handle(): void
    {
        try {
            match ($this->monitoringType) {
                'queue_health' => $this->monitorQueueHealth(),
                'failed_jobs' => $this->monitorFailedJobs(),
                'job_metrics' => $this->collectJobMetrics(),
                'memory_usage' => $this->monitorMemoryUsage(),
                'slow_jobs' => $this->identifySlowJobs(),
                default => $this->performGeneralMonitoring(),
            };
        } catch (Exception $exception) {
            Log::error('Job monitoring failed', [
                'monitoring_type' => $this->monitoringType,
                'error' => $exception->getMessage(),
                'options' => $this->options,
            ]);

            throw $exception;
        }
    }

    private function monitorQueueHealth(): void
    {
        $queueSizes = $this->getQueueSizes();
        $thresholds = config('queue.monitoring.thresholds', [
            'notifications' => 1000,
            'payments' => 100,
            'exports' => 50,
            'bulk' => 500,
            'default' => 200,
        ]);

        $alerts = [];

        foreach ($queueSizes as $queue => $size) {
            $threshold = $thresholds[$queue] ?? $thresholds['default'];

            if ($size > $threshold) {
                $alerts[] = [
                    'queue' => $queue,
                    'size' => $size,
                    'threshold' => $threshold,
                    'severity' => $size > ($threshold * 2) ? 'critical' : 'warning',
                ];
            }
        }

        if (! empty($alerts)) {
            // Convert indexed array to associative array for method signature
            $alertsAssoc = [];
            foreach ($alerts as $i => $alert) {
                $alertsAssoc["alert_{$i}"] = $alert;
            }
            $this->sendQueueHealthAlert($alertsAssoc);
        }

        // Store metrics for trending
        Cache::put('queue_monitoring:health:' . now()->format('Y-m-d-H'), $queueSizes, 86400);

        Log::info('Queue health monitoring completed', [
            'queue_sizes' => $queueSizes,
            'alerts_triggered' => count($alerts),
        ]);
    }

    private function monitorFailedJobs(): void
    {
        $failedJobsCount = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subHours(1))
            ->count();

        // Query failed jobs directly to avoid collection pluck optimization warning
        $failedJobsQuery = DB::table('failed_jobs')
            ->select('queue', DB::raw('count(*) as count'))
            ->where('failed_at', '>=', now()->subHours(1))
            ->groupBy('queue');

        $failedJobsByQueue = [];
        foreach ($failedJobsQuery->get() as $row) {
            $failedJobsByQueue[$row->queue] = $row->count;
        }

        $criticalThreshold = config('queue.monitoring.failed_jobs_threshold', 10);

        if ($failedJobsCount > $criticalThreshold) {
            $this->sendFailedJobsAlert($failedJobsCount, $failedJobsByQueue);
        }

        // Store metrics
        Cache::put('queue_monitoring:failed_jobs:' . now()->format('Y-m-d-H'), [
            'total' => $failedJobsCount,
            'by_queue' => $failedJobsByQueue,
        ], 86400);

        Log::info('Failed jobs monitoring completed', [
            'failed_jobs_last_hour' => $failedJobsCount,
            'by_queue' => $failedJobsByQueue,
        ]);
    }

    private function collectJobMetrics(): void
    {
        $metrics = [
            'processed_jobs_last_hour' => $this->getProcessedJobsCount(),
            'average_job_duration' => $this->getAverageJobDuration(),
            'queue_throughput' => $this->getQueueThroughput(),
            'memory_peak_usage' => $this->getMemoryPeakUsage(),
        ];

        // Store metrics for analysis
        Cache::put('queue_monitoring:metrics:' . now()->format('Y-m-d-H'), $metrics, 86400);

        Log::info('Job metrics collected', $metrics);
    }

    private function monitorMemoryUsage(): void
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        $usagePercentage = ($memoryPeak / $memoryLimit) * 100;

        if ($usagePercentage > 80) {
            $this->sendMemoryAlert($memoryUsage, $memoryPeak, $memoryLimit, $usagePercentage);
        }

        Cache::put('queue_monitoring:memory:' . now()->format('Y-m-d-H-i'), [
            'usage' => $memoryUsage,
            'peak' => $memoryPeak,
            'limit' => $memoryLimit,
            'percentage' => $usagePercentage,
        ], 3600);
    }

    private function identifySlowJobs(): void
    {
        // This would require custom job timing tracking
        // For now, we'll check for jobs that have been running too long
        $longRunningJobs = DB::table('jobs')
            ->where('reserved_at', '<', now()->subMinutes(30)->getTimestamp())
            ->whereNotNull('reserved_at')
            ->get();

        if ($longRunningJobs->isNotEmpty()) {
            $this->sendSlowJobsAlert($longRunningJobs);
        }

        Log::info('Slow jobs monitoring completed', [
            'long_running_jobs_count' => $longRunningJobs->count(),
        ]);
    }

    private function performGeneralMonitoring(): void
    {
        $this->monitorQueueHealth();
        $this->monitorFailedJobs();
        $this->collectJobMetrics();
        $this->monitorMemoryUsage();
    }

    /**
     * @return array<string, int>
     */
    private function getQueueSizes(): array
    {
        try {
            $redis = Redis::connection('queue');
            $queues = ['default', 'notifications', 'payments', 'exports', 'bulk', 'maintenance'];
            $sizes = [];

            foreach ($queues as $queue) {
                $sizes[$queue] = $redis->llen("queues:{$queue}");
            }

            return $sizes;
        } catch (Exception $exception) {
            Log::warning('Failed to get queue sizes from Redis', [
                'error' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function getProcessedJobsCount(): int
    {
        // This would require custom tracking
        // For now, estimate based on failed jobs and queue throughput
        return Cache::get('queue_monitoring:processed_jobs_estimate', 0);
    }

    private function getAverageJobDuration(): float
    {
        // This would require custom timing tracking
        return Cache::get('queue_monitoring:avg_duration', 0.0);
    }

    /**
     * @return array<string, int>
     */
    private function getQueueThroughput(): array
    {
        // Calculate jobs per minute for each queue
        return Cache::get('queue_monitoring:throughput', []);
    }

    private function getMemoryPeakUsage(): int
    {
        return Cache::get('queue_monitoring:memory_peak', memory_get_peak_usage(true));
    }

    private function parseMemoryLimit(string $memoryLimit): int
    {
        if ($memoryLimit === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) $memoryLimit;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * @param  array<string, mixed>  $alerts
     */
    private function sendQueueHealthAlert(array $alerts): void
    {
        \Modules\Shared\Infrastructure\Laravel\Jobs\SendEmailJob::dispatch(
            emailData: [
                'to' => config('queue.monitoring.alert_email', 'admin@example.com'),
                'subject' => 'Queue Health Alert - High Queue Sizes',
                'view' => 'emails.monitoring.queue-health-alert',
                'data' => [
                    'alerts' => $alerts,
                    'timestamp' => now(),
                    'server' => gethostname(),
                ],
            ],
            locale: null,
            priority: 9
        )->onQueue('notifications');

        Log::critical('Queue health alert sent', ['alerts' => $alerts]);
    }

    /**
     * @param  array<string, int>  $failedByQueue
     */
    private function sendFailedJobsAlert(int $failedCount, array $failedByQueue): void
    {
        \Modules\Shared\Infrastructure\Laravel\Jobs\SendEmailJob::dispatch(
            emailData: [
                'to' => config('queue.monitoring.alert_email', 'admin@example.com'),
                'subject' => 'Failed Jobs Alert - High Failure Rate',
                'view' => 'emails.monitoring.failed-jobs-alert',
                'data' => [
                    'failed_count' => $failedCount,
                    'failed_by_queue' => $failedByQueue,
                    'timestamp' => now(),
                    'period' => 'last hour',
                ],
            ],
            locale: null,
            priority: 9
        )->onQueue('notifications');

        Log::critical('Failed jobs alert sent', [
            'failed_count' => $failedCount,
            'failed_by_queue' => $failedByQueue,
        ]);
    }

    private function sendMemoryAlert(int $usage, int $peak, int $limit, float $percentage): void
    {
        \Modules\Shared\Infrastructure\Laravel\Jobs\SendEmailJob::dispatch(
            emailData: [
                'to' => config('queue.monitoring.alert_email', 'admin@example.com'),
                'subject' => 'Memory Usage Alert - High Memory Consumption',
                'view' => 'emails.monitoring.memory-alert',
                'data' => [
                    'memory_usage' => $usage,
                    'memory_peak' => $peak,
                    'memory_limit' => $limit,
                    'usage_percentage' => $percentage,
                    'timestamp' => now(),
                ],
            ],
            locale: null,
            priority: 8
        )->onQueue('notifications');

        Log::warning('Memory usage alert sent', [
            'usage_percentage' => $percentage,
            'peak_memory' => $peak,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \stdClass>  $longRunningJobs
     */
    private function sendSlowJobsAlert($longRunningJobs): void
    {
        \Modules\Shared\Infrastructure\Laravel\Jobs\SendEmailJob::dispatch(
            emailData: [
                'to' => config('queue.monitoring.alert_email', 'admin@example.com'),
                'subject' => 'Slow Jobs Alert - Long Running Jobs Detected',
                'view' => 'emails.monitoring.slow-jobs-alert',
                'data' => [
                    'long_running_jobs' => $longRunningJobs->toArray(),
                    'count' => $longRunningJobs->count(),
                    'timestamp' => now(),
                ],
            ],
            locale: null,
            priority: 7
        )->onQueue('notifications');

        Log::warning('Slow jobs alert sent', [
            'long_running_jobs_count' => $longRunningJobs->count(),
        ]);
    }

    public static function scheduleMonitoring(): void
    {
        // Schedule different types of monitoring
        self::dispatch('queue_health')->delay(now()->addMinutes(5));
        self::dispatch('failed_jobs')->delay(now()->addMinutes(10));
        self::dispatch('job_metrics')->delay(now()->addMinutes(15));
        self::dispatch('memory_usage')->delay(now()->addMinutes(3));
        self::dispatch('slow_jobs')->delay(now()->addMinutes(20));
    }
}
