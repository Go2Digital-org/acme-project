<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Notifications;

use DateTime;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Donation\Domain\Model\Donation;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationPriority;
use Modules\Notification\Domain\Enum\NotificationType;
use Modules\Organization\Domain\Model\Organization;
use Modules\Shared\Infrastructure\Notifications\Events\AdminNotificationEvent;
use Modules\Shared\Infrastructure\Notifications\Notifications\CampaignMilestoneNotification;
use Modules\Shared\Infrastructure\Notifications\Notifications\DonationProcessedNotification;
use Modules\Shared\Infrastructure\Notifications\Notifications\LargeDonationNotification;
use Modules\Shared\Infrastructure\Notifications\Notifications\OrganizationVerificationNotification;
use Modules\Shared\Infrastructure\Notifications\Notifications\SecurityAlertNotification;
use Modules\Shared\Infrastructure\Notifications\Notifications\SystemMaintenanceNotification;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Shared admin notification service following hexagonal architecture principles.
 *
 * This service handles sending notifications to admin users across all modules,
 * supporting multiple notification channels and priorities based on the domain enums.
 */
class AdminNotificationService
{
    /**
     * Send notification when a large donation is received.
     */
    public function notifyLargeDonation(Donation $donation): void
    {
        if ($donation->amount < 1000) {
            return;
        }

        $admins = $this->getAdminUsers(['super_admin', 'csr_admin', 'finance_admin']);

        Notification::send($admins, new LargeDonationNotification($donation));

        // Real-time notification for urgent donations
        $this->broadcastToAdmins('donation.large', [
            'donation_id' => $donation->id,
            'amount' => $donation->amount,
            'campaign' => $donation->campaign->title ?? 'Unknown Campaign',
            'donor' => $donation->anonymous ? 'Anonymous' : ($donation->user->name ?? 'Unknown'),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Send notification when a donation is processed.
     */
    public function notifyDonationProcessed(Donation $donation): void
    {
        $admins = $this->getAdminUsers(['super_admin', 'csr_admin', 'finance_admin']);

        Notification::send($admins, new DonationProcessedNotification($donation));
    }

    /**
     * Send notification when a campaign reaches a milestone.
     */
    public function notifyCampaignMilestone(Campaign $campaign, string $milestone): void
    {
        $admins = $this->getAdminUsers(['super_admin', 'csr_admin']);

        Notification::send($admins, new CampaignMilestoneNotification($campaign, $milestone));

        // Real-time notification for campaign milestones
        $this->broadcastToAdmins('campaign.milestone', [
            'campaign_id' => $campaign->id,
            'campaign_title' => $campaign->title,
            'milestone' => $milestone,
            'current_amount' => $campaign->current_amount,
            'goal_amount' => $campaign->goal_amount,
            'progress_percentage' => $campaign->getProgressPercentage(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Send notification when an organization needs verification.
     */
    public function notifyOrganizationVerification(Organization $organization, string $action): void
    {
        $admins = $this->getAdminUsers(['super_admin', 'csr_admin']);

        Notification::send($admins, new OrganizationVerificationNotification($organization, $action));

        // Real-time notification for organization verification
        $this->broadcastToAdmins('organization.verification', [
            'organization_id' => $organization->id,
            'organization_name' => $organization->getName(),
            'action' => $action,
            'category' => $organization->category,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Send security alert notifications.
     */
    /**
     * @param  array<string, mixed>  $details
     */
    public function notifySecurityAlert(string $event, array $details): void
    {
        $admins = $this->getAdminUsers(['super_admin']);

        Notification::send($admins, new SecurityAlertNotification($event, $details));

        // Real-time notification for security alerts
        $this->broadcastToAdmins('security.alert', [
            'event' => $event,
            'details' => $details,
            'severity' => $details['severity'] ?? 'medium',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Send system maintenance notifications.
     */
    public function notifySystemMaintenance(string $type, DateTime $scheduledFor, string $message): void
    {
        $allAdmins = $this->getAdminUsers();

        Notification::send($allAdmins, new SystemMaintenanceNotification($type, $scheduledFor, $message));

        // Real-time notification for system maintenance
        $this->broadcastToAdmins('system.maintenance', [
            'type' => $type,
            'scheduled_for' => $scheduledFor->format('c'),
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Send real-time dashboard updates.
     */
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function broadcastDashboardUpdate(string $metric, mixed $value, array $metadata = []): void
    {
        $this->broadcastToAdmins('dashboard.update', [
            'metric' => $metric,
            'value' => $value,
            'metadata' => $metadata,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Send custom notification to specific admin roles with enhanced type support.
     */
    /**
     * @param  array<int, string>  $roles
     * @param  array<int, NotificationChannel>  $channels
     * @param  array<string, mixed>  $data
     */
    public function sendCustomNotification(
        array $roles,
        string $title,
        string $message,
        NotificationType $type = NotificationType::GENERIC,
        NotificationPriority $priority = NotificationPriority::MEDIUM,
        array $channels = [NotificationChannel::DATABASE],
        array $data = []
    ): void {
        $admins = $this->getAdminUsers($roles);

        // Convert channels to their appropriate values
        $channelValues = array_map(fn (NotificationChannel $channel): string => $channel->getValue(), $channels);

        foreach ($admins as $admin) {
            $notification = FilamentNotification::make()
                ->title($title)
                ->body($message)
                ->status($this->mapTypeToFilamentStatus($type))
                ->icon($type->getIcon())
                ->color($priority->getColorClass());

            // Send based on priority and channels
            if (in_array(NotificationChannel::DATABASE->getValue(), $channelValues)) {
                $notification->toDatabase();
                $admin->notify($notification);
            }
        }

        // Real-time notification for appropriate channels
        if ($this->shouldSendRealTime($channels, $priority)) {
            $this->broadcastToRoles($roles, 'custom.notification', [
                'title' => $title,
                'message' => $message,
                'type' => $type->getValue(),
                'priority' => $priority->getValue(),
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * Send notification when a campaign is created and needs approval.
     */
    public function notifyCampaignNeedsApproval(Campaign $campaign): void
    {
        // Check that the campaign status is actually pending approval
        if ($campaign->status !== CampaignStatus::PENDING_APPROVAL) {
            return;
        }

        $approvers = $this->getAdminUsers(['super_admin']);

        foreach ($approvers as $approver) {
            $managerName = $campaign->employee->name ?? 'Unknown';
            $campaignTitle = $campaign->getTitle();

            $notification = FilamentNotification::make()
                ->title('Campaign Awaiting Approval')
                ->body("'{$campaignTitle}' by {$managerName} needs approval")
                ->status('warning')
                ->icon(NotificationType::CAMPAIGN_PENDING_REVIEW->getIcon())
                ->actions([
                    Action::make('review')
                        ->button()
                        ->url(route('filament.admin.resources.campaigns.edit', $campaign)),
                ])
                ->toDatabase();

            $approver->notify($notification);
        }

        // Real-time notification
        $this->broadcastToAdmins('campaign.approval_needed', [
            'campaign_id' => $campaign->id,
            'campaign_title' => $campaign->getTitle(),
            'manager' => $campaign->employee->name ?? 'Unknown',
            'organization' => $campaign->organization->getName(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Send notification for payment failures.
     */
    public function notifyPaymentFailed(Donation $donation, string $reason): void
    {
        $admins = $this->getAdminUsers(['super_admin', 'finance_admin']);

        foreach ($admins as $admin) {
            $notification = FilamentNotification::make()
                ->title('Payment Failed')
                ->body('Payment of $' . number_format($donation->amount, 2) . " failed: {$reason}")
                ->status('danger')
                ->icon(NotificationType::PAYMENT_FAILED->getIcon())
                ->actions([
                    Action::make('investigate')
                        ->button()
                        ->url(route('filament.admin.resources.donations.view', $donation)),
                ])
                ->toDatabase();

            $admin->notify($notification);
        }

        // Real-time notification
        $this->broadcastToAdmins('payment.failed', [
            'donation_id' => $donation->id,
            'amount' => $donation->amount,
            'reason' => $reason,
            'campaign' => $donation->campaign->title ?? 'Unknown Campaign',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Send notification for compliance issues.
     */
    /**
     * @param  array<string, mixed>  $issues
     */
    public function notifyComplianceIssue(Organization $organization, array $issues): void
    {
        $admins = $this->getAdminUsers(['super_admin', 'csr_admin']);

        $issuesList = implode(', ', $issues);

        foreach ($admins as $admin) {
            $notification = FilamentNotification::make()
                ->title('Compliance Issues Detected')
                ->body("Issues found for {$organization->getName()}: {$issuesList}")
                ->status('warning')
                ->icon(NotificationType::COMPLIANCE_ISSUES->getIcon())
                ->actions([
                    Action::make('review')
                        ->button()
                        ->url(route('filament.admin.resources.organizations.view', $organization)),
                ])
                ->toDatabase();

            $admin->notify($notification);
        }

        // Real-time notification
        $this->broadcastToAdmins('compliance.issues', [
            'organization_id' => $organization->id,
            'organization_name' => $organization->getName(),
            'issues' => $issues,
            'severity' => count($issues) > 3 ? 'high' : 'medium',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Send notification when a translation is completed.
     */
    /**
     * @param  array<string, mixed>  $translatedFields
     */
    public function notifyTranslationCompleted(
        string $modelType,
        int $modelId,
        string $locale,
        array $translatedFields,
        ?int $translatorId = null
    ): void {
        $admins = $this->getAdminUsers(['super_admin', 'csr_admin']);

        $fieldsList = implode(', ', $translatedFields);

        foreach ($admins as $admin) {
            $notification = FilamentNotification::make()
                ->title('Translation Completed')
                ->body("Translation completed for {$modelType} #{$modelId} in {$locale}. Fields: {$fieldsList}")
                ->status('success')
                ->icon(NotificationType::TRANSLATION_COMPLETED->getIcon())
                ->toDatabase();

            $admin->notify($notification);
        }

        // Real-time notification
        $this->broadcastToAdmins('translation.completed', [
            'model_type' => $modelType,
            'model_id' => $modelId,
            'locale' => $locale,
            'translated_fields' => $translatedFields,
            'translator_id' => $translatorId,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Send generic notification to admins with subject, data and template.
     */
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $recipients
     */
    public function sendToAdmins(
        string $subject,
        array $data,
        string $template = 'admin.notification',
        array $recipients = []
    ): void {
        $admins = $recipients === []
            ? $this->getAdminUsers()
            : $this->getAdminUsers($recipients);

        foreach ($admins as $admin) {
            $notification = FilamentNotification::make()
                ->title($subject)
                ->body($this->formatNotificationBody($data, $template))
                ->status('info')
                ->toDatabase();

            $admin->notify($notification);
        }

        // Real-time notification
        $this->broadcastToAdmins('admin.generic_notification', [
            'subject' => $subject,
            'data' => $data,
            'template' => $template,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Send typed notification with enhanced domain support.
     */
    /**
     * @param  array<int, string>  $roles
     * @param  array<int, NotificationChannel>  $channels
     * @param  array<string, mixed>  $data
     */
    public function sendTypedNotification(
        NotificationType $type,
        NotificationPriority $priority,
        string $title,
        string $message,
        array $roles = [],
        array $channels = [NotificationChannel::DATABASE],
        array $data = []
    ): void {
        $admins = $roles === []
            ? $this->getAdminUsers()
            : $this->getAdminUsers($roles);

        foreach ($admins as $admin) {
            $notification = FilamentNotification::make()
                ->title($title)
                ->body($message)
                ->status($this->mapTypeToFilamentStatus($type))
                ->icon($type->getIcon())
                ->color($priority->getColorClass());

            if (in_array(NotificationChannel::DATABASE, $channels)) {
                $notification->toDatabase();
                $admin->notify($notification);
            }
        }

        // Real-time broadcasting for appropriate types
        if ($type->isRealTime() || $priority->requiresImmediateProcessing()) {
            $this->broadcastToAdmins($type->getValue(), [
                'title' => $title,
                'message' => $message,
                'priority' => $priority->getValue(),
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * Get admin users by roles.
     *
     * @param  array<int, string>  $roles
     * @return Collection<int, User>
     */
    private function getAdminUsers(array $roles = []): Collection
    {
        $query = User::query();

        if ($roles !== []) {
            // Use Spatie role system or fallback to role field
            $query->where(function (Builder $query) use ($roles): void {
                $query->role($roles)  // Spatie Laravel Permission
                    ->orWhereIn('role', $roles);  // Fallback to role field
            });
        } else {
            // Default admin roles
            $defaultRoles = ['super_admin', 'csr_admin', 'finance_admin', 'hr_manager'];
            $query->where(function (Builder $query) use ($defaultRoles): void {
                $query->role($defaultRoles)  // Spatie Laravel Permission
                    ->orWhereIn('role', $defaultRoles);  // Fallback to role field
            });
        }

        return $query->where('is_active', true)->get();
    }

    /**
     * Broadcast message to all admin users.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    private function broadcastToAdmins(string $event, array $data): void
    {
        $channelName = 'admin-dashboard';

        broadcast(new AdminNotificationEvent(
            $channelName,
            $event,
            $data
        ));
    }

    /**
     * Broadcast message to specific roles.
     */
    /**
     * @param  array<int, string>  $roles
     * @param  array<string, mixed>  $data
     */
    private function broadcastToRoles(array $roles, string $event, array $data): void
    {
        foreach ($roles as $role) {
            $channelName = "admin-role-{$role}";

            broadcast(new AdminNotificationEvent(
                $channelName,
                $event,
                $data
            ));
        }
    }

    /**
     * Format notification body from data and template.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    private function formatNotificationBody(array $data, string $template): string
    {
        // Simple template processing - replace placeholders with data values
        $body = match ($template) {
            'admin.notification' => $this->getDefaultNotificationTemplate($data),
            default => $this->getDefaultNotificationTemplate($data),
        };

        // Replace placeholders in the format {key} with values from data
        foreach ($data as $key => $value) {
            $placeholder = "{{$key}}";
            $body = str_replace($placeholder, (string) $value, $body);
        }

        return $body;
    }

    /**
     * Get default notification template.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    private function getDefaultNotificationTemplate(array $data): string
    {
        $items = [];

        foreach ($data as $key => $value) {
            $items[] = ucfirst(str_replace('_', ' ', $key)) . ": {$value}";
        }

        return implode("\n", $items);
    }

    /**
     * Map NotificationType to Filament status.
     */
    private function mapTypeToFilamentStatus(NotificationType $type): string
    {
        return match ($type) {
            NotificationType::SECURITY_ALERT,
            NotificationType::PAYMENT_FAILED,
            NotificationType::DONATION_FAILED => 'danger',

            NotificationType::CAMPAIGN_PENDING_REVIEW,
            NotificationType::COMPLIANCE_ISSUES,
            NotificationType::SYSTEM_MAINTENANCE => 'warning',

            NotificationType::CAMPAIGN_APPROVED,
            NotificationType::CAMPAIGN_COMPLETED,
            NotificationType::TRANSLATION_COMPLETED => 'success',

            default => 'info',
        };
    }

    /**
     * Determine if notification should be sent via real-time channels.
     */
    /**
     * @param  array<int, NotificationChannel>  $channels
     */
    private function shouldSendRealTime(array $channels, NotificationPriority $priority): bool
    {
        // Send real-time for urgent priorities
        if ($priority->requiresImmediateProcessing()) {
            return true;
        }

        // Send real-time if any real-time channel is requested
        foreach ($channels as $channel) {
            if ($channel->supportsRealTime()) {
                return true;
            }
        }

        return false;
    }
}
