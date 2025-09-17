<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Listeners;

use Illuminate\Support\Facades\Notification;
use Modules\Campaign\Domain\Event\CampaignSubmittedForApprovalEvent;
use Modules\Campaign\Infrastructure\Laravel\Notifications\CampaignSubmittedForApprovalNotification;
use Modules\User\Infrastructure\Laravel\Models\User;

class SendCampaignApprovalNotificationListener
{
    /**
     * Handle the event.
     */
    public function handle(CampaignSubmittedForApprovalEvent $event): void
    {
        $campaign = $event->campaign;
        $submitter = User::find($event->submitterId);

        if (! $submitter) {
            return;
        }

        // Get all super admins
        $superAdmins = User::role('super_admin')->get();

        if ($superAdmins->isEmpty()) {
            return;
        }

        // Send notifications to all super admins
        foreach ($superAdmins as $superAdmin) {
            // Check if this super admin is the submitter
            $isSubmitterNotification = $superAdmin->id === $event->submitterId;

            Notification::send(
                $superAdmin,
                new CampaignSubmittedForApprovalNotification(
                    campaign: $campaign,
                    submitter: $submitter,
                    isSubmitterNotification: $isSubmitterNotification
                )
            );
        }
    }
}
