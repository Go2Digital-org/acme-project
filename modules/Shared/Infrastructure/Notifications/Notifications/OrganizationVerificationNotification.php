<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Notifications\Notifications;

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
        $actionText = match ($this->action) {
            'verification_required' => 'requires verification',
            'verification_pending' => 'has pending verification',
            'verification_approved' => 'has been verified and approved',
            'verification_rejected' => 'verification has been rejected',
            'documents_updated' => 'has updated verification documents',
            'compliance_review' => 'needs compliance review',
            default => 'needs attention',
        };

        $subject = match ($this->action) {
            'verification_required' => 'Organization Verification Required',
            'verification_pending' => 'Organization Verification Pending',
            'verification_approved' => 'Organization Verification Approved',
            'verification_rejected' => 'Organization Verification Rejected',
            'documents_updated' => 'Organization Documents Updated',
            'compliance_review' => 'Organization Compliance Review Required',
            default => 'Organization Status Update',
        };

        return (new MailMessage)
            ->subject("{$subject}: {$this->organization->getName()}")
            ->greeting('Organization Verification Update')
            ->line("Organization '{$this->organization->getName()}' {$actionText}.")
            ->line("Category: {$this->organization->category}")
            ->line("Registration Number: {$this->organization->registration_number}")
            ->line("Contact: {$this->organization->email}")
            ->when($this->organization->website, fn (MailMessage $mail) => $mail->line("Website: {$this->organization->website}"))
            ->line("Address: {$this->organization->address}")
            ->when($this->action === 'verification_required', fn (MailMessage $mail) => $mail->line('Please review the organization details and verify their legitimacy before approving any campaigns.')
            )
            ->when($this->action === 'verification_pending', fn (MailMessage $mail) => $mail->line('The organization is awaiting verification review. Please process this request promptly.')
            )
            ->when($this->action === 'documents_updated', fn (MailMessage $mail) => $mail->line('New verification documents have been submitted. Please review the updated information.')
            )
            ->action('Review Organization', url("/admin/organizations/{$this->organization->id}"))
            ->salutation('Best regards, ACME Corp CSR Team');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(mixed $notifiable): DatabaseMessage
    {
        $actionLabels = [
            'verification_required' => 'Verification Required',
            'verification_pending' => 'Verification Pending',
            'verification_approved' => 'Verification Approved',
            'verification_rejected' => 'Verification Rejected',
            'documents_updated' => 'Documents Updated',
            'compliance_review' => 'Compliance Review Required',
        ];

        $title = $actionLabels[$this->action] ?? 'Organization Update';

        return new DatabaseMessage([
            'title' => $title,
            'message' => "Organization '{$this->organization->getName()}' {$this->action}",
            'action_url' => url("/admin/organizations/{$this->organization->id}"),
            'action_label' => 'Review Organization',
            'type' => 'organization_verification',
            'organization_id' => $this->organization->id,
            'organization_name' => $this->organization->getName(),
            'action' => $this->action,
            'category' => $this->organization->category,
            'registration_number' => $this->organization->registration_number,
            'contact_email' => $this->organization->email,
            'verification_status' => $this->organization->is_verified ? 'verified' : 'unverified',
        ]);
    }
}
