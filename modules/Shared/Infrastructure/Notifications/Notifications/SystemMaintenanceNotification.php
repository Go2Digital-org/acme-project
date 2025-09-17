<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Notifications\Notifications;

use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SystemMaintenanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $type,
        private readonly DateTime $scheduledFor,
        private readonly string $message,
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
            ->subject("System Maintenance Scheduled: {$this->type}")
            ->greeting('System Maintenance Notice')
            ->line("A system maintenance has been scheduled: {$this->type}")
            ->line("Scheduled for: {$this->scheduledFor->format('F j, Y \a\t g:i A')}")
            ->line("Details: {$this->message}")
            ->line('Please plan accordingly and ensure all critical operations are completed before the maintenance window.')
            ->salutation('Best regards, ACME Corp Technical Team');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(mixed $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'System Maintenance Scheduled',
            'message' => "Maintenance scheduled: {$this->type} on {$this->scheduledFor->format('M j, Y g:i A')}",
            'action_url' => null,
            'action_label' => null,
            'type' => 'system_maintenance',
            'maintenance_type' => $this->type,
            'scheduled_for' => $this->scheduledFor->format('c'),
            'details' => $this->message,
        ]);
    }
}
