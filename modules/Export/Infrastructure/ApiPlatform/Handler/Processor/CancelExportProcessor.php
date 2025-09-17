<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Http\Request;
use Modules\Export\Domain\Model\ExportJob;
use Modules\Export\Domain\ValueObject\ExportStatus;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<mixed, array<string, string>>
 */
class CancelExportProcessor implements ProcessorInterface
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
        if (! in_array($currentStatus, [ExportStatus::PENDING, ExportStatus::PROCESSING])) {
            throw new BadRequestHttpException('Export cannot be cancelled in current status');
        }

        $export->status = ExportStatus::CANCELLED;
        $export->completed_at = now();
        $export->save();

        return ['message' => 'Export cancelled successfully'];
    }
}
