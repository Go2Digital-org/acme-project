<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\Laravel\EventListener;

use Modules\Campaign\Application\Event\CampaignCreatedEvent;
use Modules\Campaign\Application\Event\CampaignUpdatedEvent;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Search\Application\Command\IndexEntityCommand;
use Modules\Search\Application\Command\IndexEntityCommandHandler;

class IndexCampaignListener
{
    public function __construct(
        private readonly IndexEntityCommandHandler $handler,
    ) {}

    /**
     * Handle campaign created/updated events.
     */
    public function handle(CampaignCreatedEvent|CampaignUpdatedEvent $event): void
    {
        // Get the campaign
        $campaign = Campaign::find($event->campaignId);

        if (! $campaign) {
            return;
        }

        // Create index command
        $command = new IndexEntityCommand(
            entityType: 'campaign',
            entityId: (string) $campaign->id,
            data: $campaign->toSearchableArray(),
            shouldQueue: true,
        );

        // Handle the indexing
        $this->handler->handle($command);
    }
}
