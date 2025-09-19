<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $campaignId,
        private readonly string $campaignTitle,
        private readonly string $campaignDescription,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.new_campaign_available'))
            ->line(__('notifications.new_campaign_available') . ': ' . $this->campaignTitle)
            ->line($this->campaignDescription)
            ->action(__('campaigns.view_campaign'), route('campaigns.show', $this->campaignId))
            ->line(__('notifications.campaign_email_footer'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'campaign_id' => $this->campaignId,
            'title' => $this->campaignTitle,
            'description' => $this->campaignDescription,
            'message' => $this->campaignDescription,
            'type' => 'campaign_created',
        ];
    }
}
