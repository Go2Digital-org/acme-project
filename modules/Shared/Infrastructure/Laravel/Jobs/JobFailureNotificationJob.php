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

/**
 * Handles notifications and recovery actions for failed jobs
 */
final class JobFailureNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 60;

    public int $tries = 3;

    public bool $deleteWhenMissingModels = true;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly string $failedJobId,
        private readonly string $jobClass,
        private readonly string $queueName,
        private readonly string $errorMessage,
        /** @var array<string, mixed> */
        private readonly array $jobPayload = [],
        /** @var array<string, mixed> */
        private readonly array $metadata = []
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        try {
            Log::info('Processing job failure notification', [
                'failed_job_id' => $this->failedJobId,
                'job_class' => $this->jobClass,
                'queue' => $this->queueName,
            ]);

            // Analyze failure pattern
            $failurePattern = $this->analyzeFailurePattern();

            // Determine notification priority
            $priority = $this->determineNotificationPriority($failurePattern);

            // Send appropriate notifications
            $this->sendFailureNotifications($priority, $failurePattern);

            // Attempt auto-recovery if applicable
            if ($this->shouldAttemptAutoRecovery($failurePattern)) {
                $this->attemptAutoRecovery();
            }

            // Update failure metrics
            $this->updateFailureMetrics();

        } catch (Exception $exception) {
            Log::error('Failed to process job failure notification', [
                'failed_job_id' => $this->failedJobId,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::critical('Job failure notification job failed', [
            'failed_job_id' => $this->failedJobId,
            'job_class' => $this->jobClass,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function analyzeFailurePattern(): array
    {
        $recentFailures = $this->getRecentFailures();
        $jobClassFailures = $this->getJobClassFailures();
        $queueFailures = $this->getQueueFailures();

        return [
            'failure_frequency' => $this->calculateFailureFrequency($recentFailures),
            'job_class_pattern' => $this->analyzeJobClassPattern($jobClassFailures),
            'queue_pattern' => $this->analyzeQueuePattern($queueFailures),
            'error_pattern' => $this->analyzeErrorPattern(),
            'system_health' => $this->assessSystemHealth(),
        ];
    }

    /**
     * @param  array<string, mixed>  $failurePattern
     */
    private function determineNotificationPriority(array $failurePattern): string
    {
        // Critical priority triggers
        if ($this->isCriticalJob() ||
            $failurePattern['failure_frequency'] === 'high' ||
            $failurePattern['system_health'] === 'poor') {
            return 'critical';
        }

        // High priority triggers
        if ($this->isHighPriorityJob() ||
            $failurePattern['job_class_pattern'] === 'recurring' ||
            $failurePattern['queue_pattern'] === 'degraded') {
            return 'high';
        }

        // Medium priority triggers
        if ($failurePattern['error_pattern'] === 'known_issue' ||
            $failurePattern['failure_frequency'] === 'moderate') {
            return 'medium';
        }

        return 'low';
    }

    /**
     * @param  array<string, mixed>  $failurePattern
     */
    private function sendFailureNotifications(string $priority, array $failurePattern): void
    {
        $recipients = $this->getNotificationRecipients($priority);

        foreach ($recipients as $recipient) {
            $this->sendIndividualNotification($recipient, $priority, $failurePattern);
        }
    }

    /**
     * @param  array<string, mixed>  $recipient
     * @param  array<string, mixed>  $failurePattern
     */
    private function sendIndividualNotification(array $recipient, string $priority, array $failurePattern): void
    {
        $template = match ($priority) {
            'critical' => 'emails.monitoring.critical-job-failure',
            'high' => 'emails.monitoring.high-priority-job-failure',
            'medium' => 'emails.monitoring.job-failure',
            default => 'emails.monitoring.low-priority-job-failure',
        };

        SendEmailJob::dispatch(
            emailData: [
                'to' => $recipient['email'],
                'subject' => $this->buildSubject($priority),
                'view' => $template,
                'data' => [
                    'failed_job_id' => $this->failedJobId,
                    'job_class' => $this->jobClass,
                    'queue' => $this->queueName,
                    'error_message' => $this->errorMessage,
                    'failure_pattern' => $failurePattern,
                    'priority' => $priority,
                    'timestamp' => now(),
                    'job_payload' => $this->sanitizePayload(),
                    'metadata' => $this->metadata,
                    'actions' => $this->getRecommendedActions($failurePattern),
                ],
            ],
            locale: null,
            priority: $priority === 'high' ? 9 : ($priority === 'medium' ? 6 : 3)
        )->onQueue('notifications');
    }

    /**
     * @param  array<string, mixed>  $failurePattern
     */
    private function shouldAttemptAutoRecovery(array $failurePattern): bool
    {
        // Don't auto-recover critical jobs or high-frequency failures
        if ($this->isCriticalJob() || $failurePattern['failure_frequency'] === 'high') {
            return false;
        }

        // Check if this is a known recoverable error
        $recoverableErrors = [
            'connection timeout',
            'temporary network error',
            'rate limit exceeded',
            'service temporarily unavailable',
        ];

        foreach ($recoverableErrors as $pattern) {
            if (stripos($this->errorMessage, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    private function attemptAutoRecovery(): void
    {
        try {
            // Get the failed job details
            $failedJob = DB::table('failed_jobs')
                ->where('uuid', $this->failedJobId)
                ->first();

            if (! $failedJob) {
                Log::warning('Failed job not found for auto-recovery', [
                    'failed_job_id' => $this->failedJobId,
                ]);

                return;
            }

            // Calculate recovery delay based on error type
            $recoveryDelay = $this->calculateRecoveryDelay();

            // Schedule retry with exponential backoff
            /** @phpstan-ignore-next-line property.notFound */
            $retryJob = unserialize($failedJob->payload);
            $retryJob->delay = $recoveryDelay;

            // Dispatch the retry
            dispatch($retryJob);

            // Mark original as recovered
            DB::table('failed_jobs')
                ->where('uuid', $this->failedJobId)
                ->update([
                    'failed_at' => now(),
                    /** @phpstan-ignore-next-line property.notFound */
                    'exception' => $failedJob->exception . "\n\n[AUTO-RECOVERY ATTEMPTED at " . now() . ']',
                ]);

            Log::info('Auto-recovery attempted for failed job', [
                'failed_job_id' => $this->failedJobId,
                'recovery_delay' => $recoveryDelay,
            ]);

        } catch (Exception $exception) {
            Log::error('Auto-recovery failed', [
                'failed_job_id' => $this->failedJobId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function updateFailureMetrics(): void
    {
        $date = now()->format('Y-m-d');
        $hour = now()->format('H');
        // Update daily metrics
        $dailyKey = "job_failures:daily:{$date}";
        $hourlyKey = "job_failures:hourly:{$date}:{$hour}";
        $jobClassKey = "job_failures:class:{$this->jobClass}:{$date}";
        $queueKey = "job_failures:queue:{$this->queueName}:{$date}";
        Cache::increment($dailyKey, 1);
        Cache::increment($hourlyKey, 1);
        Cache::increment($jobClassKey, 1);
        Cache::increment($queueKey, 1);
        // Set expiration for cleanup
        Cache::put($dailyKey, Cache::get($dailyKey, 0), now()->addDays(30));
        Cache::put($hourlyKey, Cache::get($hourlyKey, 0), now()->addDays(7));
        Cache::put($jobClassKey, Cache::get($jobClassKey, 0), now()->addDays(30));
        Cache::put($queueKey, Cache::get($queueKey, 0), now()->addDays(30));
    }

    /**
     * @return array<string, mixed>
     */
    private function getRecentFailures(): array
    {
        return DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subHours(1))
            ->get()
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function getJobClassFailures(): array
    {
        return DB::table('failed_jobs')
            ->where('payload', 'LIKE', '%' . $this->jobClass . '%')
            ->where('failed_at', '>=', now()->subHours(24))
            ->get()
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function getQueueFailures(): array
    {
        return DB::table('failed_jobs')
            ->where('queue', $this->queueName)
            ->where('failed_at', '>=', now()->subHours(24))
            ->get()
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $recentFailures
     */
    private function calculateFailureFrequency(array $recentFailures): string
    {
        $count = count($recentFailures);

        return match (true) {
            $count >= 20 => 'high',
            $count >= 10 => 'moderate',
            $count >= 5 => 'low',
            default => 'minimal',
        };
    }

    /**
     * @param  array<string, mixed>  $jobClassFailures
     */
    private function analyzeJobClassPattern(array $jobClassFailures): string
    {
        $count = count($jobClassFailures); // hours

        if ($count >= 5) {
            return 'recurring';
        }

        if ($count >= 2) {
            return 'occasional';
        }

        return 'isolated';
    }

    /**
     * @param  array<string, mixed>  $queueFailures
     */
    private function analyzeQueuePattern(array $queueFailures): string
    {
        $count = count($queueFailures);

        return match (true) {
            $count >= 15 => 'degraded',
            $count >= 5 => 'unstable',
            default => 'normal',
        };
    }

    private function analyzeErrorPattern(): string
    {
        $knownPatterns = [
            'connection' => ['connection', 'timeout', 'network'],
            'database' => ['deadlock', 'database', 'query'],
            'memory' => ['memory', 'out of memory', 'allocation'],
            'validation' => ['validation', 'invalid', 'constraint'],
            'external_service' => ['api', 'service unavailable', 'rate limit'],
        ];

        foreach ($knownPatterns as $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($this->errorMessage, $keyword) !== false) {
                    return 'known_issue';
                }
            }
        }

        return 'unknown';
    }

    private function assessSystemHealth(): string
    {
        $systemMetrics = Cache::get('queue_monitoring:system_health', []);

        // Simple health assessment based on recent metrics
        if (empty($systemMetrics)) {
            return 'unknown';
        }

        $healthScore = 0;
        $healthScore += isset($systemMetrics['memory_usage']) && $systemMetrics['memory_usage'] < 80 ? 1 : 0;
        $healthScore += isset($systemMetrics['queue_sizes']) && max($systemMetrics['queue_sizes']) < 100 ? 1 : 0;
        $healthScore += isset($systemMetrics['error_rate']) && $systemMetrics['error_rate'] < 5 ? 1 : 0;

        return match ($healthScore) {
            3 => 'good',
            2 => 'fair',
            1 => 'poor',
            default => 'critical',
        };
    }

    private function isCriticalJob(): bool
    {
        $criticalJobs = [
            'ProcessPaymentJob',
            'ProcessRefundJob',
            'SendPaymentConfirmationJob',
            'ProcessDonationJob',
        ];

        foreach ($criticalJobs as $criticalJob) {
            if (str_contains($this->jobClass, $criticalJob)) {
                return true;
            }
        }

        return false;
    }

    private function isHighPriorityJob(): bool
    {
        $highPriorityJobs = [
            'SendEmailJob',
            'GenerateTaxReceiptJob',
            'SendAdminNotificationJob',
        ];

        foreach ($highPriorityJobs as $highPriorityJob) {
            if (str_contains($this->jobClass, $highPriorityJob)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getNotificationRecipients(string $priority): array
    {
        $config = config('queue.monitoring.recipients', []);

        return match ($priority) {
            'critical' => $config['critical'] ?? [['email' => 'admin@example.com']],
            'high' => $config['high'] ?? [['email' => 'admin@example.com']],
            'medium' => $config['medium'] ?? [['email' => 'dev@example.com']],
            default => $config['low'] ?? [],
        };
    }

    private function buildSubject(string $priority): string
    {
        $prefix = match ($priority) {
            'critical' => '[CRITICAL]',
            'high' => '[HIGH]',
            'medium' => '[MEDIUM]',
            default => '[LOW]',
        };

        return "{$prefix} Job Failed: {$this->jobClass} on {$this->queueName} queue";
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizePayload(): array
    {
        // Remove sensitive information from payload
        $sensitiveKeys = ['password', 'token', 'secret', 'key', 'auth'];
        $sanitized = $this->jobPayload;

        foreach ($sensitiveKeys as $key) {
            unset($sanitized[$key]);
        }

        return $sanitized;
    }

    /**
     * @param  array<string, mixed>  $failurePattern
     * @return list<string>
     */
    private function getRecommendedActions(array $failurePattern): array
    {
        $actions = [];

        if ($failurePattern['error_pattern'] === 'known_issue') {
            $actions[] = 'Check service status and retry if resolved';
        }

        if ($failurePattern['failure_frequency'] === 'high') {
            $actions[] = 'Investigate system resources and scaling needs';
        }

        if ($failurePattern['system_health'] === 'poor') {
            $actions[] = 'Review system health metrics and performance';
        }

        if ($actions === []) {
            $actions[] = 'Review error details and consider manual retry';
        }

        return $actions;
    }

    private function calculateRecoveryDelay(): int
    {
        // Base delay in seconds
        $baseDelay = 300; // 5 minutes

        // Adjust based on error type
        if (stripos($this->errorMessage, 'rate limit') !== false) {
            return 3600; // 1 hour for rate limits
        }

        if (stripos($this->errorMessage, 'timeout') !== false) {
            return 600; // 10 minutes for timeouts
        }

        if (stripos($this->errorMessage, 'connection') !== false) {
            return 180; // 3 minutes for connection issues
        }

        return $baseDelay;
    }
}
