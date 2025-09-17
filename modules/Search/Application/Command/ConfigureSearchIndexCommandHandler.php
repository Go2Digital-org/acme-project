<?php

declare(strict_types=1);

namespace Modules\Search\Application\Command;

use InvalidArgumentException;
use Modules\Search\Domain\Service\SearchIndexServiceInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

/**
 * Handles configuration of search indexes.
 */
final readonly class ConfigureSearchIndexCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private SearchIndexServiceInterface $searchIndexService,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        if (! $command instanceof ConfigureSearchIndexCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        $this->searchIndexService->configureIndexes(
            $command->modelClass,
            $command->settings
        );

        return null;
    }
}
