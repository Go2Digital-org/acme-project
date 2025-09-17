<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Broadcasting\Listeners;

use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Notification\Infrastructure\Broadcasting\Service\NotificationBroadcaster;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener that broadcasts campaign-related notifications.
 *
 * This listener handles domain events related to campaigns and broadcasts
 * appropriate notifications to admin dashboards and relevant channels.
 */
class CampaignBroadcastListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationBroadcaster $broadcaster, // @phpstan-ignore-line
        private readonly CampaignRepositoryInterface $campaignRepository, // @phpstan-ignore-line
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * The queue to push the job onto.
     */
    public function viaQueue(): string
    {
        return 'broadcasts';
    }

    /**
     * Determine the number of times the job may be attempted.
     */
    public function retryUntil(): DateTime
    {
        return now()->addMinutes(5);
    }

    /**
     * Handle a job failure.
     */
    public function failed(object $event, Throwable $exception): void
    {
        $eventType = class_basename($event);

        $this->logger->error('Campaign broadcast permanently failed', [
            'event_type' => $eventType,
            'error' => $exception->getMessage(),
        ]);
    }
}
