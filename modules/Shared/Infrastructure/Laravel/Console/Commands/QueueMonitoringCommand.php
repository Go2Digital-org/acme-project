<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Shared\Infrastructure\Laravel\Jobs\JobMonitoringJob;

/**
 * Queue monitoring command for health checks and metrics collection
 */
class QueueMonitoringCommand extends Command
{
    protected $signature = 'queue:monitor
                           {--type=general : Type of monitoring (general, health, failed, metrics, memory, slow)}
                           {--alert : Send alerts if thresholds are exceeded}
                           {--report : Generate a monitoring report}';

    protected $description = 'Monitor queue health, performance, and trigger alerts';

    public function handle(): int
    {
        $type = $this->option('type') ?? 'general';
        $shouldAlert = $this->option('alert');
        $shouldReport = $this->option('report');

        $this->info("Starting queue monitoring: {$type}");

        try {
            // Dispatch monitoring job
            JobMonitoringJob::dispatch($type, [
                'alert' => $shouldAlert,
                'report' => $shouldReport,
                'triggered_by' => 'console_command',
                'timestamp' => now()->toISOString(),
            ]);

            if ($shouldReport) {
                $this->generateReport();
            }

            if ($type === 'general' || $type === 'health') {
                $this->displayQueueStatus();
            }

            $this->info('Queue monitoring completed successfully');

            return self::SUCCESS;

        } catch (Exception $exception) {
            $this->error("Queue monitoring failed: {$exception->getMessage()}");

            return self::FAILURE;
        }
    }

    private function generateReport(): void
    {
        $this->info('Generating queue monitoring report...');

        $this->table(
            ['Metric', 'Value', 'Status'],
            $this->collectMetrics()
        );
    }

    private function displayQueueStatus(): void
    {
        $this->info('Current Queue Status:');

        try {
            $queueSizes = $this->getQueueSizes();
            $failedJobs = $this->getFailedJobsCount();
            $systemHealth = $this->getSystemHealth();

            $this->table(
                ['Queue', 'Size', 'Status'],
                $this->formatQueueData($queueSizes)
            );

            $this->newLine();
            $this->info("Failed jobs (last 24h): {$failedJobs}");
            $this->info("System health: {$systemHealth}");

            if ($failedJobs > 10) {
                $this->warn('High number of failed jobs detected!');
            }

        } catch (Exception $exception) {
            $this->error("Failed to retrieve queue status: {$exception->getMessage()}");
        }
    }

    /**
     * @return list<array<int, mixed>>
     */
    private function collectMetrics(): array
    {
        $metrics = [];

        try {
            // Queue sizes
            $queueSizes = $this->getQueueSizes();
            foreach ($queueSizes as $queue => $size) {
                $status = $this->getQueueStatus($queue, $size);
                $metrics[] = ["Queue: {$queue}", $size, $status];
            }

            // Failed jobs
            $failedJobs = $this->getFailedJobsCount();
            $failedStatus = $failedJobs > 10 ? '⚠️  High' : '✅ Normal';
            $metrics[] = ['Failed Jobs (24h)', $failedJobs, $failedStatus];

            // System health
            $systemHealth = $this->getSystemHealth();
            $healthStatus = $systemHealth === 'good' ? '✅ Good' : '⚠️  Issues';
            $metrics[] = ['System Health', $systemHealth, $healthStatus];

            // Memory usage
            $memoryUsage = $this->getMemoryUsage();
            $memoryStatus = $memoryUsage < 80 ? '✅ Normal' : '⚠️  High';
            $metrics[] = ['Memory Usage %', $memoryUsage, $memoryStatus];

            // Processing rate
            $processingRate = $this->getProcessingRate();
            $rateStatus = $processingRate > 100 ? '✅ Good' : '⚠️  Slow';
            $metrics[] = ['Processing Rate (jobs/min)', $processingRate, $rateStatus];

        } catch (Exception $exception) {
            $metrics[] = ['Error', $exception->getMessage(), '❌ Failed'];
        }

        return $metrics;
    }

    /**
     * @return array<string, mixed>
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
        } catch (Exception) {
            return [];
        }
    }

    private function getFailedJobsCount(): int
    {
        try {
            return DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subHours(24))
                ->count();
        } catch (Exception) {
            return 0;
        }
    }

    private function getSystemHealth(): string
    {
        try {
            $queueSizes = $this->getQueueSizes();
            $maxQueueSize = count($queueSizes) > 0 ? max($queueSizes) : 0;
            $failedJobs = $this->getFailedJobsCount();
            $memoryUsage = $this->getMemoryUsage();

            $score = 0;
            $score += $maxQueueSize < 100 ? 1 : 0;
            $score += $failedJobs < 10 ? 1 : 0;
            $score += $memoryUsage < 80 ? 1 : 0;

            return match ($score) {
                3 => 'good',
                2 => 'fair',
                1 => 'poor',
                default => 'critical',
            };
        } catch (Exception) {
            return 'unknown';
        }
    }

    private function getMemoryUsage(): float
    {
        try {
            $memoryUsed = memory_get_usage(true);
            $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

            if ($memoryLimit <= 0) {
                return 0.0;
            }

            return ($memoryUsed / $memoryLimit) * 100;
        } catch (Exception) {
            return 0.0;
        }
    }

    private function getProcessingRate(): int
    {
        try {
            // Estimate based on recent job processing
            // This is a simplified calculation
            $recentJobs = DB::table('jobs')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->count();

            return $recentJobs * 12; // Extrapolate to jobs per hour, then per minute
        } catch (Exception) {
            return 0;
        }
    }

    /**
     * @param  array<string, mixed>  $queueSizes
     * @return list<array<int, mixed>>
     */
    private function formatQueueData(array $queueSizes): array
    {
        $formatted = [];

        foreach ($queueSizes as $queue => $size) {
            $status = $this->getQueueStatus($queue, $size);
            $formatted[] = [$queue, $size, $status];
        }

        return $formatted;
    }

    private function getQueueStatus(string $queue, int $size): string
    {
        $thresholds = [
            'notifications' => 500,
            'payments' => 50,
            'exports' => 25,
            'bulk' => 100,
            'default' => 100,
        ];

        $threshold = $thresholds[$queue] ?? $thresholds['default'];

        if ($size === 0) {
            return '✅ Empty';
        }

        if ($size <= $threshold * 0.5) {
            return '✅ Normal';
        }

        if ($size <= $threshold) {
            return '⚠️  Busy';
        }

        return '❌ Overloaded';
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
}
