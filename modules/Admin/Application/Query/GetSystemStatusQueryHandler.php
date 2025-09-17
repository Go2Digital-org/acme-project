<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Query;

use DateTimeImmutable;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Application\ReadModel\CacheStatusReadModel;
use Modules\Admin\Application\ReadModel\QueueStatusReadModel;
use Modules\Admin\Application\ReadModel\StorageStatusReadModel;
use Modules\Admin\Application\ReadModel\SystemStatusReadModel;

final class GetSystemStatusQueryHandler
{
    public function handle(GetSystemStatusQuery $query): SystemStatusReadModel
    {
        $healthChecks = $this->performHealthChecks();
        $performanceMetrics = $query->includePerformanceMetrics ? $this->getPerformanceMetrics() : [];
        $queueStatus = $query->includeQueueStatus ? $this->getQueueStatus() : new QueueStatusReadModel(0, 0, [], 'unknown');
        $cacheStatus = $query->includeCacheStatus ? $this->getCacheStatus() : new CacheStatusReadModel('unknown', false, [], 'unknown');
        $storageStatus = $query->includeStorageStatus ? $this->getStorageStatus() : new StorageStatusReadModel([], 0, 0, 0, 0);

        $overallStatus = $this->determineOverallStatus($healthChecks, $queueStatus, $cacheStatus, $storageStatus);

        return new SystemStatusReadModel(
            status: $overallStatus,
            healthChecks: $healthChecks,
            performanceMetrics: $performanceMetrics,
            queueStatus: $queueStatus,
            cacheStatus: $cacheStatus,
            storageStatus: $storageStatus,
            lastChecked: new DateTimeImmutable
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function performHealthChecks(): array
    {
        $checks = [];

        // Database check
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'response_time' => $this->measureDatabaseResponseTime(),
            ];
        } catch (Exception $e) {
            $checks['database'] = [
                'status' => 'critical',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'response_time' => null,
            ];
        }

        // Cache check
        try {
            Cache::put('health_check', 'test', 1);
            $value = Cache::get('health_check');
            Cache::forget('health_check');

            $checks['cache'] = [
                'status' => $value === 'test' ? 'healthy' : 'warning',
                'message' => $value === 'test' ? 'Cache is working properly' : 'Cache test failed',
            ];
        } catch (Exception $e) {
            $checks['cache'] = [
                'status' => 'critical',
                'message' => 'Cache check failed: ' . $e->getMessage(),
            ];
        }

        // Queue check
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            $status = 'healthy';
            if ($failedJobs > 50) {
                $status = 'critical';
            } elseif ($failedJobs > 10 || $pendingJobs > 1000) {
                $status = 'warning';
            }

            $checks['queue'] = [
                'status' => $status,
                'message' => "Pending: {$pendingJobs}, Failed: {$failedJobs}",
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
            ];
        } catch (Exception $e) {
            $checks['queue'] = [
                'status' => 'critical',
                'message' => 'Queue check failed: ' . $e->getMessage(),
            ];
        }

        return $checks;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getPerformanceMetrics(): array
    {
        return [
            'memory' => [
                'current_usage' => memory_get_usage(true),
                'peak_usage' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit'),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'extensions' => get_loaded_extensions(),
                'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status() !== false,
            ],
            'server' => [
                'load_average' => sys_getloadavg(),
                'uptime' => $this->getSystemUptime(),
                'disk_usage' => $this->getDiskUsage(),
            ],
        ];
    }

    private function getQueueStatus(): QueueStatusReadModel
    {
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            // Get queue workers (simplified - in real implementation you'd check actual workers)
            $queueWorkers = [
                ['name' => 'default', 'status' => 'active', 'jobs_processed' => 0],
            ];

            $status = match (true) {
                $failedJobs > 50 => 'critical',
                $failedJobs > 10 || $pendingJobs > 1000 => 'warning',
                default => 'healthy'
            };

            return new QueueStatusReadModel(
                pendingJobs: $pendingJobs,
                failedJobs: $failedJobs,
                queueWorkers: $queueWorkers,
                status: $status
            );
        } catch (Exception) {
            return new QueueStatusReadModel(0, 0, [], 'error');
        }
    }

    private function getCacheStatus(): CacheStatusReadModel
    {
        $driver = config('cache.default');
        $isConnected = false;
        $stats = [];
        $status = 'unknown';

        try {
            if ($driver === 'redis') {
                $redis = Redis::connection();
                $info = $redis->info();
                $isConnected = true;
                $stats = [
                    'memory_usage' => $info['used_memory_human'] ?? 'unknown',
                    'connected_clients' => $info['connected_clients'] ?? 0,
                    'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                ];
                $status = 'healthy';
            } else {
                // For file/database cache, just test if we can read/write
                Cache::put('cache_test', 'test', 1);
                $isConnected = Cache::get('cache_test') === 'test';
                Cache::forget('cache_test');
                $status = $isConnected ? 'healthy' : 'warning';
            }
        } catch (Exception) {
            $status = 'critical';
        }

        return new CacheStatusReadModel(
            driver: $driver,
            isConnected: $isConnected,
            stats: $stats,
            status: $status
        );
    }

    private function getStorageStatus(): StorageStatusReadModel
    {
        $disks = [];
        $totalSpace = 0;
        $usedSpace = 0;

        foreach (config('filesystems.disks') as $name => $config) {
            try {
                if ($name === 'local' || $name === 'public') {
                    $path = $config['root'] ?? storage_path('app');
                    $free = disk_free_space($path);
                    $total = disk_total_space($path);
                    $used = $total - $free;

                    $disks[$name] = [
                        'total' => $total,
                        'used' => $used,
                        'free' => $free,
                        'usage_percentage' => $total > 0 ? ($used / $total) * 100 : 0,
                    ];

                    $totalSpace += $total;
                    $usedSpace += $used;
                }
            } catch (Exception $e) {
                $disks[$name] = ['error' => $e->getMessage()];
            }
        }

        $freeSpace = $totalSpace - $usedSpace;
        $usagePercentage = $totalSpace > 0 ? ($usedSpace / $totalSpace) * 100 : 0;

        return new StorageStatusReadModel(
            disks: $disks,
            totalSpace: (int) $totalSpace,
            usedSpace: (int) $usedSpace,
            freeSpace: (int) $freeSpace,
            usagePercentage: $usagePercentage
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $healthChecks
     */
    private function determineOverallStatus(
        array $healthChecks,
        QueueStatusReadModel $queueStatus,
        CacheStatusReadModel $cacheStatus,
        StorageStatusReadModel $storageStatus
    ): string {
        $criticalCount = 0;
        $warningCount = 0;

        // Check health checks
        foreach ($healthChecks as $check) {
            if ($check['status'] === 'critical') {
                $criticalCount++;
            } elseif ($check['status'] === 'warning') {
                $warningCount++;
            }
        }

        // Check subsystems
        if (in_array($queueStatus->status, ['critical', 'error'])) {
            $criticalCount++;
        } elseif ($queueStatus->status === 'warning') {
            $warningCount++;
        }

        if ($cacheStatus->status === 'critical') {
            $criticalCount++;
        } elseif ($cacheStatus->status === 'warning') {
            $warningCount++;
        }

        if ($storageStatus->usagePercentage > 90) {
            $criticalCount++;
        } elseif ($storageStatus->usagePercentage > 80) {
            $warningCount++;
        }

        return match (true) {
            $criticalCount > 0 => 'critical',
            $warningCount > 0 => 'warning',
            default => 'healthy'
        };
    }

    private function measureDatabaseResponseTime(): float
    {
        $start = microtime(true);
        DB::select('SELECT 1');

        return (microtime(true) - $start) * 1000; // milliseconds
    }

    private function getSystemUptime(): ?string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = shell_exec('uptime -p');

            return $uptime ? trim($uptime) : null;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function getDiskUsage(): array
    {
        $path = '/';
        $total = disk_total_space($path);
        $free = disk_free_space($path);

        return [
            'total' => $total,
            'free' => $free,
            'used' => $total - $free,
            'usage_percentage' => $total > 0 ? (($total - $free) / $total) * 100 : 0,
        ];
    }
}
