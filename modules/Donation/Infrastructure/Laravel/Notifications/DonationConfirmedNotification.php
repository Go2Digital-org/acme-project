<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DonationConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $donationId,
        private readonly string $amount,
        private readonly string $campaignTitle,
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
            ->subject(__('notifications.donation_confirmed'))
            ->line(__('notifications.donation_processed', [
                'amount' => $this->amount,
                'campaign' => $this->campaignTitle,
            ]))
            ->action(__('donations.view_donation'), route('donations.show', $this->donationId))
            ->line(__('donations.thank_you_message'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'donation_id' => $this->donationId,
            'amount' => $this->amount,
            'campaign_title' => $this->campaignTitle,
            'message' => __('notifications.donation_processed', [
                'amount' => $this->amount,
                'campaign' => $this->campaignTitle,
            ]),
            'type' => 'donation_confirmed',
        ];
    }
}
