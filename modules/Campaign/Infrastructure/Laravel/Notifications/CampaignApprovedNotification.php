<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\User\Infrastructure\Laravel\Models\User;

class CampaignApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Campaign $campaign,
        private readonly User $approver,
        private readonly ?string $notes = null,
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
            ->subject('Campaign Approved: ' . $this->campaign->getTitle())
            ->greeting('Congratulations!')
            ->line('Your campaign has been approved and is now active.')
            ->line('**Campaign:** ' . $this->campaign->getTitle())
            ->line('**Approved by:** ' . $this->approver->name)
            ->line('**Status:** Active')
            ->line('Your campaign is now visible to donors and ready to receive donations.')
            ->action('View Campaign', route('campaigns.show', $this->campaign))
            ->line('Thank you for contributing to our CSR initiatives!')
            ->salutation('Best regards, ACME Corp CSR Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'type' => 'campaign_approved',
            'campaign_id' => $this->campaign->id,
            'campaign_title' => $this->campaign->getTitle(),
            'approver_id' => $this->approver->id,
            'approver_name' => $this->approver->name,
            'notes' => $this->notes,
            'message' => "Your campaign '{$this->campaign->getTitle()}' has been approved and is now active",
            'action_url' => route('campaigns.show', $this->campaign),
            'action_text' => 'View Campaign',
        ];
    }
}
