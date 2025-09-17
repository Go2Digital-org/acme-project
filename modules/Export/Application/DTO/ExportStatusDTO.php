<?php

declare(strict_types=1);

namespace Modules\Export\Application\DTO;

use Carbon\Carbon;
use Modules\Export\Domain\ValueObject\ExportFormat;
use Modules\Export\Domain\ValueObject\ExportProgress;
use Modules\Export\Domain\ValueObject\ExportStatus;

final readonly class ExportStatusDTO
{
    public function __construct(
        public string $exportId,
        public ExportStatus $status,
        public ExportProgress $progress,
        public ExportFormat $format,
        public Carbon $createdAt,
        public ?Carbon $startedAt = null,
        public ?Carbon $completedAt = null,
        public ?Carbon $expiresAt = null,
        public ?string $filePath = null,
        public ?int $fileSize = null,
        public ?string $fileSizeFormatted = null,
        public ?string $errorMessage = null,
        public ?string $estimatedTimeRemaining = null,
        public ?int $expiresInHours = null,
        public bool $canBeDownloaded = false,
        public bool $isExpired = false,
        public ?Carbon $requestedAt = null,
        public ?string $downloadUrl = null
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'export_id' => $this->exportId,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'status_color' => $this->status->getColor(),
            'progress' => [
                'percentage' => $this->progress->percentage,
                'message' => $this->progress->message,
                'processed_records' => $this->progress->processedRecords,
                'total_records' => $this->progress->totalRecords,
            ],
            'format' => [
                'value' => $this->format->value,
                'label' => $this->format->getLabel(),
                'mime_type' => $this->format->getMimeType(),
                'file_extension' => $this->format->getFileExtension(),
            ],
            'timestamps' => [
                'created_at' => $this->createdAt,
                'started_at' => $this->startedAt,
                'completed_at' => $this->completedAt,
                'expires_at' => $this->expiresAt,
            ],
            'file' => [
                'path' => $this->filePath,
                'size' => $this->fileSize,
                'size_formatted' => $this->fileSizeFormatted,
            ],
            'error_message' => $this->errorMessage,
            'estimated_time_remaining' => $this->estimatedTimeRemaining,
            'expires_in_hours' => $this->expiresInHours,
            'can_be_downloaded' => $this->canBeDownloaded,
            'is_expired' => $this->isExpired,
            'can_be_cancelled' => ! $this->status->isFinished(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getStatusBadge(): array
    {
        return [
            'text' => $this->status->getLabel(),
            'color' => $this->status->getColor(),
            'icon' => match ($this->status) {
                ExportStatus::PENDING => 'clock',
                ExportStatus::PROCESSING => 'download',
                ExportStatus::COMPLETED => 'check-circle',
                ExportStatus::FAILED => 'x-circle',
                ExportStatus::CANCELLED => 'x-circle',
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getProgressBar(): array
    {
        $color = match (true) {
            $this->progress->percentage < 25 => 'red',
            $this->progress->percentage < 50 => 'orange',
            $this->progress->percentage < 75 => 'yellow',
            $this->progress->percentage < 100 => 'blue',
            default => 'green',
        };

        return [
            'percentage' => $this->progress->percentage,
            'color' => $color,
            'animated' => $this->status->isProcessing(),
            'striped' => $this->status->isProcessing(),
        ];
    }
}
