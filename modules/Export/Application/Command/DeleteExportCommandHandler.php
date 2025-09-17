<?php

declare(strict_types=1);

namespace Modules\Export\Application\Command;

use Exception;
use Illuminate\Support\Facades\Storage;
use Modules\Export\Domain\Exception\ExportException;
use Modules\Export\Domain\Model\ExportJob;
use Modules\Export\Domain\Repository\ExportJobRepositoryInterface;
use Modules\Export\Domain\ValueObject\ExportId;

/**
 * Delete Export Command Handler.
 *
 * Handles deletion of export jobs and their files through the domain layer.
 * Validates export ownership and status before deletion.
 */
final class DeleteExportCommandHandler
{
    public function __construct(
        private readonly ExportJobRepositoryInterface $exportRepository,
    ) {}

    /**
     * Handle the deletion of an export.
     *
     * @throws ExportException
     */
    public function handle(DeleteExportCommand $command): void
    {
        $exportId = ExportId::fromString($command->exportId);
        $export = $this->exportRepository->findById($exportId);

        if (! $export) {
            throw ExportException::exportNotFound($exportId);
        }

        // Verify ownership
        if ($export->getUserId() !== $command->userId) {
            throw ExportException::accessDenied();
        }

        // Only allow deletion of completed, failed, or cancelled exports
        $status = $export->getStatusValueObject();
        if (! in_array($status->value, ['completed', 'failed', 'cancelled'])) {
            throw ExportException::cannotDeleteProcessingExport();
        }

        // Delete the file from storage if it exists
        if ($export->file_path) {
            try {
                $disk = Storage::disk(config('export.storage.disk', 'local'));
                if ($disk->exists($export->file_path)) {
                    $disk->delete($export->file_path);
                }
            } catch (Exception $e) {
                // Log the error but continue with database deletion
                logger()->warning('Failed to delete export file', [
                    'export_id' => $command->exportId,
                    'file_path' => $export->file_path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Delete the export record from database
        $this->deleteExport($export);
    }

    private function deleteExport(ExportJob $export): void
    {
        // Use the Eloquent model's delete method since the repository
        // interface doesn't define a delete method
        $export->delete();
    }
}
