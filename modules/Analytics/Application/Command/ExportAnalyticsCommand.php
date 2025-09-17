<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class ExportAnalyticsCommand implements CommandInterface
{
    public function __construct(
        public string $exportType,
        public string $format = 'csv',
        /** @var array<string> */
        public array $dataTypes = [],
        public ?string $timeRange = null,
        /** @var array<string, mixed> */
        public array $filters = [],
        public ?int $userId = null,
        public ?int $organizationId = null,
        public ?string $filename = null,
        public bool $includeHeaders = true,
        public bool $compressOutput = false,
        public ?string $deliveryMethod = 'download', // download, email, storage
        public ?string $notificationEmail = null,
    ) {}
}
