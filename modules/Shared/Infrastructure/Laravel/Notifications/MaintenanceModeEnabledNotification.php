<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class MaintenanceModeEnabledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $message
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Maintenance Mode Enabled - ACME Corp CSR Platform')
            ->line('The ACME Corp CSR Platform has entered maintenance mode.')
            ->line('Reason: ' . $this->message)
            ->line('Users will not be able to access the platform during this time.')
            ->action('Admin Panel', url('/admin'))
            ->line('You will be notified when maintenance mode is disabled.');
    }

    /**
     * Get the array representation of the notification.
     */
    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Maintenance Mode Enabled',
            'message' => $this->message,
            'type' => 'maintenance',
            'timestamp' => now(),
        ];
    }
}
