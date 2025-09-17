<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Listeners;

use Illuminate\Support\Facades\Notification;
use Modules\Campaign\Domain\Event\CampaignApprovedEvent;
use Modules\Campaign\Domain\Event\CampaignRejectedEvent;
use Modules\Campaign\Domain\Event\CampaignStatusChangedEvent;
use Modules\Campaign\Infrastructure\Laravel\Notifications\CampaignApprovedNotification;
use Modules\Campaign\Infrastructure\Laravel\Notifications\CampaignRejectedNotification;
use Modules\Campaign\Infrastructure\Laravel\Notifications\CampaignStatusChangedNotification;
use Modules\User\Infrastructure\Laravel\Models\User;

class SendCampaignStatusChangeNotificationListener
{
    /**
     * Handle campaign status changed events.
     */
    public function handleStatusChanged(CampaignStatusChangedEvent $event): void
    {
        $campaign = $event->campaign;
        $campaignOwner = User::find($campaign->user_id);
        $changedByUser = User::find($event->changedByUserId);

        if (! $campaignOwner || ! $changedByUser) {
            return;
        }

        // Don't send notification if user changed their own campaign status
        // unless it's a specific business rule change
        if ($campaignOwner->id === $changedByUser->id && ! $this->shouldNotifyOnOwnChange($event)) {
            return;
        }

        Notification::send(
            $campaignOwner,
            new CampaignStatusChangedNotification(
                campaign: $campaign,
                previousStatus: $event->previousStatus,
                newStatus: $event->newStatus,
                changedByUser: $changedByUser,
                reason: $event->reason
            )
        );
    }

    /**
     * Handle campaign approved events.
     */
    public function handleApproved(CampaignApprovedEvent $event): void
    {
        $campaign = $event->campaign;
        $campaignOwner = User::find($campaign->user_id);
        $approver = User::find($event->approvedByUserId);

        if (! $campaignOwner || ! $approver) {
            return;
        }

        Notification::send(
            $campaignOwner,
            new CampaignApprovedNotification(
                campaign: $campaign,
                approver: $approver,
                notes: $event->notes
            )
        );
    }

    /**
     * Handle campaign rejected events.
     */
    public function handleRejected(CampaignRejectedEvent $event): void
    {
        $campaign = $event->campaign;
        $campaignOwner = User::find($campaign->user_id);
        $rejecter = User::find($event->rejectedByUserId);

        if (! $campaignOwner || ! $rejecter) {
            return;
        }

        Notification::send(
            $campaignOwner,
            new CampaignRejectedNotification(
                campaign: $campaign,
                rejecter: $rejecter,
                reason: $event->reason
            )
        );
    }

    /**
     * Determine if we should send notification when user changes their own campaign
     */
    private function shouldNotifyOnOwnChange(CampaignStatusChangedEvent $event): bool
    {
        // Send notifications for certain status changes even if self-initiated
        if ($event->isSubmissionForApproval()) {
            return true;
        }
        if ($event->isActivation()) {
            return true;
        }

        return $event->isCompletion();
    }
}
