<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Seeder\SuperSeed;

use Illuminate\Console\Command;

/**
 * Real-Time Performance Monitor for Enterprise Super Seeding
 *
 * Features:
 * - Real-time throughput calculation
 * - Memory usage tracking
 * - Batch performance analysis
 * - Resource utilization monitoring
 * - Performance optimization suggestions
 */
class PerformanceMonitor
{
    // Timing metrics
    private float $startTime;

    /** @var array<float> */
    private array $batchTimes = [];

    // Throughput metrics
    private int $totalRecords = 0;

    private int $totalBatches = 0;

    /** @var array<float> */
    private array $throughputHistory = [];

    // Memory metrics
    private int $startMemory;

    private int $peakMemory = 0;

    // Performance tracking
    /** @var array<int> */
    private array $batchSizes = [];

    /** @var array<float> */
    private array $batchDurations = [];

    private float $slowestBatch = 0;

    private float $fastestBatch = PHP_FLOAT_MAX;

    // Resource utilization
    /** @var array<int, array<string, mixed>> */
    private array $systemMetrics = [];

    public function __construct(private readonly Command $command) {}

    /**
     * Start monitoring session
     */
    public function start(): void
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->peakMemory = $this->startMemory;

        // Clear any previous session data
        $this->batchTimes = [];
        $this->throughputHistory = [];
        $this->batchSizes = [];
        $this->batchDurations = [];
        $this->systemMetrics = [];

        $this->recordSystemMetrics();
    }

    /**
     * Record batch completion with performance metrics
     */
    public function recordBatch(int $recordCount, float $duration): void
    {
        $currentTime = microtime(true);

        // Update counters
        $this->totalRecords += $recordCount;
        $this->totalBatches++;

        // Track timing
        $this->batchTimes[] = $duration;
        $this->batchDurations[] = $duration;
        $this->batchSizes[] = $recordCount;

        // Track performance extremes
        $this->slowestBatch = max($this->slowestBatch, $duration);
        $this->fastestBatch = min($this->fastestBatch, $duration);

        // Calculate real-time throughput
        $timeSinceStart = $currentTime - $this->startTime;
        $overallThroughput = $timeSinceStart > 0 ? $this->totalRecords / $timeSinceStart : 0;
        $this->throughputHistory[] = $overallThroughput;

        // Track memory usage
        $currentMemory = memory_get_usage(true);
        $this->peakMemory = max($this->peakMemory, $currentMemory);

        // Record system metrics periodically
        if ($this->totalBatches % 10 === 0) {
            $this->recordSystemMetrics();
        }
    }

    /**
     * Record memory usage snapshot
     */
    public function recordMemoryUsage(int $current, int $peak): void
    {
        $this->peakMemory = max($this->peakMemory, $peak);
    }

    /**
     * Get current real-time metrics
     */
    /** @return array<string, mixed> */
    public function getCurrentMetrics(): array
    {
        $currentTime = microtime(true);
        $elapsedTime = $currentTime - $this->startTime;

        $avgBatchTime = $this->batchTimes === [] ?
            0 : array_sum($this->batchTimes) / count($this->batchTimes);

        $throughputPerSecond = $elapsedTime > 0 ? $this->totalRecords / $elapsedTime : 0;

        // Calculate recent throughput (last 10 batches)
        $recentThroughput = $this->calculateRecentThroughput();

        return [
            'total_records' => $this->totalRecords,
            'total_batches' => $this->totalBatches,
            'elapsed_time' => $elapsedTime,
            'avg_batch_time' => $avgBatchTime,
            'throughput_per_second' => $throughputPerSecond,
            'recent_throughput' => $recentThroughput,
            'current_memory' => memory_get_usage(true),
            'peak_memory' => $this->peakMemory,
            'memory_efficiency' => $this->calculateMemoryEfficiency(),
            'estimated_completion' => $this->estimateCompletion(),
        ];
    }

    /**
     * Get final comprehensive metrics
     */
    /** @return array<string, mixed> */
    public function getFinalMetrics(): array
    {
        $totalTime = microtime(true) - $this->startTime;

        $metrics = [
            'total_time' => $totalTime,
            'total_records' => $this->totalRecords,
            'total_batches' => $this->totalBatches,
            'records_per_second' => $totalTime > 0 ? $this->totalRecords / $totalTime : 0,
            'avg_batch_time' => $this->batchTimes === [] ?
                0 : array_sum($this->batchTimes) / count($this->batchTimes),
            'fastest_batch' => $this->fastestBatch === PHP_FLOAT_MAX ? 0 : $this->fastestBatch,
            'slowest_batch' => $this->slowestBatch,
            'peak_memory' => $this->peakMemory,
            'memory_efficiency' => $this->calculateMemoryEfficiency(),
            'avg_batch_size' => $this->batchSizes === [] ?
                0 : array_sum($this->batchSizes) / count($this->batchSizes),
            'performance_consistency' => $this->calculatePerformanceConsistency(),
            'throughput_trend' => $this->analyzeThroughputTrend(),
        ];

        // Add performance analysis
        $metrics['performance_analysis'] = $this->generatePerformanceAnalysis($metrics);

        return $metrics;
    }

    /**
     * Finish monitoring session
     */
    public function finish(): void
    {
        $this->recordSystemMetrics();

        // Log final metrics
        $finalMetrics = $this->getFinalMetrics();

        $this->command->info('=== Performance Analysis ===');

        if (! empty($finalMetrics['performance_analysis'])) {
            foreach ($finalMetrics['performance_analysis'] as $insight) {
                $this->command->line("• {$insight}");
            }
        }
    }

    /**
     * Calculate recent throughput based on last few batches
     */
    private function calculateRecentThroughput(int $batchWindow = 10): float
    {
        if (count($this->batchSizes) < $batchWindow) {
            return 0;
        }

        $recentSizes = array_slice($this->batchSizes, -$batchWindow);
        $recentDurations = array_slice($this->batchDurations, -$batchWindow);

        $totalRecords = array_sum($recentSizes);
        $totalTime = array_sum($recentDurations);

        return $totalTime > 0 ? $totalRecords / $totalTime : 0;
    }

    /**
     * Calculate memory efficiency percentage
     */
    private function calculateMemoryEfficiency(): float
    {
        if ($this->totalRecords === 0) {
            return 0;
        }

        $memoryPerRecord = $this->peakMemory / $this->totalRecords;

        // Efficiency based on optimal memory per record (lower is better)
        // Assuming 1KB per record as optimal
        $optimalMemoryPerRecord = 1024;

        if ($memoryPerRecord <= $optimalMemoryPerRecord) {
            return 100.0;
        }

        return max(0, 100 - (($memoryPerRecord / $optimalMemoryPerRecord - 1) * 50));
    }

    /**
     * Estimate completion time for remaining records
     */
    private function estimateCompletion(): float
    {
        $this->calculateRecentThroughput();

        // This would need to be set from the seeder
        // For now, return 0 as we don't know remaining records
        return 0;
    }

    /**
     * Calculate performance consistency (lower standard deviation is better)
     */
    private function calculatePerformanceConsistency(): float
    {
        if (count($this->batchDurations) < 2) {
            return 100.0;
        }

        $mean = array_sum($this->batchDurations) / count($this->batchDurations);
        $variance = 0;

        foreach ($this->batchDurations as $duration) {
            $variance += ($duration - $mean) ** 2;
        }

        $variance /= count($this->batchDurations);
        $standardDeviation = sqrt($variance);

        // Convert to consistency percentage (100% = perfect consistency)
        if ($mean === 0) {
            return 100.0;
        }

        $coefficientOfVariation = $standardDeviation / $mean;

        return max(0, 100 - ($coefficientOfVariation * 100));
    }

    /**
     * Analyze throughput trend (improving, declining, stable)
     */
    private function analyzeThroughputTrend(): string
    {
        if (count($this->throughputHistory) < 10) {
            return 'insufficient_data';
        }

        $firstHalf = array_slice($this->throughputHistory, 0, (int) (count($this->throughputHistory) / 2));
        $secondHalf = array_slice($this->throughputHistory, (int) (count($this->throughputHistory) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        $improvement = (($secondAvg - $firstAvg) / $firstAvg) * 100;

        if ($improvement > 10) {
            return 'improving';
        }

        if ($improvement < -10) {
            return 'declining';
        }

        return 'stable';
    }

    /**
     * Generate performance insights and recommendations
     */
    /** @param array<string, mixed> $metrics */
    /** @return array<string> */
    /**
     * @param  array<string, mixed>  $metrics
     * @return array<int, string>
     */
    private function generatePerformanceAnalysis(array $metrics): array
    {
        $analysis = [];

        // Throughput analysis
        if ($metrics['records_per_second'] > 10000) {
            $analysis[] = 'Excellent throughput: ' . number_format($metrics['records_per_second']) . ' records/sec';
        } elseif ($metrics['records_per_second'] > 5000) {
            $analysis[] = 'Good throughput: ' . number_format($metrics['records_per_second']) . ' records/sec';
        } else {
            $analysis[] = 'Low throughput: ' . number_format($metrics['records_per_second']) . ' records/sec - consider batch size optimization';
        }

        // Memory efficiency analysis
        if ($metrics['memory_efficiency'] > 80) {
            $analysis[] = "Excellent memory efficiency: {$metrics['memory_efficiency']}%";
        } elseif ($metrics['memory_efficiency'] > 60) {
            $analysis[] = "Good memory efficiency: {$metrics['memory_efficiency']}%";
        } else {
            $analysis[] = "Poor memory efficiency: {$metrics['memory_efficiency']}% - consider reducing batch sizes";
        }

        // Performance consistency
        if ($metrics['performance_consistency'] > 80) {
            $analysis[] = 'Consistent performance across batches';
        } else {
            $analysis[] = 'Inconsistent batch performance - system may be under resource pressure';
        }

        // Batch timing analysis
        $avgBatchTime = $metrics['avg_batch_time'];
        if ($avgBatchTime > 5.0) {
            $analysis[] = "Slow average batch time: {$avgBatchTime}s - consider smaller batch sizes";
        } elseif ($avgBatchTime < 0.1) {
            $analysis[] = "Very fast batches: {$avgBatchTime}s - could increase batch size for better throughput";
        }

        return $analysis;
    }

    /**
     * Record system resource metrics
     */
    private function recordSystemMetrics(): void
    {
        $metrics = [
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ];

        // Add CPU load if available
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $metrics['cpu_load'] = $load[0] ?? 0;
        }

        $this->systemMetrics[] = $metrics;
    }

    /**
     * Get system resource utilization summary
     *
     * @return array<string, mixed>
     */
    public function getSystemMetrics(): array
    {
        if ($this->systemMetrics === []) {
            return [];
        }

        $latest = end($this->systemMetrics);

        return [
            'current_memory' => $latest['memory_usage'],
            'peak_memory' => $latest['memory_peak'],
            'cpu_load' => $latest['cpu_load'] ?? 0,
            'metrics_collected' => count($this->systemMetrics),
        ];
    }
}
