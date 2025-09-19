<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Campaign\Domain\Model\Campaign;

final class CampaignMilestoneNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Campaign $campaign,
        private readonly string $milestone,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $currentAmount = '$' . number_format((float) $this->campaign->current_amount, 2);
        $goalAmount = '$' . number_format((float) $this->campaign->goal_amount, 2);
        $progressPercentage = $this->campaign->getProgressPercentage();
        $organizationName = $this->campaign->organization ? $this->campaign->organization->getName() : 'Unknown Organization';

        return (new MailMessage)
            ->subject("Campaign Milestone Reached: {$this->campaign->title}")
            ->greeting('Milestone Achievement!')
            ->line("Great news! The campaign '{$this->campaign->title}' has reached a significant milestone.")
            ->line("Milestone: {$this->milestone}")
            ->line("Organization: {$organizationName}")
            ->line("Current Amount: {$currentAmount}")
            ->line("Goal Amount: {$goalAmount}")
            ->line("Progress: {$progressPercentage}%")
            ->line("Total Donations: {$this->campaign->donations_count}")
            ->line('This achievement demonstrates the strong support from our community.')
            ->action('View Campaign', url("/admin/campaigns/{$this->campaign->id}"))
            ->salutation('Best regards, ACME Corp CSR Team');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(mixed $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Campaign Milestone Reached',
            'message' => "Campaign '{$this->campaign->title}' has reached milestone: {$this->milestone}",
            'action_url' => url("/admin/campaigns/{$this->campaign->id}"),
            'action_label' => 'View Campaign',
            'type' => 'campaign_milestone',
            'campaign_id' => $this->campaign->id,
            'campaign_title' => $this->campaign->title,
            'milestone' => $this->milestone,
            'current_amount' => $this->campaign->current_amount,
            'goal_amount' => $this->campaign->goal_amount,
            'progress_percentage' => $this->campaign->getProgressPercentage(),
            'organization_id' => $this->campaign->organization->id,
            'organization_name' => $this->campaign->organization->getName(),
            'donations_count' => $this->campaign->donations_count,
        ]);
    }
}
