<?php

declare(strict_types=1);

namespace Modules\Export\Application\Handler;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Modules\Export\Application\Command\CancelExportCommand;
use Modules\Export\Domain\Exception\ExportException;
use Modules\Export\Domain\Model\ExportJob;
use Modules\Export\Domain\Repository\ExportJobRepositoryInterface;

final readonly class CancelExportCommandHandler
{
    public function __construct(
        private ExportJobRepositoryInterface $repository
    ) {}

    public function handle(CancelExportCommand $command): bool
    {
        return DB::transaction(function () use ($command): true {
            $exportJob = $this->repository->findByExportId($command->exportId);

            if (! $exportJob instanceof ExportJob) {
                throw ExportException::exportNotFound($command->exportId);
            }

            // Verify user owns this export
            if ($exportJob->user_id !== $command->userId) {
                throw ExportException::unauthorizedAccess();
            }

            $currentStatus = $exportJob->getStatusValueObject();

            // Can only cancel pending or processing exports
            if ($currentStatus->isFinished()) {
                throw ExportException::cannotCancelFinishedExport($command->exportId);
            }

            try {
                // Try to cancel queued job if still pending (assuming Queue facade exists)
                if ($currentStatus->isPending() && method_exists(Queue::class, 'forget')) {
                    // Queue::forget('export.donations.'.$command->exportId->toString());
                }

                // Use the new cancel method instead of fail
                $exportJob->cancel('Cancelled: ' . $command->reason);
                $this->repository->store($exportJob);

                return true;
            } catch (Exception $e) {
                throw ExportException::cancellationFailed($e->getMessage());
            }
        });
    }
}
