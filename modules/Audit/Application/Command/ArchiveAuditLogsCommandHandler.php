<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Command;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Audit\Domain\Repository\AuditRepositoryInterface;
use RuntimeException;

class ArchiveAuditLogsCommandHandler
{
    public function __construct(
        private readonly AuditRepositoryInterface $repository
    ) {}

    /** @return array<string, mixed> */
    public function handle(ArchiveAuditLogsCommand $command): array
    {
        $cutoffDate = Carbon::now()->subDays($command->daysOld);
        $archivedCount = 0;
        $archivedFiles = [];

        DB::transaction(function () use ($command, $cutoffDate, &$archivedCount, &$archivedFiles) {
            $query = $this->repository->newQuery()
                ->where('created_at', '<', $cutoffDate);

            if ($command->auditableType !== null) {
                $query->where('auditable_type', $command->auditableType);
            }

            // Process in batches
            $offset = 0;
            do {
                $audits = $query->offset($offset)
                    ->limit($command->batchSize ?? 500)
                    ->get();

                if ($audits->isNotEmpty()) {
                    // Create archive file name
                    $fileName = sprintf(
                        'audit_archive_%s_%s_%d.json',
                        $command->auditableType ? str_replace('\\', '_', $command->auditableType) : 'all',
                        $cutoffDate->format('Y_m_d'),
                        $offset
                    );

                    // Store audit data as JSON
                    $archiveData = [
                        'archived_at' => now()->toISOString(),
                        'cutoff_date' => $cutoffDate->toISOString(),
                        'auditable_type' => $command->auditableType,
                        'count' => $audits->count(),
                        'audits' => $audits->toArray(),
                    ];

                    $jsonContent = json_encode($archiveData, JSON_PRETTY_PRINT);
                    if ($jsonContent === false) {
                        throw new RuntimeException('Failed to encode archive data as JSON');
                    }

                    Storage::disk($command->archiveStorage)->put(
                        $fileName,
                        $jsonContent
                    );

                    // Delete archived records
                    $auditIds = $audits->pluck('id')->toArray();
                    $this->repository->newQuery()
                        ->whereIn('id', $auditIds)
                        ->delete();

                    $archivedCount += $audits->count();
                    $archivedFiles[] = $fileName;
                }

                $offset += $command->batchSize ?? 500;
            } while ($audits->isNotEmpty());
        });

        return [
            'archived_count' => $archivedCount,
            'archived_files' => $archivedFiles,
            'cutoff_date' => $cutoffDate->toISOString(),
        ];
    }
}
