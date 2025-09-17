<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\Laravel\EventListener;

use Modules\Donation\Application\Event\DonationCompletedEvent;
use Modules\Donation\Domain\Model\Donation;
use Modules\Search\Application\Command\IndexEntityCommand;
use Modules\Search\Application\Command\IndexEntityCommandHandler;

class IndexDonationListener
{
    public function __construct(
        private readonly IndexEntityCommandHandler $handler,
    ) {}

    /**
     * Handle donation completed event.
     */
    public function handle(DonationCompletedEvent $event): void
    {
        // Get the donation
        $donationClass = Donation::class;

        if (! class_exists($donationClass)) {
            return;
        }

        $donation = $donationClass::find($event->donationId);

        if (! $donation instanceof Donation) {
            return;
        }

        // Check if model has searchable trait

        // Create index command
        $command = new IndexEntityCommand(
            entityType: 'donation',
            entityId: (string) $donation->id,
            data: $donation->toSearchableArray(),
            shouldQueue: true,
        );

        // Handle the indexing
        $this->handler->handle($command);
    }
}
