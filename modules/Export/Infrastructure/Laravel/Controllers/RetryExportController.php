<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Export\Application\Command\RetryExportCommand;
use Modules\Export\Application\Command\RetryExportCommandHandler;
use Modules\Export\Domain\Exception\ExportException;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class RetryExportController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private readonly RetryExportCommandHandler $retryExportHandler,
    ) {}

    /**
     * Retry a failed export.
     */
    public function __invoke(Request $request, string $exportId): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        try {
            $command = new RetryExportCommand(
                exportId: $exportId,
                userId: $user->id,
            );

            $export = $this->retryExportHandler->handle($command);

            return ApiResponse::success(
                data: [
                    'export_id' => $exportId,
                    'status' => $export->status,
                    'retried_at' => now()->toISOString(),
                ],
                message: 'Export retry initiated successfully.'
            );
        } catch (ExportException $e) {
            return match ($e->getMessage()) {
                'Export with ID ' . $exportId . ' not found' => ApiResponse::notFound('Export not found or access denied.'),
                'Access denied to the requested export' => ApiResponse::notFound('Export not found or access denied.'),
                'Only failed exports can be retried' => ApiResponse::error('Only failed exports can be retried.', null, 422),
                default => ApiResponse::error($e->getMessage(), null, 500),
            };
        }
    }
}
