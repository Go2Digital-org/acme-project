<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Export\Application\Command\DeleteExportCommand;
use Modules\Export\Application\Command\DeleteExportCommandHandler;
use Modules\Export\Domain\Exception\ExportException;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class DeleteExportController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private DeleteExportCommandHandler $deleteExportHandler,
    ) {}

    /**
     * Delete an export and its associated file.
     */
    public function __invoke(Request $request, string $exportId): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        try {
            $command = new DeleteExportCommand(
                exportId: $exportId,
                userId: $user->id,
            );

            $this->deleteExportHandler->handle($command);

            return ApiResponse::success(
                data: [
                    'export_id' => $exportId,
                    'deleted_at' => now()->toISOString(),
                ],
                message: 'Export deleted successfully.'
            );
        } catch (ExportException $e) {
            return match ($e->getMessage()) {
                'Export with ID ' . $exportId . ' not found' => ApiResponse::notFound('Export not found or access denied.'),
                'Access denied to the requested export' => ApiResponse::notFound('Export not found or access denied.'),
                'Cannot delete an export that is still processing' => ApiResponse::error('Cannot delete an export that is still processing.', null, 422),
                default => ApiResponse::error($e->getMessage(), null, 500),
            };
        }
    }
}
