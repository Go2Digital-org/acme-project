<?php

declare(strict_types=1);

namespace Modules\Export\Application\QueryHandler;

use Modules\Export\Application\DTO\ExportListDTO;
use Modules\Export\Application\Query\GetUserExportsQuery;
use Modules\Export\Domain\Repository\ExportJobRepositoryInterface;
use Modules\Export\Domain\ValueObject\ExportStatus;

class GetUserExportsQueryHandler
{
    public function __construct(
        private readonly ExportJobRepositoryInterface $repository
    ) {}

    public function handle(GetUserExportsQuery $query): ExportListDTO
    {
        $filters = [
            'user_id' => $query->userId,
        ];

        if ($query->status instanceof ExportStatus) {
            $filters['status'] = $query->status->value;
        }

        if ($query->resourceType) {
            $filters['resource_type'] = $query->resourceType;
        }

        $pagination = $this->repository->paginate(
            page: $query->page,
            perPage: $query->perPage,
            filters: $filters,
            sortBy: $query->sortBy,
            sortOrder: $query->sortOrder
        );

        /** @var array<int, array<string, mixed>> $exports */
        $exports = collect($pagination->items())->map(fn ($exportJob): array => [
            'export_id' => $exportJob->export_id,
            'resource_type' => $exportJob->resource_type,
            'format' => $exportJob->format,
            'status' => $exportJob->status,
            'status_label' => $exportJob->getStatusValueObject()->getLabel(),
            'status_color' => $exportJob->getStatusValueObject()->getColor(),
            'progress_percentage' => $exportJob->getAttribute('current_percentage'),
            'progress_message' => $exportJob->getAttribute('current_message'),
            'processed_records' => $exportJob->processed_records ?? 0,
            'total_records' => $exportJob->total_records ?? 0,
            'file_size' => $exportJob->file_size,
            'file_size_formatted' => $exportJob->getFileSizeFormatted(),
            'error_message' => $exportJob->error_message,
            'created_at' => $exportJob->created_at,
            'started_at' => $exportJob->started_at,
            'completed_at' => $exportJob->completed_at,
            'expires_at' => $exportJob->expires_at,
            'expires_in_hours' => $exportJob->getExpiresInHours(),
            'estimated_time_remaining' => $exportJob->getEstimatedTimeRemaining(),
            'can_be_downloaded' => $exportJob->canBeDownloaded(),
            'is_expired' => $exportJob->isExpired(),
            'can_be_cancelled' => ! $exportJob->getStatusValueObject()->isFinished(),
        ])->toArray();

        return new ExportListDTO(
            exports: $exports,
            totalCount: $pagination->total(),
            currentPage: $pagination->currentPage(),
            perPage: $pagination->perPage(),
            totalPages: $pagination->lastPage(),
            hasMorePages: $pagination->hasMorePages()
        );
    }
}
