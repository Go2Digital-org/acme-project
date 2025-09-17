<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Export\Application\Query\GetExportDownloadUrlQuery;
use Modules\Export\Application\QueryHandler\GetExportDownloadUrlQueryHandler;
use Modules\Export\Domain\Exception\ExportException;
use Modules\Export\Domain\ValueObject\ExportId;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class DownloadExportController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private GetExportDownloadUrlQueryHandler $handler,
    ) {}

    /**
     * Download completed export file.
     */
    public function __invoke(Request $request, string $exportId): RedirectResponse|JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        try {
            $query = new GetExportDownloadUrlQuery(
                exportId: ExportId::fromString($exportId),
                userId: $user->id,
            );

            $downloadInfo = $this->handler->handle($query);

            // The handler already validates status and file existence
            // Handler provides a temporary URL for secure downloads
            return response()->redirectTo($downloadInfo['download_url']);

        } catch (ExportException $e) {
            if ($e->getCode() === 404) {
                return response()->json(
                    ApiResponse::notFound('Export not found or access denied.')->getData(),
                    404
                );
            }

            if ($e->getCode() === 403) {
                return response()->json(
                    ApiResponse::forbidden('Access denied to this export.')->getData(),
                    403
                );
            }

            if ($e->getCode() === 410) {
                return response()->json(
                    ApiResponse::error('Export has expired and is no longer available for download.', statusCode: 410)->getData(),
                    410
                );
            }

            return response()->json(
                ApiResponse::error($e->getMessage(), statusCode: $e->getCode() ?: 500)->getData(),
                $e->getCode() ?: 500
            );
        }
    }
}
