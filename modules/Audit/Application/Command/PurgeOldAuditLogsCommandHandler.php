<?php

declare(strict_types=1);

namespace Modules\Audit\Application\Command;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Audit\Domain\Repository\AuditRepositoryInterface;

class PurgeOldAuditLogsCommandHandler
{
    public function __construct(
        private readonly AuditRepositoryInterface $repository
    ) {}

    public function handle(PurgeOldAuditLogsCommand $command): int
    {
        $cutoffDate = Carbon::now()->subDays($command->daysToKeep);
        $deletedCount = 0;

        DB::transaction(function () use ($command, $cutoffDate, &$deletedCount) {
            $query = $this->repository->newQuery()
                ->where('created_at', '<', $cutoffDate);

            if ($command->auditableType !== null) {
                $query->where('auditable_type', $command->auditableType);
            }

            // Process in batches to avoid memory issues
            do {
                $batchCount = $query->limit($command->batchSize ?? 1000)->delete();
                $deletedCount += (int) $batchCount;
            } while ($batchCount > 0);
        });

        return $deletedCount;
    }
}
