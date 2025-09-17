<?php

declare(strict_types=1);

namespace Modules\Export\Application\QueryHandler;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Modules\Export\Application\Query\GetExportDownloadUrlQuery;
use Modules\Export\Domain\Exception\ExportException;
use Modules\Export\Domain\Model\ExportJob;
use Modules\Export\Domain\Repository\ExportJobRepositoryInterface;

class GetExportDownloadUrlQueryHandler
{
    public function __construct(
        private readonly ExportJobRepositoryInterface $repository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(GetExportDownloadUrlQuery $query): array
    {
        $exportJob = $this->repository->findByExportId($query->exportId);

        if (! $exportJob instanceof ExportJob) {
            throw ExportException::exportNotFound($query->exportId);
        }

        // Verify user owns this export
        if ($exportJob->user_id !== $query->userId) {
            throw ExportException::unauthorizedAccess();
        }

        if (! $exportJob->canBeDownloaded()) {
            if ($exportJob->isExpired()) {
                throw ExportException::exportExpired($query->exportId);
            }

            if (! $exportJob->getStatusValueObject()->isCompleted()) {
                throw ExportException::exportNotReady($query->exportId, $exportJob->getStatusValueObject());
            }

            throw ExportException::exportFileNotAvailable($query->exportId);
        }

        $filePath = $exportJob->file_path;
        if ($filePath === null || ! Storage::exists($filePath)) {
            throw ExportException::exportFileNotFound($query->exportId, $filePath);
        }

        // Generate temporary signed URL
        $url = Storage::temporaryUrl(
            $filePath,
            now()->addMinutes($query->expiresInMinutes)
        );

        return [
            'download_url' => $url,
            'expires_at' => now()->addMinutes($query->expiresInMinutes),
            'file_name' => $this->generateFileName($exportJob),
            'file_size' => $exportJob->file_size,
            'file_size_formatted' => $exportJob->getFileSizeFormatted(),
            'mime_type' => $exportJob->getFormatValueObject()->getMimeType(),
        ];
    }

    private function generateFileName(ExportJob $exportJob): string
    {
        $format = $exportJob->getFormatValueObject();
        $resourceType = ucfirst($exportJob->resource_type);
        $createdAt = $exportJob->created_at;
        $date = $createdAt ? $createdAt->format('Y-m-d') : now()->format('Y-m-d');

        return "{$resourceType}_Export_{$date}.{$format->getFileExtension()}";
    }
}
