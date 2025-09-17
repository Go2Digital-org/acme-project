<?php

declare(strict_types=1);

namespace Modules\Search\Application\Command;

use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Search\Application\Event\EntityIndexedEvent;
use Modules\Search\Domain\Service\SearchEngineInterface;
use Modules\Search\Infrastructure\Laravel\Jobs\IndexEntityJob;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

final readonly class IndexEntityCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private SearchEngineInterface $searchEngine,
    ) {}

    public function handle(CommandInterface $command): bool
    {
        if (! $command instanceof IndexEntityCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        // Queue the indexing if needed
        if ($command->shouldQueue) {
            IndexEntityJob::dispatch(
                $command->entityType,
                $command->entityId,
                $command->data,
            );

            return true;
        }

        // Index immediately
        $indexName = $this->getIndexName($command->entityType);

        // Prepare document for indexing
        $document = array_merge($command->data, [
            'id' => $command->entityId,
            'entity_type' => $command->entityType,
            'indexed_at' => now()->toIso8601String(),
        ]);

        // Index the document
        $this->searchEngine->index($indexName, $document);

        // Dispatch event
        Event::dispatch(new EntityIndexedEvent(
            entityType: $command->entityType,
            entityId: $command->entityId,
        ));

        return true;
    }

    /**
     * Get index name for entity type.
     */
    private function getIndexName(string $entityType): string
    {
        $mapping = [
            'campaign' => 'acme_campaigns',
            'donation' => 'acme_donations',
            'user' => 'acme_users',
            'organization' => 'acme_organizations',
        ];

        return $mapping[$entityType] ?? 'acme_' . $entityType;
    }
}
