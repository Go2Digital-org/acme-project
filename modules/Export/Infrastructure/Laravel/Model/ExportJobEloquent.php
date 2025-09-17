<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Laravel\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent model for export jobs database operations.
 * This is the infrastructure layer model for persistence only.
 * The domain model handles business logic.
 *
 * @property int $id
 * @property string $export_id
 * @property int $user_id
 * @property int|null $organization_id
 * @property string $resource_type
 * @property array<string, mixed>|null $resource_filters
 * @property string $format
 * @property string $status
 * @property string|null $file_path
 * @property int|null $file_size
 * @property string|null $error_message
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $expires_at
 * @property int|null $total_records
 * @property int|null $processed_records
 * @property int|null $current_percentage
 * @property string|null $current_message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $formatted_file_size
 * @property-read bool $is_expired
 * @property-read bool $can_be_downloaded
 * @property-read int|null $expires_in_hours
 */
class ExportJobEloquent extends Model
{
    protected $table = 'export_jobs';

    protected $fillable = [
        'export_id',
        'user_id',
        'organization_id',
        'resource_type',
        'resource_filters',
        'format',
        'status',
        'file_path',
        'file_size',
        'error_message',
        'started_at',
        'completed_at',
        'expires_at',
        'total_records',
        'processed_records',
        'current_percentage',
        'current_message',
    ];

    protected $attributes = [
        'status' => 'pending',
        'processed_records' => 0,
        'current_percentage' => 0,
    ];

    /**
     * @return HasMany<ExportProgressEloquent, $this>
     */
    public function progress(): HasMany
    {
        return $this->hasMany(ExportProgressEloquent::class, 'export_id', 'export_id');
    }

    /**
     * Scope to filter by status
     */
    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function status(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by organization
     */
    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function forOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope to filter by user
     */
    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function forUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter expired jobs
     */
    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function expired(Builder $query, ?Carbon $before = null): Builder
    {
        $before ??= now();

        return $query->where('expires_at', '<', $before);
    }

    /**
     * Scope to filter active jobs (pending or processing)
     */
    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    /**
     * Scope to filter completed jobs
     */
    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function completed(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to filter failed jobs
     */
    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function failed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to filter jobs by date range
     */
    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function createdBetween(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Get formatted file size
     *
     * @return Attribute<string, never>
     */
    protected function formattedFileSize(): Attribute
    {
        return Attribute::make(get: function (): string {
            if ($this->file_size === null) {
                return 'N/A';
            }
            $units = ['B', 'KB', 'MB', 'GB'];
            $size = $this->file_size;
            $unitIndex = 0;
            while ($size >= 1024 && $unitIndex < count($units) - 1) {
                $size /= 1024;
                $unitIndex++;
            }

            return round($size, 2) . ' ' . $units[$unitIndex];
        });
    }

    /**
     * Check if export is expired
     *
     * @return Attribute<bool, never>
     */
    protected function isExpired(): Attribute
    {
        return Attribute::make(get: fn (): bool => $this->expires_at !== null && $this->expires_at->isPast());
    }

    /**
     * Check if export can be downloaded
     *
     * @return Attribute<bool, never>
     */
    protected function canBeDownloaded(): Attribute
    {
        return Attribute::make(get: fn (): bool => $this->status === 'completed'
            && $this->file_path !== null
            && ! $this->is_expired);
    }

    /**
     * Get hours until expiration
     *
     * @return Attribute<int|null, never>
     */
    protected function expiresInHours(): Attribute
    {
        return Attribute::make(get: function (): ?int {
            if ($this->expires_at === null) {
                return null;
            }
            $hoursRemaining = now()->diffInHours($this->expires_at, false);

            return $hoursRemaining > 0 ? (int) $hoursRemaining : 0;
        });
    }

    protected function casts(): array
    {
        return [
            'resource_filters' => 'json',
            'file_size' => 'integer',
            'total_records' => 'integer',
            'processed_records' => 'integer',
            'current_percentage' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
