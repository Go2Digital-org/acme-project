<?php

declare(strict_types=1);

namespace Modules\Export\Application\Command;

/**
 * Delete Export Command.
 *
 * Command for deleting an export job and its associated file.
 * Follows CQRS pattern for export operations.
 */
final readonly class DeleteExportCommand
{
    public function __construct(
        public string $exportId,
        public int $userId,
    ) {}
}
