<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Export\Domain\Model\ExportJob;
use Modules\Export\Domain\ValueObject\ExportStatus;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<BinaryFileResponse>
 */
class DownloadExportProcessor implements ProviderInterface
{
    public function __construct(
        private readonly Request $request
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): BinaryFileResponse
    {
        $user = $this->request->user();
        if ($user === null) {
            throw new BadRequestHttpException('User not authenticated');
        }

        $export = ExportJob::query()
            ->where('export_id', $uriVariables['id'])
            ->where('user_id', $user->id)
            ->first();

        if (! $export) {
            throw new NotFoundHttpException('Export not found');
        }

        if ($export->getStatusValueObject() !== ExportStatus::COMPLETED) {
            throw new BadRequestHttpException('Export is not ready for download');
        }

        if (! $export->file_path || ! Storage::exists($export->file_path)) {
            throw new NotFoundHttpException('Export file not found');
        }

        $path = Storage::path($export->file_path);
        $filename = basename((string) $export->file_path);

        return new BinaryFileResponse(
            $path,
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }
}
