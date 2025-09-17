<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Http\Request;
use Modules\Export\Domain\Model\ExportJob;
use Modules\Export\Domain\ValueObject\ExportFormat;
use Modules\Export\Domain\ValueObject\ExportStatus;
use Modules\Export\Infrastructure\Laravel\Jobs\ProcessDonationExportJob;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<mixed, array<string, string>>
 */
class RetryExportProcessor implements ProcessorInterface
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

        if ($export->getStatusValueObject() !== ExportStatus::FAILED) {
            throw new BadRequestHttpException('Only failed exports can be retried');
        }

        // Reset export status for retry
        $export->status = ExportStatus::PENDING;
        $export->error_message = null;
        $export->current_percentage = 0;
        $export->processed_records = 0;
        $export->started_at = null;
        $export->completed_at = null;
        $export->save();

        // Dispatch export job to queue
        $format = $export->format instanceof ExportFormat
            ? $export->format->value
            : ($export->format ?? 'csv');

        ProcessDonationExportJob::dispatch(
            $export->export_id,
            $export->resource_filters ?? [],
            $format,
            $export->user_id,
            $export->organization_id ?? 1
        )->onQueue(config('export.processing.queue', 'exports'));

        return ['message' => 'Export retry initiated successfully'];
    }
}
