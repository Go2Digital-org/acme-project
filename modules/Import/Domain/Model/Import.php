<?php

declare(strict_types=1);

namespace Modules\Import\Domain\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Import\Domain\ValueObject\ImportStatus;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Import Model
 *
 * @property int $id
 * @property int $user_id
 * @property string $filename
 * @property string $original_filename
 * @property string $type
 * @property ImportStatus $status
 * @property int $total_records
 * @property int $processed_records
 * @property int $successful_records
 * @property int $failed_records
 * @property array<string, mixed>|null $metadata
 * @property array<int, string>|null $errors
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read Collection<int, ImportRecord> $records
 */
class Import extends Model
{
    protected $table = 'imports';

    protected $fillable = [
        'user_id',
        'filename',
        'original_filename',
        'type',
        'status',
        'total_records',
        'processed_records',
        'successful_records',
        'failed_records',
        'metadata',
        'errors',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => ImportStatus::class,
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'successful_records' => 'integer',
        'failed_records' => 'integer',
        'metadata' => 'array',
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ImportRecord, $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(ImportRecord::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === ImportStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === ImportStatus::FAILED;
    }

    public function isProcessing(): bool
    {
        return $this->status === ImportStatus::PROCESSING;
    }

    public function getProgressPercentage(): float
    {
        if ($this->total_records === 0) {
            return 0.0;
        }

        return round(($this->processed_records / $this->total_records) * 100, 2);
    }

    public function getSuccessRate(): float
    {
        if ($this->processed_records === 0) {
            return 0.0;
        }

        return round(($this->successful_records / $this->processed_records) * 100, 2);
    }
}
