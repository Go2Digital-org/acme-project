<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Laravel\Notifications;

use DateTime;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Modules\Organization\Domain\Model\Organization;
use Modules\Shared\Infrastructure\Notifications\Events\AdminNotificationEvent;
use Modules\Shared\Infrastructure\Notifications\Notifications\CampaignMilestoneNotification;
use Modules\Shared\Infrastructure\Notifications\Notifications\DonationProcessedNotification;
use Modules\Shared\Infrastructure\Notifications\Notifications\LargeDonationNotification;
use Modules\Shared\Infrastructure\Notifications\Notifications\OrganizationVerificationNotification;
use Modules\Shared\Infrastructure\Notifications\Notifications\SecurityAlertNotification;
use Modules\Shared\Infrastructure\Notifications\Notifications\SystemMaintenanceNotification;
use Modules\User\Infrastructure\Laravel\Models\User;

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

        // Real-time notification
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

        // Real-time notification
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

        // Real-time notification
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
     *
     * @param  array<string, mixed>  $details
     */
    public function notifySecurityAlert(string $event, array $details): void
    {
        $admins = $this->getAdminUsers(['super_admin']);

        Notification::send($admins, new SecurityAlertNotification($event, $details));

        // Real-time notification
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

        // Real-time notification
        $this->broadcastToAdmins('system.maintenance', [
            'type' => $type,
            'scheduled_for' => $scheduledFor->format('c'),
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Send real-time dashboard updates.
     *
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
     * Send custom notification to specific admin roles.
     *
     * @param  array<string>  $roles
     * @param  array<string, mixed>  $data
     */
    public function sendCustomNotification(
        array $roles,
        string $title,
        string $message,
        string $type = 'info',
        array $data = [],
    ): void {
        $admins = $this->getAdminUsers($roles);

        foreach ($admins as $admin) {
            $notification = \Filament\Notifications\Notification::make()
                ->title($title)
                ->body($message)
                ->status($type)
                ->toDatabase();

            $admin->notify($notification);
        }

        // Real-time notification
        $this->broadcastToRoles($roles, 'custom.notification', [
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Send notification when a campaign is created and needs approval.
     */
    public function notifyCampaignNeedsApproval(Campaign $campaign): void
    {
        $approvers = $this->getAdminUsers(['super_admin', 'csr_admin', 'hr_manager']);

        foreach ($approvers as $approver) {
            $managerName = $campaign->employee->name ?? 'Unknown';
            $approver->notify(
                \Filament\Notifications\Notification::make()
                    ->title('Campaign Awaiting Approval')
                    ->body("'{$campaign->title}' by {$managerName} needs approval")
                    ->status('warning')
                    ->actions([
                        Action::make('review')
                            ->button()
                            ->url(route('filament.admin.resources.campaigns.view', $campaign)),
                    ])
                    ->toDatabase(),
            );
        }

        // Real-time notification
        $this->broadcastToAdmins('campaign.approval_needed', [
            'campaign_id' => $campaign->id,
            'campaign_title' => $campaign->title,
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
            $admin->notify(
                \Filament\Notifications\Notification::make()
                    ->title('Payment Failed')
                    ->body('Payment of $' . number_format($donation->amount, 2) . " failed: {$reason}")
                    ->status('danger')
                    ->actions([
                        Action::make('investigate')
                            ->button()
                            ->url(route('filament.admin.resources.donations.view', $donation)),
                    ])
                    ->toDatabase(),
            );
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
     *
     * @param  array<string>  $issues
     */
    public function notifyComplianceIssue(Organization $organization, array $issues): void
    {
        $admins = $this->getAdminUsers(['super_admin', 'csr_admin']);

        $issuesList = implode(', ', $issues);

        foreach ($admins as $admin) {
            $admin->notify(
                \Filament\Notifications\Notification::make()
                    ->title('Compliance Issues Detected')
                    ->body("Issues found for {$organization->getName()}: {$issuesList}")
                    ->status('warning')
                    ->actions([
                        Action::make('review')
                            ->button()
                            ->url(route('filament.admin.resources.organizations.view', $organization)),
                    ])
                    ->toDatabase(),
            );
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
     *
     * @param  array<string>  $translatedFields
     */
    public function notifyTranslationCompleted(
        string $modelType,
        int $modelId,
        string $locale,
        array $translatedFields,
        ?int $translatorId = null,
    ): void {
        $admins = $this->getAdminUsers(['super_admin', 'csr_admin']);

        $fieldsList = implode(', ', $translatedFields);

        foreach ($admins as $admin) {
            $admin->notify(
                \Filament\Notifications\Notification::make()
                    ->title('Translation Completed')
                    ->body("Translation completed for {$modelType} #{$modelId} in {$locale}. Fields: {$fieldsList}")
                    ->status('success')
                    ->toDatabase(),
            );
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
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>  $recipients
     */
    public function sendToAdmins(
        string $subject,
        array $data,
        string $template = 'admin.notification',
        array $recipients = [],
    ): void {
        $admins = $recipients === []
            ? $this->getAdminUsers()
            : $this->getAdminUsers($recipients);

        foreach ($admins as $admin) {
            $admin->notify(
                \Filament\Notifications\Notification::make()
                    ->title($subject)
                    ->body($this->formatNotificationBody($data, $template))
                    ->status('info')
                    ->toDatabase(),
            );
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
     * Get admin users by roles.
     *
     * @param  array<string>  $roles
     * @return Collection<int, User>
     */
    private function getAdminUsers(array $roles = []): Collection
    {
        $query = User::query();

        if ($roles !== []) {
            $query->whereIn('role', $roles);
        } else {
            $query->whereIn('role', ['super_admin', 'csr_admin', 'finance_admin', 'hr_manager']);
        }

        return $query->where('is_active', true)->get();
    }

    /**
     * Broadcast message to all admin users.
     *
     * @param  array<string, mixed>  $data
     */
    private function broadcastToAdmins(string $event, array $data): void
    {
        $channelName = 'admin-dashboard';

        broadcast(new AdminNotificationEvent(
            $channelName,
            $event,
            $data,
        ));
    }

    /**
     * Format notification body from data and template.
     *
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
            $body = str_replace($placeholder, $value, $body);
        }

        return $body;
    }

    /**
     * Get default notification template.
     *
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
     * Broadcast message to specific roles.
     *
     * @param  array<string>  $roles
     * @param  array<string, mixed>  $data
     */
    private function broadcastToRoles(array $roles, string $event, array $data): void
    {
        foreach ($roles as $role) {
            $channelName = "admin-role-{$role}";

            broadcast(new AdminNotificationEvent(
                $channelName,
                $event,
                $data,
            ));
        }
    }
}
