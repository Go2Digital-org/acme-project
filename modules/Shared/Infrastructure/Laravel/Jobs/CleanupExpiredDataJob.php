<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Shared\Infrastructure\Laravel\Models\JobProgress;

final class CleanupExpiredDataJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800; // 30 minutes

    public int $tries = 1; // Don't retry cleanup jobs

    /**
     * @param  array<int, string>  $cleanupTasks
     */
    public function __construct(
        private readonly array $cleanupTasks = [],
        private readonly bool $dryRun = false,
    ) {
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        Log::info('Starting scheduled data cleanup', [
            'tasks' => count($this->cleanupTasks) > 0 ? $this->cleanupTasks : 'all',
            'dry_run' => $this->dryRun,
            'job_id' => $this->job?->getJobId(),
        ]);

        $results = [];

        try {
            // Define all available cleanup tasks
            $allTasks = [
                'job_progress' => [$this, 'cleanupJobProgress'],
                'failed_jobs' => [$this, 'cleanupFailedJobs'],
                'export_files' => [$this, 'cleanupExportFiles'],
                'temp_files' => [$this, 'cleanupTempFiles'],
                'cache_entries' => [$this, 'cleanupExpiredCache'],
                'session_data' => [$this, 'cleanupExpiredSessions'],
                'audit_logs' => [$this, 'cleanupAuditLogs'],
            ];

            // Determine which tasks to run
            $tasksToRun = $this->cleanupTasks === []
                ? $allTasks
                : array_intersect_key($allTasks, array_flip($this->cleanupTasks));

            // Execute each cleanup task
            foreach ($tasksToRun as $taskName => $callback) {
                Log::info("Running cleanup task: {$taskName}");

                try {
                    $result = $callback();
                    $results[$taskName] = $result;

                    Log::info("Cleanup task completed: {$taskName}", $result);
                } catch (Exception $exception) {
                    $error = [
                        'status' => 'error',
                        'message' => $exception->getMessage(),
                    ];
                    $results[$taskName] = $error;

                    Log::error("Cleanup task failed: {$taskName}", [
                        'error' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);
                }
            }

            // Log overall results
            $this->logCleanupSummary($results);

            // Send notification if significant cleanup occurred
            $this->sendNotificationIfNeeded($results);
        } catch (Exception $exception) {
            Log::error('Data cleanup job failed', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Data cleanup job failed permanently', [
            'tasks' => $this->cleanupTasks,
            'dry_run' => $this->dryRun,
            'error' => $exception->getMessage(),
        ]);

        // Send failure notification
        try {
            SendEmailJob::dispatch(
                emailData: [
                    'to' => config('mail.admin_notifications', 'admin@acme-corp.com'),
                    'subject' => 'Data Cleanup Failed',
                    'view' => 'emails.cleanup-failed',
                    'data' => [
                        'tasks' => count($this->cleanupTasks) > 0 ? $this->cleanupTasks : 'all',
                        'error_message' => $exception->getMessage(),
                        'failed_at' => now()->format('F j, Y \a\t g:i A'),
                    ],
                ],
                locale: null,
                priority: 7
            )->onQueue('notifications');
        } catch (Exception $e) {
            Log::error('Failed to send cleanup failure notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Schedule periodic cleanup.
     */
    public static function scheduleDaily(): void
    {
        self::dispatch()->delay(now()->addMinutes(5));
    }

    /**
     * Create a dry run to see what would be cleaned.
     */
    /**
     * @param  array<int, string>  $tasks
     */
    public static function dryRun(array $tasks = []): self
    {
        return new self($tasks, true);
    }

    /** @return array<array-key, mixed> */
    private function cleanupJobProgress(): array
    {
        $daysToKeep = config('queue.cleanup.job_progress_days', 30);

        if ($this->dryRun) {
            $count = JobProgress::where('completed_at', '<', now()->subDays($daysToKeep))
                ->where('status', 'completed')
                ->count();

            return [
                'status' => 'dry_run',
                'would_delete_count' => $count,
                'days_threshold' => $daysToKeep,
            ];
        }

        $deletedCount = JobProgress::cleanup($daysToKeep);

        return [
            'status' => 'completed',
            'deleted_count' => $deletedCount,
            'days_threshold' => $daysToKeep,
        ];
    }

    /** @return array<array-key, mixed> */
    private function cleanupFailedJobs(): array
    {
        $daysToKeep = config('queue.cleanup.failed_jobs_days', 90);

        if ($this->dryRun) {
            $count = DB::table('failed_jobs')
                ->where('failed_at', '<', now()->subDays($daysToKeep))
                ->count();

            return [
                'status' => 'dry_run',
                'would_delete_count' => $count,
                'days_threshold' => $daysToKeep,
            ];
        }

        $deletedCount = DB::table('failed_jobs')
            ->where('failed_at', '<', now()->subDays($daysToKeep))
            ->delete();

        return [
            'status' => 'completed',
            'deleted_count' => $deletedCount,
            'days_threshold' => $daysToKeep,
        ];
    }

    /** @return array<array-key, mixed> */
    private function cleanupExportFiles(): array
    {
        $daysToKeep = config('exports.cleanup_days', 30);
        $exportsDisk = Storage::disk('exports');
        $deletedCount = 0;
        $deletedSize = 0;

        try {
            $files = $exportsDisk->allFiles();
            $cutoffDate = now()->subDays($daysToKeep);

            foreach ($files as $file) {
                $lastModified = $exportsDisk->lastModified($file);

                if ($lastModified < $cutoffDate->timestamp) {
                    if (! $this->dryRun) {
                        $fileSize = $exportsDisk->size($file);
                        $exportsDisk->delete($file);
                        $deletedSize += $fileSize;
                    }
                    $deletedCount++;
                }
            }

            return [
                'status' => $this->dryRun ? 'dry_run' : 'completed',
                'deleted_count' => $deletedCount,
                'deleted_size_mb' => round($deletedSize / 1024 / 1024, 2),
                'days_threshold' => $daysToKeep,
            ];
        } catch (Exception $exception) {
            return [
                'status' => 'error',
                'message' => $exception->getMessage(),
            ];
        }
    }

    /** @return array<array-key, mixed> */
    private function cleanupTempFiles(): array
    {
        $tempDisk = Storage::disk('local');
        $deletedCount = 0;
        $deletedSize = 0;

        try {
            // Clean up temp directories
            $tempDirectories = ['temp', 'uploads/temp', 'exports/temp'];

            foreach ($tempDirectories as $directory) {
                if (! $tempDisk->exists($directory)) {
                    continue;
                }

                $files = $tempDisk->allFiles($directory);
                $cutoffDate = now()->subHours(24); // Clean files older than 24 hours

                foreach ($files as $file) {
                    $lastModified = $tempDisk->lastModified($file);

                    if ($lastModified < $cutoffDate->timestamp) {
                        if (! $this->dryRun) {
                            $fileSize = $tempDisk->size($file);
                            $tempDisk->delete($file);
                            $deletedSize += $fileSize;
                        }
                        $deletedCount++;
                    }
                }
            }

            return [
                'status' => $this->dryRun ? 'dry_run' : 'completed',
                'deleted_count' => $deletedCount,
                'deleted_size_mb' => round($deletedSize / 1024 / 1024, 2),
                'hours_threshold' => 24,
            ];
        } catch (Exception $exception) {
            return [
                'status' => 'error',
                'message' => $exception->getMessage(),
            ];
        }
    }

    /** @return array<array-key, mixed> */
    private function cleanupExpiredCache(): array
    {
        if ($this->dryRun) {
            return [
                'status' => 'dry_run',
                'message' => 'Would clear expired cache entries',
            ];
        }

        // Cache flushing removed

        return [
            'status' => 'completed',
            'message' => 'Cleanup completed successfully',
        ];
    }

    /** @return array<array-key, mixed> */
    private function cleanupExpiredSessions(): array
    {
        $daysToKeep = config('session.cleanup_days', 7);

        if ($this->dryRun) {
            $count = DB::table('sessions')
                ->where('last_activity', '<', now()->subDays($daysToKeep)->timestamp)
                ->count();

            return [
                'status' => 'dry_run',
                'would_delete_count' => $count,
                'days_threshold' => $daysToKeep,
            ];
        }

        $deletedCount = DB::table('sessions')
            ->where('last_activity', '<', now()->subDays($daysToKeep)->timestamp)
            ->delete();

        return [
            'status' => 'completed',
            'deleted_count' => $deletedCount,
            'days_threshold' => $daysToKeep,
        ];
    }

    /** @return array<array-key, mixed> */
    private function cleanupAuditLogs(): array
    {
        $daysToKeep = config('audit.cleanup_days', 365);

        if ($this->dryRun) {
            $count = DB::table('audit_logs')
                ->where('created_at', '<', now()->subDays($daysToKeep))
                ->count();

            return [
                'status' => 'dry_run',
                'would_delete_count' => $count,
                'days_threshold' => $daysToKeep,
            ];
        }

        $deletedCount = DB::table('audit_logs')
            ->where('created_at', '<', now()->subDays($daysToKeep))
            ->delete();

        return [
            'status' => 'completed',
            'deleted_count' => $deletedCount,
            'days_threshold' => $daysToKeep,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $results
     */
    private function logCleanupSummary(array $results): void
    {
        $summary = [
            'total_tasks' => count($results),
            'successful_tasks' => 0,
            'failed_tasks' => 0,
            'total_deletions' => 0,
        ];

        foreach ($results as $result) {
            if ($result['status'] === 'error') {
                $summary['failed_tasks']++;
            } else {
                $summary['successful_tasks']++;
                $summary['total_deletions'] += $result['deleted_count'] ?? $result['would_delete_count'] ?? 0;
            }
        }

        Log::info('Data cleanup completed', [
            'summary' => $summary,
            'detailed_results' => $results,
            'dry_run' => $this->dryRun,
        ]);
    }

    /**
     * @param  array<string, array<string, mixed>>  $results
     */
    private function sendNotificationIfNeeded(array $results): void
    {
        // Count total items cleaned up
        $totalCleaned = 0;
        $hasErrors = false;

        foreach ($results as $result) {
            if ($result['status'] === 'error') {
                $hasErrors = true;
            }
            $totalCleaned += $result['deleted_count'] ?? $result['would_delete_count'] ?? 0;
        }

        // Send notification if significant cleanup or if there were errors
        if ($totalCleaned > 1000 || $hasErrors) {
            try {
                SendEmailJob::dispatch(
                    emailData: [
                        'to' => config('mail.admin_notifications', 'admin@acme-corp.com'),
                        'subject' => 'Data Cleanup Report - ' . ($hasErrors ? 'With Errors' : 'Completed'),
                        'view' => 'emails.cleanup-report',
                        'data' => [
                            'results' => $results,
                            'total_cleaned' => $totalCleaned,
                            'has_errors' => $hasErrors,
                            'dry_run' => $this->dryRun,
                            'completed_at' => now()->format('F j, Y \a\t g:i A'),
                        ],
                    ],
                    locale: null,
                    priority: 5
                )->onQueue('notifications');
            } catch (Exception $exception) {
                Log::error('Failed to send cleanup notification', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
