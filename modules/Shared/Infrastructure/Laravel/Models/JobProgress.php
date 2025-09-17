<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @property int $id
 * @property string $job_id
 * @property string $job_type
 * @property string $job_class
 * @property string $queue
 * @property int|null $user_id
 * @property string $status
 * @property int $progress_percentage
 * @property string|null $progress_message
 * @property int $total_items
 * @property int $processed_items
 * @property int $failed_items
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $failed_at
 * @property Carbon|null $estimated_completion_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property User|null $user
 *
 * @method static Builder<static>|JobProgress where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static static|null find($id, $columns = ['*'])
 * @method static static findOrFail($id, $columns = ['*'])
 * @method static Collection<int, static> all($columns = ['*'])
 * @method static int count($columns = '*')
 * @method static Builder<static>|JobProgress newModelQuery()
 * @method static Builder<static>|JobProgress newQuery()
 * @method static Builder<static>|JobProgress query()
 * @method static Builder<static>|JobProgress forUser(int $userId)
 * @method static Builder<static>|JobProgress byStatus(string $status)
 * @method static Builder<static>|JobProgress byJobType(string $jobType)
 * @method static Builder<static>|JobProgress running()
 * @method static Builder<static>|JobProgress completed()
 * @method static Builder<static>|JobProgress failed()
 * @method static Builder<static>|JobProgress recent(int $days = 7)
 * @method static Builder<static>|JobProgress whereCompletedAt($value)
 * @method static Builder<static>|JobProgress whereCreatedAt($value)
 * @method static Builder<static>|JobProgress whereEstimatedCompletionAt($value)
 * @method static Builder<static>|JobProgress whereFailedAt($value)
 * @method static Builder<static>|JobProgress whereFailedItems($value)
 * @method static Builder<static>|JobProgress whereId($value)
 * @method static Builder<static>|JobProgress whereJobClass($value)
 * @method static Builder<static>|JobProgress whereJobId($value)
 * @method static Builder<static>|JobProgress whereJobType($value)
 * @method static Builder<static>|JobProgress whereMetadata($value)
 * @method static Builder<static>|JobProgress whereProcessedItems($value)
 * @method static Builder<static>|JobProgress whereProgressMessage($value)
 * @method static Builder<static>|JobProgress whereProgressPercentage($value)
 * @method static Builder<static>|JobProgress whereQueue($value)
 * @method static Builder<static>|JobProgress whereStartedAt($value)
 * @method static Builder<static>|JobProgress whereStatus($value)
 * @method static Builder<static>|JobProgress whereTotalItems($value)
 * @method static Builder<static>|JobProgress whereUpdatedAt($value)
 * @method static Builder<static>|JobProgress whereUserId($value)
 *
 * @mixin Model
 */
final class JobProgress extends Model
{
    // Statuses
    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'job_progress';

    protected $fillable = [
        'job_id',
        'job_type',
        'job_class',
        'queue',
        'user_id',
        'status',
        'progress_percentage',
        'progress_message',
        'total_items',
        'processed_items',
        'failed_items',
        'metadata',
        'started_at',
        'completed_at',
        'failed_at',
        'estimated_completion_at',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  array<array-key, mixed>  $metadata
     */
    public static function createForJob(
        string $jobId,
        string $jobType,
        string $jobClass,
        string $queue,
        ?int $userId = null,
        array $metadata = [],
    ): self {
        /** @var self $jobProgress */
        $jobProgress = self::query()->create([
            'job_id' => $jobId,
            'job_type' => $jobType,
            'job_class' => $jobClass,
            'queue' => $queue,
            'user_id' => $userId,
            'status' => self::STATUS_PENDING,
            'progress_percentage' => 0,
            'progress_message' => 'Job queued...',
            'total_items' => 0,
            'processed_items' => 0,
            'failed_items' => 0,
            'metadata' => $metadata,
            'started_at' => now(),
        ]);

        return $jobProgress;
    }

    public function updateProgress(
        int $percentage,
        string $message,
        ?int $processedItems = null,
        ?int $totalItems = null,
        ?int $failedItems = null,
    ): void {
        $updates = [
            'progress_percentage' => min(100, max(0, $percentage)),
            'progress_message' => $message,
            'status' => $percentage >= 100 ? self::STATUS_COMPLETED : self::STATUS_RUNNING,
        ];

        if ($processedItems !== null) {
            $updates['processed_items'] = $processedItems;
        }

        if ($totalItems !== null) {
            $updates['total_items'] = $totalItems;
        }

        if ($failedItems !== null) {
            $updates['failed_items'] = $failedItems;
        }

        // Calculate estimated completion time
        if ($percentage > 0 && $percentage < 100 && $totalItems !== null && $processedItems !== null && $this->started_at !== null) {
            $remainingItems = $totalItems - $processedItems;
            $elapsedTime = now()->diffInSeconds($this->started_at);
            $itemsPerSecond = $processedItems / max($elapsedTime, 1);

            if ($itemsPerSecond > 0) {
                $remainingSeconds = (int) ($remainingItems / $itemsPerSecond);
                $updates['estimated_completion_at'] = now()->addSeconds($remainingSeconds);
            }
        }

        if ($percentage >= 100) {
            $updates['completed_at'] = now();
            $updates['estimated_completion_at'] = null;
        }

        $this->update($updates);
    }

    public function markAsStarted(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(string $message = 'Job completed successfully'): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'progress_percentage' => 100,
            'progress_message' => $message,
            'completed_at' => now(),
            'estimated_completion_at' => null,
        ]);
    }

    public function markAsFailed(string $message, ?string $errorDetails = null): void
    {
        /** @var array<array-key, mixed> $metadata */
        $metadata = $this->metadata ?? [];

        if ($errorDetails !== null) {
            $metadata['error_details'] = $errorDetails;
        }

        $this->update([
            'status' => self::STATUS_FAILED,
            'progress_message' => $message,
            'failed_at' => now(),
            'estimated_completion_at' => null,
            'metadata' => $metadata,
        ]);
    }

    public function markAsCancelled(string $reason = 'Job was cancelled'): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'progress_message' => $reason,
            'estimated_completion_at' => null,
        ]);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getElapsedTime(): ?float
    {
        if ($this->started_at === null) {
            return null;
        }

        $endTime = $this->completed_at ?? $this->failed_at ?? now();

        return $this->started_at->diffInSeconds($endTime);
    }

    public function getEstimatedRemainingTime(): ?float
    {
        if ($this->estimated_completion_at === null || $this->isCompleted() || $this->isFailed()) {
            return null;
        }

        return (float) max(0, now()->diffInSeconds($this->estimated_completion_at));
    }

    public function getHumanReadableJobType(): string
    {
        return match ($this->job_type) {
            'export_campaigns' => 'Campaign Export',
            'export_donations' => 'Donation Export',
            'generate_report' => 'Report Generation',
            'backup_database' => 'Database Backup',
            'process_payment_webhook' => 'Payment Webhook Processing',
            'process_donation' => 'Donation Processing',
            'send_email' => 'Email Sending',
            'send_bulk_notification' => 'Bulk Notification',
            'cleanup_expired_data' => 'Data Cleanup',
            default => Str::title(str_replace('_', ' ', $this->job_type)),
        };
    }

    /** @return array<array-key, mixed> */
    public function toProgressArray(): array
    {
        return [
            'id' => $this->id,
            'job_id' => $this->job_id,
            'job_type' => $this->job_type,
            'job_type_human' => $this->getHumanReadableJobType(),
            'queue' => $this->queue,
            'status' => $this->status,
            'progress_percentage' => $this->progress_percentage,
            'progress_message' => $this->progress_message,
            'total_items' => $this->total_items,
            'processed_items' => $this->processed_items,
            'failed_items' => $this->failed_items,
            'started_at' => $this->started_at?->toISOString(),
            'estimated_completion_at' => $this->estimated_completion_at?->toISOString(),
            'elapsed_time_seconds' => $this->getElapsedTime(),
            'remaining_time_seconds' => $this->getEstimatedRemainingTime(),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * @param  Builder<JobProgress>  $query
     * @return Builder<JobProgress>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param  Builder<JobProgress>  $query
     * @return Builder<JobProgress>
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * @param  Builder<JobProgress>  $query
     * @return Builder<JobProgress>
     */
    public function scopeByJobType(Builder $query, string $jobType): Builder
    {
        return $query->where('job_type', $jobType);
    }

    /**
     * @param  Builder<JobProgress>  $query
     * @return Builder<JobProgress>
     */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * @param  Builder<JobProgress>  $query
     * @return Builder<JobProgress>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * @param  Builder<JobProgress>  $query
     * @return Builder<JobProgress>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * @param  Builder<JobProgress>  $query
     * @return Builder<JobProgress>
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Clean up old completed job progress records.
     */
    public static function cleanup(int $daysToKeep = 30): int
    {
        return self::where('status', self::STATUS_COMPLETED)
            ->where('completed_at', '<', now()->subDays($daysToKeep))
            ->delete();
    }

    /**
     * @return array<string, string> */
    protected function casts(): array
    {
        return [
            'progress_percentage' => 'integer',
            'total_items' => 'integer',
            'processed_items' => 'integer',
            'failed_items' => 'integer',
            'metadata' => 'json',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'estimated_completion_at' => 'datetime',
        ];
    }
}
