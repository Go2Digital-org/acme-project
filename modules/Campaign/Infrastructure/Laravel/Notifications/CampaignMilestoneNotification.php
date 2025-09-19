<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Notifications;

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
        return (new MailMessage)
            ->subject("Campaign Milestone Reached: {$this->milestone}")
            ->greeting('Campaign Milestone Achievement!')
            ->line("The campaign '{$this->campaign->title}' has reached a significant milestone: {$this->milestone}.")
            ->line("Organization: {$this->campaign->organization->getName()}")
            ->line('Current Amount: $' . number_format((float) $this->campaign->current_amount, 2))
            ->line('Goal Amount: $' . number_format((float) $this->campaign->goal_amount, 2))
            ->action('View Campaign', route('filament.admin.resources.campaigns.view', $this->campaign))
            ->salutation('Best regards, ACME Corp CSR Team');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(mixed $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Campaign Milestone Reached',
            'message' => "Campaign '{$this->campaign->title}' reached milestone: {$this->milestone}",
            'action_url' => route('filament.admin.resources.campaigns.view', $this->campaign),
            'action_label' => 'View Campaign',
            'type' => 'campaign_milestone',
            'campaign_id' => $this->campaign->id,
            'milestone' => $this->milestone,
            'current_amount' => $this->campaign->current_amount,
            'goal_amount' => $this->campaign->goal_amount,
        ]);
    }
}
