<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class GenerateReportCommand implements CommandInterface
{
    public function __construct(
        public string $reportType,
        public string $reportName,
        /** @var array<string, mixed> */
        public array $parameters,
        public string $format = 'json',
        public ?int $userId = null,
        public ?int $organizationId = null,
        public ?string $timeRange = null,
        /** @var array<string, mixed>|null */
        public ?array $filters = [],
        public bool $includeComparisons = false,
        public bool $includeVisualizationData = true,
        public ?string $scheduledFor = null,
    ) {}
}
