<?php

declare(strict_types=1);

namespace Modules\Search\Application\Command;

use InvalidArgumentException;
use Modules\Search\Domain\Service\SearchIndexServiceInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

/**
 * Handles the RebuildSearchIndexCommand.
 *
 * This handler orchestrates the rebuilding of search indexes,
 * delegating to the domain service for the actual work.
 */
final readonly class RebuildSearchIndexCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private SearchIndexServiceInterface $searchIndexService,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        if (! $command instanceof RebuildSearchIndexCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        if ($command->clearFirst) {
            $this->searchIndexService->clearIndexes($command->modelClass);
        }

        $this->searchIndexService->rebuildIndexes($command->modelClass);

        return null;
    }
}
