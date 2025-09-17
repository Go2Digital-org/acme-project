<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\Laravel\EventListener;

use Modules\Organization\Domain\Event\OrganizationCreatedEvent;
use Modules\Organization\Domain\Event\OrganizationUpdatedEvent;
use Modules\Organization\Domain\Model\Organization;
use Modules\Search\Application\Command\IndexEntityCommand;
use Modules\Search\Application\Command\IndexEntityCommandHandler;

class IndexOrganizationListener
{
    public function __construct(
        private readonly IndexEntityCommandHandler $handler,
    ) {}

    /**
     * Handle organization created/updated events.
     */
    public function handle(OrganizationCreatedEvent|OrganizationUpdatedEvent $event): void
    {
        // Get the organization
        $organization = Organization::find($event->organizationId);

        if (! $organization) {
            return;
        }

        // Check if model has searchable trait

        // Create index command
        $command = new IndexEntityCommand(
            entityType: 'organization',
            entityId: (string) $organization->id,
            data: $organization->toSearchableArray(),
            shouldQueue: true,
        );

        // Handle the indexing
        $this->handler->handle($command);
    }
}
