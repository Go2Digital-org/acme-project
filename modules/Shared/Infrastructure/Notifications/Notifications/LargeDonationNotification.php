<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Donation\Domain\Model\Donation;

final class LargeDonationNotification extends Notification implements ShouldQueue
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
        $donorName = $this->donation->anonymous ? 'Anonymous' : ($this->donation->user->name ?? 'Unknown');
        $campaignTitle = $this->donation->campaign->title ?? 'Unknown Campaign';

        return (new MailMessage)
            ->subject("Large Donation Received: {$amount}")
            ->greeting('Large Donation Alert!')
            ->line("A significant donation of {$amount} has been received.")
            ->line("Campaign: {$campaignTitle}")
            ->line("Donor: {$donorName}")
            ->when(! $this->donation->anonymous && $this->donation->user, fn (MailMessage $mail) => $mail->line("User ID: {$this->donation->user?->id}"))
            ->line("Donation ID: {$this->donation->id}")
            ->line('This donation exceeds the large donation threshold and requires your attention.')
            ->action('Review Donation', url("/admin/donations/{$this->donation->id}"))
            ->salutation('Best regards, ACME Corp CSR Team');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(mixed $notifiable): DatabaseMessage
    {
        $donorName = $this->donation->anonymous ? 'Anonymous' : ($this->donation->user->name ?? 'Unknown');

        return new DatabaseMessage([
            'title' => 'Large Donation Received',
            'message' => 'Large donation of $' . number_format($this->donation->amount, 2) . " from {$donorName}",
            'action_url' => url("/admin/donations/{$this->donation->id}"),
            'action_label' => 'Review Donation',
            'type' => 'large_donation',
            'donation_id' => $this->donation->id,
            'amount' => $this->donation->amount,
            'campaign_id' => $this->donation->campaign?->id,
            'campaign_title' => $this->donation->campaign?->title,
            'donor_name' => $donorName,
            'is_anonymous' => $this->donation->anonymous,
        ]);
    }
}
