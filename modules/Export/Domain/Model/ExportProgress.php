<?php

declare(strict_types=1);

namespace Modules\Export\Domain\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Export\Domain\ValueObject\ExportId;
use Modules\Export\Domain\ValueObject\ExportProgress as ExportProgressValueObject;

/**
 * @property int $id
 * @property string $export_id
 * @property int $percentage
 * @property string|null $message
 * @property int $processed_records
 * @property int $total_records
 * @property Carbon $created_at
 */
class ExportProgress extends Model
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

    public function getExportIdValueObject(): ExportId
    {
        return ExportId::fromString($this->export_id);
    }

    public function getProgressValueObject(): ExportProgressValueObject
    {
        return ExportProgressValueObject::create(
            percentage: $this->percentage,
            message: $this->message ?? 'Export in progress...',
            processedRecords: $this->processed_records,
            totalRecords: $this->total_records
        );
    }

    public static function fromValueObject(ExportId $exportId, ExportProgressValueObject $progress): self
    {
        $instance = new self;
        $instance->export_id = $exportId->toString();
        $instance->percentage = $progress->percentage;
        $instance->message = $progress->message;
        $instance->processed_records = $progress->processedRecords;
        $instance->total_records = $progress->totalRecords;
        $instance->created_at = now();

        return $instance;
    }

    public function isCompleted(): bool
    {
        return $this->percentage === 100;
    }

    public function getRemainingRecords(): int
    {
        return max(0, $this->total_records - $this->processed_records);
    }

    /**
     * @return array<string, string>
     */
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
