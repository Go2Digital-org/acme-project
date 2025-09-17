<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Donation\Domain\Model\Donation;

final class DonationProcessedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Donation $donation,
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
        $donorName = $this->donation->anonymous ? 'Anonymous donor' : ($this->donation->employee->name ?? 'Unknown donor');
        $amount = '$' . number_format($this->donation->amount, 2);

        return (new MailMessage)
            ->subject('Donation Processed - ' . $amount)
            ->greeting('Donation Successfully Processed!')
            ->line("{$donorName} donation of {$amount} has been processed.")
            ->line('Campaign: ' . ($this->donation->campaign->title ?? 'Unknown Campaign'))
            ->line('Organization: ' . ($this->donation->campaign?->organization?->getName() ?? 'Unknown Organization'))
            ->line("Transaction ID: {$this->donation->transaction_id}")
            ->action('View Donation', route('filament.admin.resources.donations.view', $this->donation))
            ->salutation('Best regards, ACME Corp CSR Team');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(mixed $notifiable): DatabaseMessage
    {
        $donorName = $this->donation->anonymous ? 'Anonymous donor' : ($this->donation->employee->name ?? 'Unknown donor');
        $amount = '$' . number_format($this->donation->amount, 2);

        return new DatabaseMessage([
            'title' => 'Donation Processed',
            'message' => "{$donorName} donation of {$amount} has been successfully processed",
            'action_url' => route('filament.admin.resources.donations.view', $this->donation),
            'action_label' => 'View Donation',
            'type' => 'donation_processed',
            'donation_id' => $this->donation->id,
            'amount' => $this->donation->amount,
            'campaign_id' => $this->donation->campaign_id,
        ]);
    }
}
