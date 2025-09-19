<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Organization\Domain\Model\Organization;

final class OrganizationVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Organization $organization,
        private readonly string $action,
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
        $actionLabel = match ($this->action) {
            'pending_verification' => 'Verification Required',
            'verification_approved' => 'Verification Approved',
            'verification_rejected' => 'Verification Rejected',
            default => 'Organization Update',
        };

        return (new MailMessage)
            ->subject("Organization {$actionLabel}: {$this->organization->getName()}")
            ->greeting('Organization Status Update!')
            ->line("Organization '{$this->organization->getName()}' requires attention.")
            ->line("Action: {$this->action}")
            ->line("Status: {$this->organization->status}")
            ->action('Review Organization', route('filament.admin.resources.organizations.view', $this->organization))
            ->salutation('Best regards, ACME Corp CSR Team');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(mixed $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Organization Status Update',
            'message' => "Organization '{$this->organization->getName()}' - Action: {$this->action}",
            'action_url' => route('filament.admin.resources.organizations.view', $this->organization),
            'action_label' => 'Review Organization',
            'type' => 'organization_verification',
            'organization_id' => $this->organization->id,
            'action' => $this->action,
            'status' => $this->organization->status,
        ]);
    }
}
