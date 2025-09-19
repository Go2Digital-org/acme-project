<?php

declare(strict_types=1);

namespace Modules\Export\Application\Handler;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Export\Application\Command\CleanupExpiredExportsCommand;
use Modules\Export\Domain\Repository\ExportJobRepositoryInterface;

final readonly class CleanupExpiredExportsCommandHandler
{
    public function __construct(
        private ExportJobRepositoryInterface $repository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(CleanupExpiredExportsCommand $command): array
    {
        $results = [
            'processed' => 0,
            'deleted' => 0,
            'errors' => [],
            'freed_space' => 0,
        ];

        return DB::transaction(function () use ($command, $results): array {
            $cutoffDate = $command->daysOld
                ? now()->subDays($command->daysOld)
                : now();

            $expiredExports = $this->repository->findExpired(
                cutoffDate: $cutoffDate,
                limit: $command->batchSize
            );

            foreach ($expiredExports as $exportJob) {
                $results['processed']++;

                try {
                    $fileSize = 0;

                    // Delete file if exists
                    if ($exportJob->file_path && Storage::exists($exportJob->file_path)) {
                        $fileSize = Storage::size($exportJob->file_path);

                        if (! $command->dryRun) {
                            Storage::delete($exportJob->file_path);
                        }

                        $results['freed_space'] += $fileSize;
                    }

                    // Delete export record
                    if (! $command->dryRun) {
                        $this->repository->delete($exportJob);
                    }

                    $results['deleted']++;

                } catch (Exception $e) {
                    $results['errors'][] = [
                        'export_id' => $exportJob->export_id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return $results;
        });
    }
}
