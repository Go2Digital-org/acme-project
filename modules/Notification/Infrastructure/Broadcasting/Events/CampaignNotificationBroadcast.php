<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Broadcasting\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Domain\Model\Campaign;

class CampaignNotificationBroadcast implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $additionalData
     */
    public function __construct(
        public readonly Campaign $campaign,
        public readonly string $eventType,
        /** @var array<string, mixed> */
        public readonly array $additionalData = [],
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel|PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Admin dashboard for significant campaign events
        $adminEvents = [
            'campaign.milestone',
            'campaign.approval_needed',
            'campaign.goal_reached',
            'campaign.pending_review',
        ];

        if (in_array($this->eventType, $adminEvents, true)) {
            $channels[] = new Channel('admin-dashboard');
        }

        // CSR admin channel for approval-needed events
        if ($this->eventType === 'campaign.approval_needed') {
            $channels[] = new Channel('admin-role-csr_admin');
        }

        // Organization-specific channel
        if ($this->campaign->organization_id) {
            $channels[] = new PrivateChannel("organization.{$this->campaign->organization_id}");
        }

        // Campaign-specific channel
        $channels[] = new PrivateChannel("campaign.{$this->campaign->id}");

        // Employee's (creator) personal notification channel
        if ($this->campaign->user_id) {
            $channels[] = new PrivateChannel("user.notifications.{$this->campaign->user_id}");
        }

        return $channels;
    }

    /**
     * Get the name of the broadcast event.
     */
    public function broadcastAs(): string
    {
        return $this->eventType;
    }

    /**
     * Get the data to broadcast.
     */
    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $baseData = [
            'campaign_id' => $this->campaign->id,
            'campaign_title' => $this->campaign->title,
            'campaign_description' => $this->campaign->description,
            'goal_amount' => (float) $this->campaign->goal_amount,
            'current_amount' => (float) $this->campaign->current_amount,
            'progress_percentage' => $this->campaign->getProgressPercentage(),
            'status' => $this->campaign->status->value,
            'employee_name' => $this->campaign->employee?->getName() ?? 'Unknown Employee',
            'organization_name' => $this->campaign->organization->getName(),
            'start_date' => $this->campaign->start_date?->toIso8601String(),
            'end_date' => $this->campaign->end_date?->toIso8601String(),
            'created_at' => $this->campaign->created_at?->toIso8601String(),
        ];

        // Add event-specific data
        return match ($this->eventType) {
            'campaign.milestone' => array_merge($baseData, [
                'milestone' => $this->additionalData['milestone'] ?? '50',
                'milestone_amount' => $this->additionalData['milestone_amount'] ?? 0,
                'previous_milestone' => $this->additionalData['previous_milestone'] ?? '25',
                'donors_count' => $this->campaign->donations_count ?? 0,
            ]),
            'campaign.approval_needed' => array_merge($baseData, [
                'submitted_at' => $this->additionalData['submitted_at'] ?? now()->toIso8601String(),
                'review_deadline' => $this->additionalData['review_deadline'] ?? null,
                'pending_since_hours' => $this->additionalData['pending_since_hours'] ?? 0,
                'changes_requested' => $this->additionalData['changes_requested'] ?? false,
            ]),
            'campaign.goal_reached' => array_merge($baseData, [
                'goal_reached_at' => $this->additionalData['goal_reached_at'] ?? now()->toIso8601String(),
                'final_amount' => $this->additionalData['final_amount'] ?? (float) $this->campaign->current_amount,
                'days_to_goal' => $this->additionalData['days_to_goal'] ?? 0,
                'total_donors' => $this->additionalData['total_donors'] ?? 0,
                'exceeded_by_amount' => max(0, (float) $this->campaign->current_amount - (float) $this->campaign->goal_amount),
            ]),
            'campaign.ending_soon' => array_merge($baseData, [
                'days_remaining' => $this->additionalData['days_remaining'] ?? 0,
                'hours_remaining' => $this->additionalData['hours_remaining'] ?? 0,
                'amount_needed' => max(0, (float) $this->campaign->goal_amount - (float) $this->campaign->current_amount),
                'is_urgent' => $this->additionalData['is_urgent'] ?? false,
            ]),
            'campaign.published' => array_merge($baseData, [
                'published_at' => $this->additionalData['published_at'] ?? now()->toIso8601String(),
                'approved_by' => $this->additionalData['approved_by'] ?? null,
                'public_url' => $this->additionalData['public_url'] ?? null,
            ]),
            'campaign.rejected' => array_merge($baseData, [
                'rejected_at' => $this->additionalData['rejected_at'] ?? now()->toIso8601String(),
                'rejected_by' => $this->additionalData['rejected_by'] ?? null,
                'rejection_reason' => $this->additionalData['rejection_reason'] ?? 'No reason provided',
                'rejection_details' => $this->additionalData['rejection_details'] ?? null,
                'can_resubmit' => $this->additionalData['can_resubmit'] ?? true,
            ]),
            default => $baseData,
        };
    }

    /**
     * The queue connection to use when broadcasting.
     */
    public function broadcastQueue(): string
    {
        return 'broadcasts';
    }

    /**
     * Determine if the event should be queued for broadcasting.
     */
    public function shouldBroadcast(): bool
    {
        // Don't broadcast test campaigns in production
        if (app()->environment('production') &&
            str_contains($this->campaign->title, '[TEST]')) {
            return false;
        }

        // Don't broadcast draft campaigns unless it's approval-related
        return ! ($this->campaign->status->value === 'draft' && ! in_array($this->eventType, ['campaign.approval_needed', 'campaign.pending_review'], true));
    }
}
