<?php

declare(strict_types=1);

namespace Modules\Export\Application\Command;

use Modules\Export\Domain\Exception\ExportException;
use Modules\Export\Domain\Model\ExportJob;
use Modules\Export\Domain\Repository\ExportJobRepositoryInterface;
use Modules\Export\Domain\ValueObject\ExportId;
use Modules\Export\Domain\ValueObject\ExportStatus;
use Modules\Export\Infrastructure\Laravel\Jobs\ProcessDonationExportJob;

/**
 * Retry Export Command Handler.
 *
 * Handles retrying failed export jobs through the domain layer.
 * Validates export ownership and status before retry.
 */
final class RetryExportCommandHandler
{
    public function __construct(
        private readonly ExportJobRepositoryInterface $exportRepository,
    ) {}

    /**
     * Handle the retry of a failed export.
     *
     * @throws ExportException
     */
    public function handle(RetryExportCommand $command): ExportJob
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

        // Only allow retry of failed exports
        if ($export->getStatus() !== ExportStatus::FAILED) {
            throw ExportException::cannotRetryNonFailedExport();
        }

        // Reset export for retry
        $export->resetForRetry();

        // Save the updated export
        $this->exportRepository->save($export);

        // Dispatch the export job to queue
        ProcessDonationExportJob::dispatch(
            $export->getExportId()->toString(),
            $export->getResourceFilters() ?? [],
            $export->getFormat()->value,
            $export->getUserId(),
            $export->getOrganizationId() ?? 0
        )->onQueue(config('export.processing.queue', 'exports'));

        return $export;
    }
}
