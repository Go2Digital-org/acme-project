<?php

declare(strict_types=1);

namespace Modules\Export\Domain\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Export\Domain\Event\ExportCompleted;
use Modules\Export\Domain\Event\ExportFailed;
use Modules\Export\Domain\Event\ExportProgressUpdated;
use Modules\Export\Domain\Event\ExportRequested;
use Modules\Export\Domain\Event\ExportStarted;
use Modules\Export\Domain\Exception\ExportException;
use Modules\Export\Domain\ValueObject\ExportFormat;
use Modules\Export\Domain\ValueObject\ExportId;
use Modules\Export\Domain\ValueObject\ExportProgress as ExportProgressValueObject;
use Modules\Export\Domain\ValueObject\ExportStatus;

/**
 * @property int $id
 * @property string $export_id
 * @property int $user_id
 * @property int|null $organization_id
 * @property string $resource_type
 * @property array<string, mixed>|null $resource_filters
 * @property ExportFormat $format
 * @property ExportStatus $status
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
 */
class ExportJob extends Model
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
        'status' => 'pending', // String value for database storage
        'processed_records' => 0,
        'current_percentage' => 0,
    ];

    // Aggregate root - collects domain events
    /** @var array<int, ExportRequested|ExportStarted|ExportProgressUpdated|ExportCompleted|ExportFailed> */
    private array $domainEvents = [];

    /**
     * @return HasMany<ExportProgress, $this>
     */
    public function progress(): HasMany
    {
        return $this->hasMany(ExportProgress::class, 'export_id', 'export_id');
    }

    public function getExportIdValueObject(): ExportId
    {
        return ExportId::fromString($this->export_id);
    }

    public function getStatusValueObject(): ExportStatus
    {
        if ($this->status instanceof ExportStatus) {
            return $this->status;
        }

        return ExportStatus::from($this->status);
    }

    public function getFormatValueObject(): ExportFormat
    {
        if ($this->format instanceof ExportFormat) {
            return $this->format;
        }

        return ExportFormat::from($this->format);
    }

    public function getCurrentProgressValueObject(): ExportProgressValueObject
    {
        return ExportProgressValueObject::create(
            percentage: $this->current_percentage ?? 0,
            message: $this->current_message ?? 'Export in progress...',
            processedRecords: $this->processed_records ?? 0,
            totalRecords: $this->total_records ?? 0
        );
    }

    /**
     * @param  array<string, mixed>  $resourceFilters
     */
    public static function create(
        ExportId $exportId,
        int $userId,
        int $organizationId,
        string $resourceType,
        array $resourceFilters,
        ExportFormat $format,
        Carbon $expiresAt
    ): self {
        $job = new self;
        $job->export_id = $exportId->toString();
        $job->user_id = $userId;
        $job->organization_id = $organizationId;
        $job->resource_type = $resourceType;
        $job->resource_filters = $resourceFilters;
        $job->format = $format;
        $job->status = ExportStatus::PENDING;
        $job->expires_at = $expiresAt;
        $job->total_records = 0; // Initialize to 0, will be updated when processing starts

        $job->recordEvent(new ExportRequested(
            exportId: $exportId,
            userId: $userId,
            organizationId: $organizationId,
            resourceType: $resourceType,
            format: $format,
            filters: $resourceFilters
        ));

        return $job;
    }

    public function startProcessing(int $totalRecords = 0): void
    {
        $currentStatus = $this->getStatusValueObject();

        if (! $currentStatus->canTransitionTo(ExportStatus::PROCESSING)) {
            throw ExportException::invalidStatusTransition($currentStatus, ExportStatus::PROCESSING);
        }

        $this->status = ExportStatus::PROCESSING;
        $this->started_at = now();
        $this->total_records = $totalRecords;
        $this->current_message = 'Starting export processing...';

        $this->recordEvent(new ExportStarted(
            exportId: $this->getExportIdValueObject(),
            totalRecords: $totalRecords
        ));
    }

    public function updateProgress(ExportProgressValueObject $progress): void
    {
        if ($this->getStatusValueObject() !== ExportStatus::PROCESSING) {
            return; // Ignore progress updates if not processing
        }

        $this->current_percentage = $progress->percentage;
        $this->current_message = $progress->message;
        $this->processed_records = $progress->processedRecords;

        if ($progress->totalRecords > 0 && $this->total_records !== $progress->totalRecords) {
            $this->total_records = $progress->totalRecords;
        }

        $this->recordEvent(new ExportProgressUpdated(
            exportId: $this->getExportIdValueObject(),
            progress: $progress
        ));
    }

    public function complete(string $filePath, int $fileSize): void
    {
        $currentStatus = $this->getStatusValueObject();

        if (! $currentStatus->canTransitionTo(ExportStatus::COMPLETED)) {
            throw ExportException::invalidStatusTransition($currentStatus, ExportStatus::COMPLETED);
        }

        $format = $this->getFormatValueObject();
        $maxFileSizeMB = $format->getMaxFileSizeMB();
        $fileSizeMB = (int) round($fileSize / 1024 / 1024);

        if ($fileSizeMB > $maxFileSizeMB) {
            throw ExportException::exportFileTooLarge($fileSizeMB, $maxFileSizeMB);
        }

        $this->status = ExportStatus::COMPLETED;
        $this->completed_at = now();
        $this->file_path = $filePath;
        $this->file_size = $fileSize;
        $this->current_percentage = 100;
        $this->current_message = 'Export completed successfully';

        $this->recordEvent(new ExportCompleted(
            exportId: $this->getExportIdValueObject(),
            filePath: $filePath,
            fileSize: $fileSize,
            recordsExported: $this->processed_records ?? 0
        ));
    }

    public function fail(string $errorMessage): void
    {
        $currentStatus = $this->getStatusValueObject();

        if (! $currentStatus->canTransitionTo(ExportStatus::FAILED)) {
            throw ExportException::invalidStatusTransition($currentStatus, ExportStatus::FAILED);
        }

        $this->status = ExportStatus::FAILED;
        $this->completed_at = now();
        $this->error_message = $errorMessage;
        $this->current_message = 'Export failed: ' . $errorMessage;

        $this->recordEvent(new ExportFailed(
            exportId: $this->getExportIdValueObject(),
            errorMessage: $errorMessage,
            processedRecords: $this->processed_records ?? 0
        ));
    }

    public function cancel(string $reason = 'Cancelled by user'): void
    {
        $currentStatus = $this->getStatusValueObject();

        if (! $currentStatus->canTransitionTo(ExportStatus::CANCELLED)) {
            throw ExportException::invalidStatusTransition($currentStatus, ExportStatus::CANCELLED);
        }

        $this->status = ExportStatus::CANCELLED;
        $this->completed_at = now();
        $this->error_message = $reason;
        $this->current_message = 'Export cancelled: ' . $reason;
    }

    public function resetForRetry(): void
    {
        $currentStatus = $this->getStatusValueObject();

        if ($currentStatus !== ExportStatus::FAILED) {
            throw ExportException::cannotRetryNonFailedExport();
        }

        $this->status = ExportStatus::PENDING;
        $this->error_message = null;
        $this->current_percentage = 0;
        $this->processed_records = 0;
        $this->started_at = null;
        $this->completed_at = null;
        $this->file_path = null;
        $this->file_size = null;
        $this->current_message = 'Export queued for retry';
    }

    public function canBeDownloaded(): bool
    {
        return $this->getStatusValueObject()->isCompleted()
            && $this->file_path !== null
            && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function getExpiresInHours(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        $hoursRemaining = now()->diffInHours($this->expires_at, false);

        return $hoursRemaining > 0 ? (int) $hoursRemaining : 0;
    }

    public function getFileSizeFormatted(): string
    {
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
    }

    public function getEstimatedTimeRemaining(): ?string
    {
        if ($this->getStatusValueObject() !== ExportStatus::PROCESSING || $this->started_at === null) {
            return null;
        }

        if ($this->current_percentage === 0) {
            return null;
        }

        $elapsedMinutes = $this->started_at->diffInMinutes(now());
        $estimatedTotalMinutes = ($elapsedMinutes / $this->current_percentage) * 100;
        $remainingMinutes = $estimatedTotalMinutes - $elapsedMinutes;

        if ($remainingMinutes <= 0) {
            return 'Almost done';
        }

        if ($remainingMinutes < 60) {
            return round($remainingMinutes) . ' minutes';
        }

        return round($remainingMinutes / 60, 1) . ' hours';
    }

    // Domain Events Management
    private function recordEvent(ExportRequested|ExportStarted|ExportProgressUpdated|ExportCompleted|ExportFailed $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return array<int, ExportRequested|ExportStarted|ExportProgressUpdated|ExportCompleted|ExportFailed>
     */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    public function hasEvents(): bool
    {
        return $this->domainEvents !== [];
    }

    // Getter methods for domain logic
    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getStatus(): ExportStatus
    {
        return $this->getStatusValueObject();
    }

    public function getExportId(): ExportId
    {
        return $this->getExportIdValueObject();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getResourceFilters(): ?array
    {
        return $this->resource_filters;
    }

    public function getFormat(): ExportFormat
    {
        return $this->getFormatValueObject();
    }

    public function getOrganizationId(): ?int
    {
        return $this->organization_id;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resource_filters' => 'array',
            'file_size' => 'integer',
            'total_records' => 'integer',
            'processed_records' => 'integer',
            'current_percentage' => 'integer',
            'status' => ExportStatus::class,
            'format' => ExportFormat::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
