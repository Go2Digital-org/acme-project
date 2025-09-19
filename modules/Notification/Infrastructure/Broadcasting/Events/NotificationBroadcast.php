<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Broadcasting\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Notification\Domain\Model\Notification;

class NotificationBroadcast implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Notification $notification,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel|PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Always broadcast to the recipient's private channel
        if ($this->notification->notifiable_id) {
            $channels[] = new PrivateChannel("user.notifications.{$this->notification->notifiable_id}");
        }

        // Add admin dashboard channel for admin notifications
        if ($this->isAdminNotification()) {
            $channels[] = new Channel('admin-dashboard');

            // Add role-specific channels if applicable
            if ($roleChannel = $this->getRoleSpecificChannel()) {
                $channels[] = new Channel($roleChannel);
            }
        }

        // Add organization-specific channel if applicable
        /** @var int|string|null $organizationId */
        $organizationId = $this->notification->metadata['organization_id'] ?? null;
        if ($organizationId !== null) {
            $channels[] = new PrivateChannel("organization.{$organizationId}");
        }

        // Add campaign-specific channel if applicable
        /** @var int|string|null $campaignId */
        $campaignId = $this->notification->metadata['campaign_id'] ?? null;
        if ($campaignId !== null) {
            $channels[] = new PrivateChannel("campaign.{$campaignId}");
        }

        return $channels;
    }

    /**
     * Get the name of the broadcast event.
     */
    public function broadcastAs(): string
    {
        return $this->notification->type;
    }

    /**
     * Get the data to broadcast.
     */
    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'priority' => $this->notification->priority,
            'status' => $this->notification->status,
            'metadata' => $this->notification->metadata,
            'created_at' => $this->notification->created_at->toISOString(),
            'sender' => $this->notification->sender ? [
                'id' => $this->notification->sender->getAttribute('id'),
                'name' => $this->notification->sender->getAttribute('name') ?? 'Unknown',
                'email' => $this->notification->sender->getAttribute('email') ?? '',
            ] : null,
            'actions' => $this->formatActions(),
        ];
    }

    /**
     * Determine if the event should be queued for broadcasting.
     */
    public function shouldBroadcast(): bool
    {
        // Don't broadcast test notifications in production
        if (app()->environment('production') && str_contains($this->notification->type, 'test')) {
            return false;
        }

        // Don't broadcast to inactive users for non-critical notifications
        if ((is_object($this->notification->priority) ? $this->notification->priority->value : $this->notification->priority) !== 'high' &&
            $this->notification->recipient &&
            ! $this->isRecipientActive()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if this is an admin notification.
     */
    private function isAdminNotification(): bool
    {
        $adminTypes = [
            'donation.large',
            'campaign.milestone',
            'organization.verification',
            'security.alert',
            'system.maintenance',
            'campaign.approval_needed',
            'payment.failed',
            'compliance.issues',
        ];

        return in_array($this->notification->type, $adminTypes, true);
    }

    /**
     * Get role-specific channel based on notification type.
     */
    private function getRoleSpecificChannel(): ?string
    {
        $roleMapping = [
            'payment.failed' => 'admin-role-finance_admin',
            'payment.processing' => 'admin-role-finance_admin',
            'compliance.issues' => 'admin-role-csr_admin',
            'organization.verification' => 'admin-role-csr_admin',
            'security.alert' => 'admin-role-super_admin',
            'system.maintenance' => 'admin-role-super_admin',
        ];

        return $roleMapping[$this->notification->type] ?? null;
    }

    /**
     * Format notification actions for broadcasting.
     *
     * @return array<int, array<string, mixed>>
     */
    private function formatActions(): array
    {
        /** @var array<int, array<string, mixed>> $actions */
        $actions = [];
        /** @var array<string, mixed> $metadata */
        $metadata = $this->notification->metadata ?? [];

        switch ($this->notification->type) {
            case 'donation.large':
                /** @var string $donationId */
                $donationId = $metadata['donation_id'] ?? '';
                $actions[] = [
                    'label' => 'View Details',
                    'url' => '/admin/donations/' . $donationId,
                    'type' => 'primary',
                ];
                break;

            case 'campaign.milestone':
                /** @var string $campaignId */
                $campaignId = $metadata['campaign_id'] ?? '';
                $actions[] = [
                    'label' => 'View Campaign',
                    'url' => '/admin/campaigns/' . $campaignId,
                    'type' => 'primary',
                ];
                break;

            case 'organization.verification':
                /** @var string $orgId */
                $orgId = $metadata['organization_id'] ?? '';
                $actions[] = [
                    'label' => 'Review',
                    'url' => '/admin/organizations/' . $orgId,
                    'type' => 'primary',
                ];
                break;

            case 'security.alert':
                $actions[] = [
                    'label' => 'Investigate',
                    'url' => '/admin/audit-logs',
                    'type' => 'danger',
                ];
                break;

            case 'campaign.approval_needed':
                /** @var string $campaignId */
                $campaignId = $metadata['campaign_id'] ?? '';
                $actions[] = [
                    'label' => 'Review Campaign',
                    'url' => '/admin/campaigns/' . $campaignId,
                    'type' => 'warning',
                ];
                break;

            case 'payment.failed':
                /** @var string $donationId */
                $donationId = $metadata['donation_id'] ?? '';
                $actions[] = [
                    'label' => 'Investigate',
                    'url' => '/admin/donations/' . $donationId,
                    'type' => 'danger',
                ];
                break;
        }

        return $actions;
    }

    /**
     * Check if the recipient is active.
     */
    private function isRecipientActive(): bool
    {
        $recipient = $this->notification->recipient;

        if (! $recipient) {
            return false;
        }

        // Try to call isActive() method if it exists
        if (method_exists($recipient, 'isActive')) {
            return $recipient->isActive();
        }

        // Fallback: check for common active status patterns
        $status = $recipient->getAttribute('status') ?? $recipient->getAttribute('is_active') ?? true;

        return in_array($status, [true, 'active', 1, '1'], true);
    }
}
