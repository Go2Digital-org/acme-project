<?php

declare(strict_types=1);

namespace Modules\Export\Application\Command;

/**
 * Retry Export Command.
 *
 * Command for retrying a failed export job.
 * Follows CQRS pattern for export operations.
 */
final readonly class RetryExportCommand
{
    public function __construct(
        public string $exportId,
        public int $userId,
    ) {}
}
