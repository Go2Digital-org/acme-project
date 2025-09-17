<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Notifications\Notifications;

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
        $amount = '$' . number_format($this->donation->amount, 2);
        $donorName = $this->donation->anonymous ? 'Anonymous' : ($this->donation->employee->name ?? 'Unknown');
        $campaignTitle = $this->donation->campaign->title ?? 'Unknown Campaign';

        return (new MailMessage)
            ->subject("Donation Processed Successfully: {$amount}")
            ->greeting('Donation Processed!')
            ->line('A donation has been successfully processed.')
            ->line("Amount: {$amount}")
            ->line("Campaign: {$campaignTitle}")
            ->line("Donor: {$donorName}")
            ->line('Payment Method: ' . ucfirst($this->donation->payment_method->value ?? 'Unknown'))
            ->line("Transaction ID: {$this->donation->transaction_id}")
            ->line('Status: ' . ucfirst($this->donation->status->value))
            ->line('The donation has been confirmed and funds have been processed.')
            ->action('View Details', url("/admin/donations/{$this->donation->id}"))
            ->salutation('Best regards, ACME Corp Finance Team');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(mixed $notifiable): DatabaseMessage
    {
        $donorName = $this->donation->anonymous ? 'Anonymous' : ($this->donation->employee->name ?? 'Unknown');

        return new DatabaseMessage([
            'title' => 'Donation Processed',
            'message' => 'Donation of $' . number_format($this->donation->amount, 2) . " from {$donorName} has been processed",
            'action_url' => url("/admin/donations/{$this->donation->id}"),
            'action_label' => 'View Details',
            'type' => 'donation_processed',
            'donation_id' => $this->donation->id,
            'amount' => $this->donation->amount,
            'campaign_id' => $this->donation->campaign?->id,
            'campaign_title' => $this->donation->campaign?->title,
            'donor_name' => $donorName,
            'payment_method' => $this->donation->payment_method?->value,
            'transaction_id' => $this->donation->transaction_id,
            'status' => $this->donation->status->value,
        ]);
    }
}
