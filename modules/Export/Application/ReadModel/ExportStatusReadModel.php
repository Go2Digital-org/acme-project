<?php

declare(strict_types=1);

namespace Modules\Export\Application\ReadModel;

use Modules\Export\Application\DTO\ExportStatusDTO;
use Modules\Export\Domain\ValueObject\ExportStatus;
use Modules\Shared\Application\ReadModel\AbstractReadModel;

final class ExportStatusReadModel extends AbstractReadModel
{
    protected int $cacheTtl = 60; // 1 minute for export status (frequently changing)

    public function getDTO(): ExportStatusDTO
    {
        return $this->get('dto');
    }

    public function getExportId(): string
    {
        return $this->getDTO()->exportId;
    }

    public function getStatus(): ExportStatus
    {
        return $this->getDTO()->status;
    }

    public function getProgressPercentage(): int
    {
        return $this->getDTO()->progress->percentage;
    }

    public function isCompleted(): bool
    {
        return $this->getStatus() === ExportStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->getStatus() === ExportStatus::FAILED;
    }

    public function isProcessing(): bool
    {
        return $this->getStatus()->isProcessing();
    }

    public function canBeDownloaded(): bool
    {
        return $this->getDTO()->canBeDownloaded;
    }

    public function canBeCancelled(): bool
    {
        return ! $this->getStatus()->isFinished();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'export_id' => $this->getId(),
            'status' => $this->getStatus()->value,
            'progress_percentage' => $this->getProgressPercentage(),
            'is_completed' => $this->isCompleted(),
            'is_failed' => $this->isFailed(),
            'is_processing' => $this->isProcessing(),
            'can_be_downloaded' => $this->canBeDownloaded(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'dto' => $this->getDTO()->toArray(),
            'version' => $this->getVersion(),
        ];
    }
}
