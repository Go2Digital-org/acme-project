<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Export\Domain\Model\ExportJob;
use Modules\Export\Domain\ValueObject\ExportStatus;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<mixed, null>
 */
class DeleteExportProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Request $request
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
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

        $currentStatus = $export->getStatusValueObject();
        if (! in_array($currentStatus, [ExportStatus::COMPLETED, ExportStatus::FAILED, ExportStatus::CANCELLED])) {
            throw new BadRequestHttpException('Cannot delete export in current status');
        }

        // Delete file if exists
        if ($export->file_path && Storage::exists($export->file_path)) {
            Storage::delete($export->file_path);
        }

        $export->delete();

        return null;
    }
}
