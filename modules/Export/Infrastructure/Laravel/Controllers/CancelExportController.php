<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Export\Application\Command\CancelExportCommand;
use Modules\Export\Application\Handler\CancelExportCommandHandler;
use Modules\Export\Domain\Exception\ExportException;
use Modules\Export\Domain\ValueObject\ExportId;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class CancelExportController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private CancelExportCommandHandler $handler,
    ) {}

    /**
     * Cancel an in-progress export.
     */
    public function __invoke(Request $request, string $exportId): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        try {
            $command = new CancelExportCommand(
                exportId: ExportId::fromString($exportId),
                userId: $user->id,
            );

            $result = $this->handler->handle($command);

            if ($result) {
                return ApiResponse::success(
                    data: [
                        'export_id' => $exportId,
                        'status' => 'cancelled',
                        'cancelled_at' => now()->toISOString(),
                    ],
                    message: 'Export cancelled successfully.',
                );
            }

            return ApiResponse::error(
                message: 'Export could not be cancelled. It may already be completed or in a non-cancellable state.',
                statusCode: 422,
            );

        } catch (ExportException $e) {
            if ($e->getCode() === 404) {
                return ApiResponse::notFound('Export not found or access denied.');
            }

            if ($e->getCode() === 403) {
                return ApiResponse::forbidden('Access denied to cancel this export.');
            }

            if ($e->getCode() === 422) {
                return ApiResponse::error(
                    message: 'Export cannot be cancelled at this stage.',
                    statusCode: 422,
                );
            }

            return ApiResponse::error(
                message: $e->getMessage(),
                statusCode: $e->getCode() ?: 500,
            );
        }
    }
}
