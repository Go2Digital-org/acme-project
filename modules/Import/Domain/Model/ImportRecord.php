<?php

declare(strict_types=1);

namespace Modules\Import\Domain\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Import\Domain\ValueObject\ImportRecordStatus;

/**
 * ImportRecord Model
 *
 * @property int $id
 * @property int $import_id
 * @property int $row_number
 * @property array<string, mixed> $data
 * @property ImportRecordStatus $status
 * @property string|null $error_message
 * @property array<string, mixed>|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Import $import
 */
class ImportRecord extends Model
{
    protected $table = 'import_records';

    protected $fillable = [
        'import_id',
        'row_number',
        'data',
        'status',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'import_id' => 'integer',
        'row_number' => 'integer',
        'data' => 'array',
        'status' => ImportRecordStatus::class,
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Import, $this>
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === ImportRecordStatus::SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === ImportRecordStatus::FAILED;
    }
}
