<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Http\Request;
use Modules\Export\Domain\Model\ExportJob;
use Modules\Export\Infrastructure\ApiPlatform\Resource\ExportResource;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<ExportResource>
 */
class ExportItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly Request $request
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExportResource
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

        return new ExportResource(
            id: $export->id,
            export_id: $export->export_id,
            type: $export->resource_type,
            status: $export->getStatusValueObject()->value,
            format: $export->getFormatValueObject()->value,
            progress: $export->current_percentage,
            total_records: $export->total_records,
            processed_records: $export->processed_records,
            filename: basename($export->file_path ?? ''),
            file_size: $export->file_size,
            error_message: $export->error_message,
            created_at: $export->created_at?->toIso8601String(),
            started_at: $export->started_at?->toIso8601String(),
            completed_at: $export->completed_at?->toIso8601String(),
            expires_at: $export->expires_at?->toIso8601String(),
        );
    }
}
