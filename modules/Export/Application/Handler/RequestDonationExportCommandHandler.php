<?php

declare(strict_types=1);

namespace Modules\Export\Application\Handler;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Export\Application\Command\RequestDonationExportCommand;
use Modules\Export\Domain\Exception\ExportException;
use Modules\Export\Domain\Model\ExportJob;
use Modules\Export\Domain\Repository\ExportJobRepositoryInterface;
use Modules\Export\Domain\ValueObject\ExportId;
use Modules\Export\Infrastructure\Laravel\Jobs\ProcessDonationExportJob;

final readonly class RequestDonationExportCommandHandler
{
    public function __construct(
        private ExportJobRepositoryInterface $repository
    ) {}

    public function handle(RequestDonationExportCommand $command): ExportJob
    {
        // Check user's export limits
        $this->validateExportRequest($command);

        return DB::transaction(function () use ($command): ExportJob {
            $exportId = ExportId::generate();
            $expiresAt = now()->addHours(48); // 48 hour expiry

            $filters = $this->buildFilters($command);

            $exportJob = ExportJob::create(
                exportId: $exportId,
                userId: $command->userId,
                organizationId: $command->organizationId,
                resourceType: 'donations',
                resourceFilters: $filters,
                format: $command->format,
                expiresAt: $expiresAt
            );

            $this->repository->store($exportJob);

            // Dispatch job to queue
            ProcessDonationExportJob::dispatch(
                $exportId->toString(),
                $filters,
                $command->format->value,
                $command->userId,
                $command->organizationId
            )->onQueue(config('export.processing.queue', 'exports'));

            return $exportJob;
        });
    }

    private function validateExportRequest(RequestDonationExportCommand $command): void
    {
        // Check if user has too many pending exports
        $pendingExports = $this->repository->countPendingByUser($command->userId);
        if ($pendingExports >= 3) {
            throw ExportException::tooManyPendingExports($pendingExports);
        }

        // Check if user has reached daily limit
        $todayExports = $this->repository->countTodayByUser($command->userId);
        if ($todayExports >= 10) {
            throw ExportException::dailyLimitExceeded($todayExports);
        }

        // Validate date range if provided
        if ($command->dateRangeFrom && $command->dateRangeTo) {
            $from = Carbon::parse($command->dateRangeFrom);
            $to = Carbon::parse($command->dateRangeTo);

            if ($from->isAfter($to)) {
                throw ExportException::invalidDateRange($command->dateRangeFrom, $command->dateRangeTo);
            }

            // Maximum 2 years range
            if ($from->diffInMonths($to) > 24) {
                throw ExportException::dateRangeTooLarge($command->dateRangeFrom, $command->dateRangeTo);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFilters(RequestDonationExportCommand $command): array
    {
        $filters = $command->filters;

        if ($command->dateRangeFrom) {
            $filters['date_from'] = $command->dateRangeFrom;
        }

        if ($command->dateRangeTo) {
            $filters['date_to'] = $command->dateRangeTo;
        }

        if ($command->campaignIds) {
            $filters['campaign_ids'] = $command->campaignIds;
        }

        $filters['include_anonymous'] = $command->includeAnonymous;
        $filters['include_recurring'] = $command->includeRecurring;

        return $filters;
    }
}
