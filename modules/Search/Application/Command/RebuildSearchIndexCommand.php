<?php

declare(strict_types=1);

namespace Modules\Search\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

/**
 * Command to rebuild search indexes.
 *
 * This command follows the hexagonal architecture pattern,
 * encapsulating the business logic for rebuilding search indexes.
 */
final readonly class RebuildSearchIndexCommand implements CommandInterface
{
    /**
     * @param  class-string|null  $modelClass  Specific model to rebuild, or null for all
     * @param  bool  $clearFirst  Whether to clear the index before rebuilding
     */
    public function __construct(
        public ?string $modelClass = null,
        public bool $clearFirst = false,
    ) {}
}
