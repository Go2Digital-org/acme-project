<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Broadcasting\Service;

use DateTime;
use Exception;
use Illuminate\Support\Facades\Event;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Modules\Notification\Domain\Model\Notification;
use Modules\Notification\Infrastructure\Broadcasting\Events\CampaignNotificationBroadcast;
use Modules\Notification\Infrastructure\Broadcasting\Events\DonationNotificationBroadcast;
use Modules\Notification\Infrastructure\Broadcasting\Events\NotificationBroadcast;
use Modules\Notification\Infrastructure\Broadcasting\Events\SystemNotificationBroadcast;
use Psr\Log\LoggerInterface;

/**
 * Service responsible for broadcasting notifications through WebSocket channels.
 *
 * This service acts as the central hub for broadcasting different types of notifications
 * to the appropriate channels and users in real-time.
 */
class NotificationBroadcaster
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Broadcast a generic notification.
     */
    public function broadcastNotification(Notification $notification): void
    {
        try {
            Event::dispatch(new NotificationBroadcast($notification));

            $this->logger->info('Notification broadcasted successfully', [
                'notification_id' => $notification->id,
                'type' => $notification->type,
                'notifiable_id' => $notification->notifiable_id,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to broadcast notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Broadcast a notification creation event with additional context.
     *
     * @param  array<string, mixed>  $context
     */
    public function broadcastNotificationCreated(Notification $notification, array $context = []): void
    {
        try {
            Event::dispatch(new NotificationBroadcast($notification));

            $this->logger->info('Notification created and broadcasted successfully', [
                'notification_id' => $notification->id,
                'type' => $notification->type,
                'notifiable_id' => $notification->notifiable_id,
                'context' => $context,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to broadcast notification creation', [
                'notification_id' => $notification->id,
                'context' => $context,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Broadcast a donation-related notification.
     *
     * @param  array<string, mixed>  $additionalData
     */
    public function broadcastDonationNotification(
        Donation $donation,
        string $eventType,
        array $additionalData = [],
    ): void {
        try {
            Event::dispatch(new DonationNotificationBroadcast($donation, $eventType, $additionalData));

            $this->logger->info('Donation notification broadcasted successfully', [
                'donation_id' => $donation->id,
                'event_type' => $eventType,
                'amount' => $donation->amount,
                'campaign_id' => $donation->campaign_id,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to broadcast donation notification', [
                'donation_id' => $donation->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Broadcast a campaign-related notification.
     *
     * @param  array<string, mixed>  $additionalData
     */
    public function broadcastCampaignNotification(
        Campaign $campaign,
        string $eventType,
        array $additionalData = [],
    ): void {
        try {
            Event::dispatch(new CampaignNotificationBroadcast($campaign, $eventType, $additionalData));

            $this->logger->info('Campaign notification broadcasted successfully', [
                'campaign_id' => $campaign->id,
                'event_type' => $eventType,
                'campaign_title' => $campaign->title,
                'organization_id' => $campaign->organization_id,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to broadcast campaign notification', [
                'campaign_id' => $campaign->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Broadcast a system notification.
     *
     * @param  array<string, mixed>  $metadata
     * @param  array<int, int>|null  $targetUserIds
     */
    public function broadcastSystemNotification(
        string $eventType,
        string $title,
        string $message,
        string $severity = 'medium',
        array $metadata = [],
        ?array $targetUserIds = null,
    ): void {
        try {
            Event::dispatch(new SystemNotificationBroadcast(
                $eventType,
                $title,
                $message,
                $severity,
                $metadata,
                $targetUserIds,
            ));

            $this->logger->info('System notification broadcasted successfully', [
                'event_type' => $eventType,
                'severity' => $severity,
                'target_users' => $targetUserIds ? count($targetUserIds) : 'all_admins',
            ]);
        } catch (Exception $e) {
            $this->logger->error('Failed to broadcast system notification', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Broadcast a large donation alert to admin dashboard.
     */
    public function broadcastLargeDonationAlert(Donation $donation, float $threshold = 1000): void
    {
        $this->broadcastDonationNotification($donation, 'donation.large', [
            'threshold_amount' => $threshold,
        ]);
    }

    /**
     * Broadcast a campaign milestone achievement.
     */
    public function broadcastCampaignMilestone(
        Campaign $campaign,
        string $milestone,
        ?float $milestoneAmount = null,
    ): void {
        $this->broadcastCampaignNotification($campaign, 'campaign.milestone', [
            'milestone' => $milestone,
            'milestone_amount' => $milestoneAmount ?? $campaign->current_amount,
            'progress_percentage' => $campaign->getProgressPercentage(),
            'donors_count' => $campaign->donations_count ?? 0,
        ]);
    }

    /**
     * Broadcast campaign approval needed alert.
     */
    public function broadcastCampaignApprovalNeeded(Campaign $campaign): void
    {
        $this->broadcastCampaignNotification($campaign, 'campaign.approval_needed', [
            'submitted_at' => now()->toISOString(),
            'pending_since_hours' => $campaign->created_at?->diffInHours(now()) ?? 0,
        ]);
    }

    /**
     * Broadcast payment failure alert.
     */
    public function broadcastPaymentFailure(
        Donation $donation,
        string $reason,
        ?string $errorCode = null,
        int $retryCount = 0,
    ): void {
        $this->broadcastDonationNotification($donation, 'payment.failed', [
            'failure_reason' => $reason,
            'error_code' => $errorCode,
            'retry_count' => $retryCount,
            'next_retry_at' => $retryCount < 3 ? now()->addMinutes(5)->toISOString() : null,
        ]);
    }

    /**
     * Broadcast security alert to administrators.
     *
     * @param  array<string, mixed>  $details
     */
    public function broadcastSecurityAlert(
        string $alertType,
        string $message,
        string $severity = 'high',
        array $details = [],
    ): void {
        $this->broadcastSystemNotification(
            'security.alert',
            'Security Alert: ' . ucfirst($alertType),
            $message,
            $severity,
            array_merge($details, ['attack_type' => $alertType]),
        );
    }

    /**
     * Broadcast system maintenance notification.
     *
     * @param  array<int, string>  $affectedServices
     */
    public function broadcastMaintenanceNotification(
        string $maintenanceType,
        DateTime $scheduledFor,
        int $estimatedDurationMinutes,
        array $affectedServices = [],
    ): void {
        $this->broadcastSystemNotification(
            'system.maintenance',
            'System Maintenance Scheduled',
            "A {$maintenanceType} maintenance is scheduled for " . $scheduledFor->format('Y-m-d H:i:s'),
            'medium',
            [
                'maintenance_type' => $maintenanceType,
                'scheduled_for' => $scheduledFor->format('c'),
                'estimated_duration' => $estimatedDurationMinutes . ' minutes',
                'affected_services' => $affectedServices,
            ],
        );
    }

    /**
     * Broadcast compliance issues alert.
     *
     * @param  array<int, string>  $issues
     */
    public function broadcastComplianceAlert(
        int $organizationId,
        string $organizationName,
        array $issues,
        string $severity = 'high',
    ): void {
        $this->broadcastSystemNotification(
            'compliance.issues',
            'Compliance Issues Detected',
            "Organization '{$organizationName}' has compliance issues that require attention.",
            $severity,
            [
                'organization_id' => $organizationId,
                'organization_name' => $organizationName,
                'issues' => $issues,
                'issue_count' => count($issues),
            ],
        );
    }

    /**
     * Broadcast multiple notifications in batch.
     *
     * @param  array<int, Notification>  $notifications
     */
    public function broadcastBatch(array $notifications): void
    {
        foreach ($notifications as $notification) {
            if ($notification instanceof Notification) {
                $this->broadcastNotification($notification);
            }
        }
    }

    /**
     * Test the broadcasting connection.
     */
    public function testBroadcast(): void
    {
        $this->broadcastSystemNotification(
            'system.test',
            'Broadcast Test',
            'This is a test notification to verify broadcasting is working correctly.',
            'low',
            ['test_timestamp' => now()->toISOString()],
        );
    }
}
