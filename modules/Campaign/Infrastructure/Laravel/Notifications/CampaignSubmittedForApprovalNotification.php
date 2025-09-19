<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\User\Infrastructure\Laravel\Models\User;

class CampaignSubmittedForApprovalNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Campaign $campaign,
        private readonly User $submitter,
        private readonly bool $isSubmitterNotification = false,
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
        if ($this->isSubmitterNotification) {
            return (new MailMessage)
                ->subject('Your Campaign is Pending Approval: ' . $this->campaign->getTitle())
                ->greeting('Hello!')
                ->line('Your campaign has been submitted for approval and is now pending review.')
                ->line('**Campaign:** ' . $this->campaign->getTitle())
                ->line('**Organization:** ' . $this->campaign->organization->getName())
                ->line('**Goal Amount:** €' . number_format((float) $this->campaign->goal_amount, 2))
                ->action(__('campaigns.approve_now'), route('filament.admin.resources.campaigns.view', $this->campaign))
                ->line('As a super admin, you can approve this campaign immediately.')
                ->salutation('Best regards, ACME Corp CSR Team');
        }

        return (new MailMessage)
            ->subject('New Campaign Pending Approval: ' . $this->campaign->getTitle())
            ->greeting('Hello!')
            ->line('A new campaign has been submitted for your approval.')
            ->line('**Campaign:** ' . $this->campaign->getTitle())
            ->line('**Submitted by:** ' . $this->submitter->name)
            ->line('**Organization:** ' . $this->campaign->organization->getName())
            ->line('**Goal Amount:** €' . number_format((float) $this->campaign->goal_amount, 2))
            ->action(__('campaigns.review_campaign'), route('filament.admin.resources.campaigns.view', $this->campaign))
            ->line('Please review and approve or reject this campaign.')
            ->salutation('Best regards, ACME Corp CSR Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        if ($this->isSubmitterNotification) {
            return [
                'type' => 'campaign_submitted_for_approval',
                'campaign_id' => $this->campaign->id,
                'campaign_title' => $this->campaign->getTitle(),
                'submitter_id' => $this->submitter->id,
                'submitter_name' => $this->submitter->name,
                'is_own_campaign' => true,
                'message' => "Your campaign '{$this->campaign->getTitle()}' is pending approval. Approve now?",
                'action_url' => route('filament.admin.resources.campaigns.view', $this->campaign),
                'action_text' => __('campaigns.approve_now'),
            ];
        }

        return [
            'type' => 'campaign_submitted_for_approval',
            'campaign_id' => $this->campaign->id,
            'campaign_title' => $this->campaign->getTitle(),
            'submitter_id' => $this->submitter->id,
            'submitter_name' => $this->submitter->name,
            'is_own_campaign' => false,
            'message' => "Campaign '{$this->campaign->getTitle()}' submitted for approval by {$this->submitter->name}",
            'action_url' => route('filament.admin.resources.campaigns.view', $this->campaign),
            'action_text' => __('campaigns.review_campaign'),
        ];
    }
}
