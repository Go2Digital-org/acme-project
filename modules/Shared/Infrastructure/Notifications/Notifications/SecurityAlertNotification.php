<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SecurityAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $event,
        /** @var array<string, mixed> */
        private readonly array $details,
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
        $severity = $this->details['severity'] ?? 'medium';
        $description = $this->details['description'] ?? 'Security event detected';

        return (new MailMessage)
            ->subject("Security Alert: {$this->event}")
            ->greeting('Security Alert!')
            ->line("A security event has been detected: {$this->event}")
            ->line('Severity: ' . ucfirst($severity))
            ->line("Description: {$description}")
            ->when(isset($this->details['ip_address']), fn (MailMessage $mail) => $mail->line("IP Address: {$this->details['ip_address']}"))
            ->when(isset($this->details['user_agent']), fn (MailMessage $mail) => $mail->line("User Agent: {$this->details['user_agent']}"))
            ->line('Please review this alert immediately.')
            ->salutation('Best regards, ACME Corp Security Team');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(mixed $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Security Alert',
            'message' => "Security event detected: {$this->event}",
            'action_url' => null,
            'action_label' => null,
            'type' => 'security_alert',
            'event' => $this->event,
            'severity' => $this->details['severity'] ?? 'medium',
            'details' => $this->details,
        ]);
    }
}
