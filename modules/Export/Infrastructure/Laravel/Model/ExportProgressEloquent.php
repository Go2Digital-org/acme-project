<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Laravel\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model for export progress database operations.
 * Used for tracking detailed progress history of export jobs.
 *
 * @property int $id
 * @property string $export_id
 * @property int $percentage
 * @property string $message
 * @property int $processed_records
 * @property int $total_records
 * @property Carbon $created_at
 */
class ExportProgressEloquent extends Model
{
    protected $table = 'export_progress';

    protected $fillable = [
        'export_id',
        'percentage',
        'message',
        'processed_records',
        'total_records',
        'created_at',
    ];

    public $timestamps = false;

    /**
     * Relationship to export job
     *
     * @return BelongsTo<ExportJobEloquent, $this>
     */
    public function exportJob(): BelongsTo
    {
        return $this->belongsTo(ExportJobEloquent::class, 'export_id', 'export_id');
    }

    /**
     * Scope to get latest progress
     */
    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function latest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to filter by export ID
     */
    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function forExport(Builder $query, string $exportId): Builder
    {
        return $query->where('export_id', $exportId);
    }

    protected function casts(): array
    {
        return [
            'percentage' => 'integer',
            'processed_records' => 'integer',
            'total_records' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
