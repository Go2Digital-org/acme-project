<?php

declare(strict_types=1);

namespace Modules\Search\Application\Command;

use InvalidArgumentException;
use Modules\Search\Domain\Service\IndexManagerInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

final readonly class ReindexAllCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private IndexManagerInterface $indexManager,
    ) {}

    public function handle(CommandInterface $command): bool
    {
        if (! $command instanceof ReindexAllCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        if ($command->entityType) {
            $this->indexManager->reindexEntity($command->entityType);

            return true;
        }

        $this->indexManager->reindexAll();

        return true;
    }
}
