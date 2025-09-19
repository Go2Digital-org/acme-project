<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\User\Infrastructure\Laravel\Models\User;

class CampaignRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Campaign $campaign,
        private readonly User $rejecter,
        private readonly ?string $reason = null,
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
        $message = (new MailMessage)
            ->subject('Campaign Rejected: ' . $this->campaign->getTitle())
            ->greeting('Hello,')
            ->line('Your campaign has been reviewed and requires revisions before approval.')
            ->line('**Campaign:** ' . $this->campaign->getTitle())
            ->line('**Reviewed by:** ' . $this->rejecter->name)
            ->line('**Status:** Rejected');

        if ($this->reason) {
            $message->line('**Reason for Rejection:**')
                ->line($this->reason);
        }

        return $message
            ->line('Please address the feedback above and resubmit your campaign for approval.')
            ->action(__('campaigns.edit_campaign_action'), route('campaigns.edit', $this->campaign))
            ->line('If you have questions about the rejection, please contact your administrator.')
            ->salutation('Best regards, ACME Corp CSR Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        $reasonText = $this->reason ? " Reason: {$this->reason}" : '';

        return [
            'type' => 'campaign_rejected',
            'campaign_id' => $this->campaign->id,
            'campaign_title' => $this->campaign->getTitle(),
            'rejecter_id' => $this->rejecter->id,
            'rejecter_name' => $this->rejecter->name,
            'rejection_reason' => $this->reason,
            'message' => "Your campaign '{$this->campaign->getTitle()}' has been rejected.{$reasonText}",
            'action_url' => route('campaigns.edit', $this->campaign),
            'action_text' => __('campaigns.edit_campaign_action'),
        ];
    }
}
