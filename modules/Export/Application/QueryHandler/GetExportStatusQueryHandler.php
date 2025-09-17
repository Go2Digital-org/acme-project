<?php

declare(strict_types=1);

namespace Modules\Export\Application\QueryHandler;

use Modules\Export\Application\DTO\ExportStatusDTO;
use Modules\Export\Application\Query\GetExportStatusQuery;
use Modules\Export\Domain\Exception\ExportException;
use Modules\Export\Domain\Model\ExportJob;
use Modules\Export\Domain\Repository\ExportJobRepositoryInterface;

class GetExportStatusQueryHandler
{
    public function __construct(
        private readonly ExportJobRepositoryInterface $repository
    ) {}

    public function handle(GetExportStatusQuery $query): ExportStatusDTO
    {
        $exportJob = $this->repository->findByExportId($query->exportId);

        if (! $exportJob instanceof ExportJob) {
            throw ExportException::exportNotFound($query->exportId);
        }

        // Verify user owns this export
        if ($exportJob->user_id !== $query->userId) {
            throw ExportException::unauthorizedAccess();
        }

        $status = $exportJob->getStatusValueObject();
        $progress = $exportJob->getCurrentProgressValueObject();

        $createdAt = $exportJob->created_at ?? now();

        return new ExportStatusDTO(
            exportId: $exportJob->export_id,
            status: $status,
            progress: $progress,
            format: $exportJob->getFormatValueObject(),
            createdAt: $createdAt,
            startedAt: $exportJob->started_at,
            completedAt: $exportJob->completed_at,
            expiresAt: $exportJob->expires_at,
            filePath: $exportJob->file_path,
            fileSize: $exportJob->file_size,
            fileSizeFormatted: $exportJob->getFileSizeFormatted(),
            errorMessage: $exportJob->error_message,
            estimatedTimeRemaining: $exportJob->getEstimatedTimeRemaining(),
            expiresInHours: $exportJob->getExpiresInHours(),
            canBeDownloaded: $exportJob->canBeDownloaded(),
            isExpired: $exportJob->isExpired()
        );
    }
}
