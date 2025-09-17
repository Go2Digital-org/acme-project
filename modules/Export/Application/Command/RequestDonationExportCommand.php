<?php

declare(strict_types=1);

namespace Modules\Export\Application\Command;

use Modules\Export\Domain\ValueObject\ExportFormat;
use Modules\Shared\Application\Command\CommandInterface;

final readonly class RequestDonationExportCommand implements CommandInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int>|null  $campaignIds
     */
    public function __construct(
        public int $userId,
        public int $organizationId,
        public ExportFormat $format,
        public array $filters = [],
        public ?string $dateRangeFrom = null,
        public ?string $dateRangeTo = null,
        public ?array $campaignIds = null,
        public bool $includeAnonymous = true,
        public bool $includeRecurring = true
    ) {}
}
