<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Http\Response;
use Modules\Export\Infrastructure\ApiPlatform\Handler\Processor\CancelExportProcessor;
use Modules\Export\Infrastructure\ApiPlatform\Handler\Processor\CreateExportProcessor;
use Modules\Export\Infrastructure\ApiPlatform\Handler\Processor\DeleteExportProcessor;
use Modules\Export\Infrastructure\ApiPlatform\Handler\Processor\DownloadExportProcessor;
use Modules\Export\Infrastructure\ApiPlatform\Handler\Processor\RetryExportProcessor;
use Modules\Export\Infrastructure\ApiPlatform\Handler\Provider\ExportCollectionProvider;
use Modules\Export\Infrastructure\ApiPlatform\Handler\Provider\ExportItemProvider;

#[ApiResource(
    shortName: 'Export',
    formats: ['jsonld' => ['application/ld+json'], 'json' => ['application/json']],
    operations: [
        new Post(
            uriTemplate: '/exports',
            status: Response::HTTP_CREATED,
            processor: CreateExportProcessor::class,
        ),
        new GetCollection(
            uriTemplate: '/exports',
            paginationEnabled: true,
            paginationItemsPerPage: 15,
            paginationMaximumItemsPerPage: 100,
            paginationClientItemsPerPage: true,
            provider: ExportCollectionProvider::class,
            parameters: [
                'search' => new QueryParameter(key: 'search', filter: PartialSearchFilter::class),
                'status' => new QueryParameter(key: 'status', filter: EqualsFilter::class),
                'type' => new QueryParameter(key: 'resource_type', filter: EqualsFilter::class),
                'format' => new QueryParameter(key: 'format', filter: EqualsFilter::class),
                'sort[:property]' => new QueryParameter(key: 'sort[:property]', filter: OrderFilter::class),
            ],
        ),
        new Get(
            uriTemplate: '/exports/{id}',
            provider: ExportItemProvider::class,
        ),
        new Get(
            uriTemplate: '/exports/{id}/progress',
            provider: ExportItemProvider::class,
        ),
        new Post(
            uriTemplate: '/exports/{id}/cancel',
            status: Response::HTTP_OK,
            processor: CancelExportProcessor::class,
        ),
        new Post(
            uriTemplate: '/exports/{id}/retry',
            status: Response::HTTP_OK,
            processor: RetryExportProcessor::class,
        ),
        new Get(
            uriTemplate: '/exports/{id}/download',
            provider: DownloadExportProcessor::class,
        ),
        new Delete(
            uriTemplate: '/exports/{id}',
            status: Response::HTTP_NO_CONTENT,
            processor: DeleteExportProcessor::class,
        ),
    ],
    middleware: ['auth:sanctum', 'api.locale'],
)]
class ExportResource
{
    public function __construct(
        public ?int $id = null,
        public ?string $export_id = null,
        public ?string $type = null,
        public ?string $status = null,
        public ?string $format = null,
        public ?int $progress = null,
        public ?int $total_records = null,
        public ?int $processed_records = null,
        public ?string $filename = null,
        public ?int $file_size = null,
        public ?string $error_message = null,
        public ?string $created_at = null,
        public ?string $started_at = null,
        public ?string $completed_at = null,
        public ?string $expires_at = null,
    ) {}
}
