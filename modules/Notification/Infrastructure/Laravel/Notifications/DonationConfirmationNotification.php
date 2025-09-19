<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;

/**
 * Multi-channel donation confirmation notification
 *
 * Supports email, SMS, push notifications, in-app notifications, and Slack
 * with user preference handling and dynamic channel selection.
 */
final class DonationConfirmationNotification extends Notification
{
    use Queueable;

    public function __construct(
        /** @var array<string, mixed> */
        private readonly array $donation,
        private readonly string $receiptUrl,
        /** @var array<string, mixed> */
        private readonly array $campaign,
        /** @var array<string, mixed>|null */
        private readonly ?array $userPreferences = null,
    ) {
        // Remove queue settings for testing
    }

    /**
     * Determine notification channels based on user preferences
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        $channels = ['mail', 'database'];

        // Add SMS if user has enabled it and phone number is available
        if ($this->shouldSendSms($notifiable)) {
            $channels[] = 'sms';
        }

        // Add push notifications if user has enabled them
        if ($this->shouldSendPush()) {
            $channels[] = 'broadcast';
        }

        // Add Slack if user has webhook configured
        if ($this->shouldSendSlack($notifiable)) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    /**
     * Email notification with rich HTML template
     */
    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $impactStatement = $this->generateImpactStatement();
        $this->calculateProgressPercentage();

        return (new MailMessage)
            ->subject("Thank you for your donation to {$this->campaign['title']}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your donation of \${$this->donation['amount']} has been successfully processed.")
            ->line("Campaign: {$this->campaign['title']}")
            ->line("Organization: {$this->campaign['organization_name']}")
            ->line($impactStatement)
            ->action('Download Receipt', $this->receiptUrl)
            ->line('Your contribution makes a real difference!')
            ->line("Transaction ID: {$this->donation['payment_reference']}")
            ->salutation('Thank you for your generosity!');
    }

    /**
     * SMS notification message
     */
    /**
     * @param  mixed  $notifiable
     */
    public function toSms($notifiable): string
    {
        $shortUrl = $this->getShortUrl($this->receiptUrl);

        return "ACME CSR: Your donation of \${$this->donation['amount']} to " .
               "{$this->campaign['title']} was successful! " .
               "Receipt: {$shortUrl}";
    }

    /**
     * Database notification for in-app display
     */
    /**
     * @return array<string, mixed>
     */
    /**
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'donation_confirmation',
            'donation_id' => $this->donation['id'],
            'amount' => $this->donation['amount'],
            'campaign_title' => $this->campaign['title'],
            'campaign_id' => $this->campaign['id'],
            'receipt_url' => $this->receiptUrl,
            'message' => "Your donation of \${$this->donation['amount']} was successful!",
            'icon' => 'check-circle',
            'color' => 'green',
            'priority' => 'normal',
            'actions' => [
                [
                    'label' => 'View Receipt',
                    'url' => $this->receiptUrl,
                    'type' => 'primary',
                ],
                [
                    'label' => 'Share Campaign',
                    'url' => url('/campaigns/' . $this->campaign['id'] . '/share'),
                    'type' => 'secondary',
                ],
            ],
        ];
    }

    /**
     * Broadcast notification for real-time updates
     */
    /**
     * @param  mixed  $notifiable
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => 'Donation Successful!',
            'body' => "Your \${$this->donation['amount']} donation has been processed.",
            'action_url' => $this->receiptUrl,
            'icon' => asset('icons/success.png'),
            'tag' => 'donation-confirmation',
            'data' => [
                'donation_id' => $this->donation['id'],
                'campaign_id' => $this->campaign['id'],
                'amount' => $this->donation['amount'],
            ],
        ]);
    }

    /**
     * Slack notification for corporate channels
     */
    /**
     * @return array<string, mixed>
     */
    /**
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toSlack($notifiable): array
    {
        return [
            'webhook_url' => $notifiable->slack_webhook_url ?? 'https://hooks.slack.com/test',
            'message' => [
                'text' => 'New Donation Received!',
                'attachments' => [
                    [
                        'color' => 'good',
                        'title' => $this->campaign['title'],
                        'fields' => [
                            [
                                'title' => 'Donor',
                                'value' => $notifiable->name,
                                'short' => true,
                            ],
                            [
                                'title' => 'Amount',
                                'value' => '$' . number_format((float) $this->donation['amount'], 2),
                                'short' => true,
                            ],
                            [
                                'title' => 'Organization',
                                'value' => $this->campaign['organization_name'],
                                'short' => true,
                            ],
                            [
                                'title' => 'Time',
                                'value' => now()->format('g:i A'),
                                'short' => true,
                            ],
                        ],
                        'footer' => 'ACME CSR Platform',
                        'footer_icon' => asset('logo.png'),
                        'ts' => now()->timestamp,
                    ],
                ],
            ],
        ];
    }

    /**
     * Check if SMS should be sent
     */
    /**
     * @param  mixed  $notifiable
     */
    private function shouldSendSms($notifiable): bool
    {
        // Check if user has SMS enabled in preferences
        if ($this->userPreferences && ! ($this->userPreferences['sms_enabled'] ?? false)) {
            return false;
        }

        // Check if user has a phone number
        if (empty($notifiable->phone)) {
            return false;
        }

        // Check if Twilio is configured
        return Config::get('services.twilio.sid') && Config::get('services.twilio.token');
    }

    /**
     * Check if push notifications should be sent
     */
    private function shouldSendPush(): bool
    {
        // Check if user has push notifications enabled
        if ($this->userPreferences && ! ($this->userPreferences['push_enabled'] ?? true)) {
            return false;
        }

        // Check quiet hours
        return ! $this->isInQuietHours();
    }

    /**
     * Check if Slack notification should be sent
     */
    /**
     * @param  mixed  $notifiable
     */
    private function shouldSendSlack($notifiable): bool
    {
        return ! empty($notifiable->slack_webhook_url ?? null);
    }

    /**
     * Check if current time is in user's quiet hours
     */
    private function isInQuietHours(): bool
    {
        if (! $this->userPreferences) {
            return false;
        }
        $quietStart = $this->userPreferences['quiet_hours_start'] ?? null;
        $quietEnd = $this->userPreferences['quiet_hours_end'] ?? null;
        if (! $quietStart || ! $quietEnd) {
            return false;
        }
        $timezone = $this->userPreferences['timezone'] ?? 'UTC';
        $now = now($timezone);
        $currentTime = $now->format('H:i');

        return $currentTime >= $quietStart || $currentTime <= $quietEnd;
    }

    /**
     * Generate impact statement based on campaign progress
     */
    private function generateImpactStatement(): string
    {
        $percentage = $this->calculateProgressPercentage();
        if ($percentage >= 100) {
            return 'Thanks to you, this campaign has reached its goal!';
        }

        if ($percentage >= 75) {
            return 'Your donation helped push this campaign past 75% of its goal!';
        }
        $remaining = $this->campaign['goal_amount'] - $this->campaign['current_amount'];

        return 'Only $' . number_format((float) $remaining, 2) . ' left to reach the goal!';
    }

    /**
     * Calculate campaign progress percentage
     */
    private function calculateProgressPercentage(): float
    {
        if ($this->campaign['goal_amount'] <= 0) {
            return 0;
        }

        return ($this->campaign['current_amount'] / $this->campaign['goal_amount']) * 100;
    }

    /**
     * Generate short URL for SMS
     */
    private function getShortUrl(string $url): string
    {
        // In a real implementation, you would use a URL shortening service
        // For now, just return the original URL or a simplified version
        return $url;
    }
}
