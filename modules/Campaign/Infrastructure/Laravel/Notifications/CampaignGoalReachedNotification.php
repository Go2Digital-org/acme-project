<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignGoalReachedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $campaignId,
        private readonly string $campaignTitle,
        private readonly string $goalAmount,
    ) {}

    /** @return array<array-key, mixed> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.campaign_goal_reached'))
            ->line(__('notifications.campaign_goal_reached') . ': ' . $this->campaignTitle)
            ->line(__('notifications.goal_reached_message', ['amount' => $this->goalAmount]))
            ->action(__('campaigns.view_campaign'), route('campaigns.show', $this->campaignId))
            ->line(__('notifications.thank_you_support'));
    }

    /** @return array<array-key, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'campaign_id' => $this->campaignId,
            'title' => $this->campaignTitle,
            'goal_amount' => $this->goalAmount,
            'message' => __('notifications.goal_reached_message', ['amount' => $this->goalAmount]),
            'type' => 'campaign_goal_reached',
        ];
    }
}
