<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Http\Request;
use Modules\Export\Domain\Model\ExportJob;
use Modules\Export\Infrastructure\ApiPlatform\Resource\ExportCollection;
use Modules\Export\Infrastructure\ApiPlatform\Resource\ExportResource;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<ExportCollection>
 */
class ExportCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly Request $request
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExportCollection
    {
        $user = $this->request->user();
        if ($user === null) {
            throw new BadRequestHttpException('User not authenticated');
        }

        $query = ExportJob::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($search = $this->request->query('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('resource_type', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('export_id', 'like', "%{$search}%");
            });
        }

        if ($status = $this->request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $this->request->query('type')) {
            $query->where('resource_type', $type);
        }

        if ($dateRange = $this->request->query('dateRange')) {
            switch ($dateRange) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year);
                    break;
                case 'quarter':
                    $query->whereQuarter('created_at', now()->quarter)
                        ->whereYear('created_at', now()->year);
                    break;
            }
        }

        $perPage = $this->request->query('per_page', 15);
        $exports = $query->paginate($perPage);

        // Transform to ExportResource
        $data = collect($exports->items())->map(fn (ExportJob $export): ExportResource => new ExportResource(
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
        ))->toArray();

        return new ExportCollection(
            data: $data,
            current_page: $exports->currentPage(),
            last_page: $exports->lastPage(),
            per_page: $exports->perPage(),
            total: $exports->total(),
        );
    }
}
