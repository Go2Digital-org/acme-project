<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Export\Application\Query\GetExportStatusQuery;
use Modules\Export\Application\QueryHandler\GetExportStatusQueryHandler;
use Modules\Export\Domain\Exception\ExportException;
use Modules\Export\Domain\ValueObject\ExportId;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class GetExportStatusController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private GetExportStatusQueryHandler $handler,
    ) {}

    /**
     * Get export status and progress.
     */
    public function __invoke(Request $request, string $exportId): JsonResponse
    {
        $request->validate([
            'export_id' => ['required', 'string'],
        ]);

        $user = $this->getAuthenticatedUser($request);

        try {
            $query = new GetExportStatusQuery(
                exportId: ExportId::fromString($exportId),
                userId: $user->id,
            );

            $status = $this->handler->handle($query);

            return ApiResponse::success(
                data: [
                    'export_id' => $status->exportId,
                    'status' => $status->status,
                    'progress' => $status->progress,
                    'total_records' => $status->progress->totalRecords,
                    'processed_records' => $status->progress->processedRecords,
                    'format' => $status->format,
                    'file_size' => $status->fileSize,
                    'requested_at' => $status->requestedAt,
                    'started_at' => $status->startedAt,
                    'completed_at' => $status->completedAt,
                    'expires_at' => $status->expiresAt,
                    'download_url' => $status->downloadUrl,
                    'error_message' => $status->errorMessage,
                ],
                message: 'Export status retrieved successfully.',
            );
        } catch (ExportException $e) {
            if ($e->getCode() === 404) {
                return ApiResponse::notFound('Export not found or access denied.');
            }

            if ($e->getCode() === 403) {
                return ApiResponse::forbidden('Access denied to this export.');
            }

            return ApiResponse::error(
                message: $e->getMessage(),
                statusCode: $e->getCode() ?: 400,
            );
        }
    }
}
