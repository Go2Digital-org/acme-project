<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Broadcasting\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemNotificationBroadcast implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $eventType,
        public readonly string $title,
        public readonly string $message,
        public readonly string $severity,
        /** @var array<string, mixed> */
        public readonly array $metadata = [],
        /** @var array<int, int>|null */
        public readonly ?array $targetUserIds = null,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Security alerts only go to super admins
        if ($this->eventType === 'security.alert') {
            $channels[] = new Channel('security-alerts');
            $channels[] = new Channel('admin-role-super_admin');
        }

        // System maintenance goes to all admins
        if ($this->eventType === 'system.maintenance') {
            $channels[] = new Channel('system-maintenance');
        }

        // Compliance issues go to CSR admins
        if ($this->eventType === 'compliance.issues') {
            $channels[] = new Channel('compliance-notifications');
            $channels[] = new Channel('admin-role-csr_admin');
        }

        // General admin dashboard for most system events
        $dashboardEvents = [
            'system.maintenance',
            'security.alert',
            'compliance.issues',
            'system.backup_completed',
            'system.backup_failed',
            'system.performance_alert',
        ];

        if (in_array($this->eventType, $dashboardEvents, true)) {
            $channels[] = new Channel('admin-dashboard');
        }

        // Target specific users if specified
        if ($this->targetUserIds) {
            foreach ($this->targetUserIds as $userId) {
                $channels[] = new PrivateChannel("user.notifications.{$userId}");
            }
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
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $baseData = [
            'event' => $this->eventType,
            'title' => $this->title,
            'message' => $this->message,
            'severity' => $this->severity,
            'timestamp' => now()->toISOString(),
        ];

        // Add event-specific data
        return match ($this->eventType) {
            'security.alert' => array_merge($baseData, [
                'details' => [
                    'ip_address' => $this->metadata['ip_address'] ?? 'Unknown',
                    'user_agent' => $this->metadata['user_agent'] ?? 'Unknown',
                    'affected_user' => $this->metadata['affected_user'] ?? null,
                    'attack_type' => $this->metadata['attack_type'] ?? 'Unknown',
                    'blocked' => $this->metadata['blocked'] ?? false,
                ],
                'actions_taken' => $this->metadata['actions_taken'] ?? [],
                'requires_immediate_action' => $this->severity === 'critical',
            ]),
            'system.maintenance' => array_merge($baseData, [
                'type' => $this->metadata['maintenance_type'] ?? 'scheduled',
                'scheduled_for' => $this->metadata['scheduled_for'] ?? null,
                'estimated_duration' => $this->metadata['estimated_duration'] ?? null,
                'affected_services' => $this->metadata['affected_services'] ?? [],
                'maintenance_window' => $this->metadata['maintenance_window'] ?? null,
            ]),
            'compliance.issues' => array_merge($baseData, [
                'organization_id' => $this->metadata['organization_id'] ?? null,
                'organization_name' => $this->metadata['organization_name'] ?? 'Unknown',
                'issues' => $this->metadata['issues'] ?? [],
                'compliance_score' => $this->metadata['compliance_score'] ?? null,
                'required_actions' => $this->metadata['required_actions'] ?? [],
                'deadline' => $this->metadata['deadline'] ?? null,
            ]),
            'system.backup_completed' => array_merge($baseData, [
                'backup_size' => $this->metadata['backup_size'] ?? null,
                'backup_duration' => $this->metadata['backup_duration'] ?? null,
                'backup_location' => $this->metadata['backup_location'] ?? null,
                'backup_type' => $this->metadata['backup_type'] ?? 'full',
                'files_count' => $this->metadata['files_count'] ?? null,
            ]),
            'system.backup_failed' => array_merge($baseData, [
                'error_message' => $this->metadata['error_message'] ?? 'Unknown error',
                'error_code' => $this->metadata['error_code'] ?? null,
                'retry_count' => $this->metadata['retry_count'] ?? 0,
                'next_attempt' => $this->metadata['next_attempt'] ?? null,
                'backup_type' => $this->metadata['backup_type'] ?? 'full',
            ]),
            'system.performance_alert' => array_merge($baseData, [
                'metric' => $this->metadata['metric'] ?? 'cpu_usage',
                'current_value' => $this->metadata['current_value'] ?? null,
                'threshold' => $this->metadata['threshold'] ?? null,
                'affected_servers' => $this->metadata['affected_servers'] ?? [],
                'duration' => $this->metadata['duration'] ?? null,
                'auto_scaling_triggered' => $this->metadata['auto_scaling_triggered'] ?? false,
            ]),
            default => array_merge($baseData, [
                'metadata' => $this->metadata,
            ]),
        };
    }

    /**
     * The queue connection to use when broadcasting.
     */
    public function broadcastQueue(): string
    {
        // Use high priority queue for critical system events
        return $this->severity === 'critical' ? 'critical-broadcasts' : 'broadcasts';
    }

    /**
     * Determine if the event should be queued for broadcasting.
     */
    public function shouldBroadcast(): bool
    {
        // Always broadcast system events unless in testing
        if (app()->environment('testing')) {
            return false;
        }

        // Don't broadcast low severity events in production unless explicitly enabled
        return ! (app()->environment('production') && $this->severity === 'low' && ! config('broadcasting.include_low_severity', false));
    }

    /**
     * Get the priority of the broadcast.
     */
    public function broadcastPriority(): int
    {
        return match ($this->severity) {
            'critical' => 1,
            'high' => 2,
            'medium' => 3,
            'low' => 4,
            default => 5,
        };
    }
}
