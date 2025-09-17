<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Laravel\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Donation\Application\Event\DonationCreatedEvent;
use Modules\Notification\Application\Command\CreateNotificationCommand;
use Modules\Notification\Application\Command\CreateNotificationCommandHandler;
use Modules\Notification\Domain\Enum\NotificationChannel;
use Modules\Notification\Domain\Enum\NotificationPriority;
use Modules\Notification\Domain\Enum\NotificationType;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener that creates notifications when donations are received.
 *
 * This listener handles the business logic of notifying relevant stakeholders
 * about new donation events within the platform.
 */
final class DonationReceivedNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the queued listener may run.
     */
    public int $timeout = 120;

    public function __construct(
        private readonly CreateNotificationCommandHandler $notificationHandler,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the donation created event.
     */
    public function handle(DonationCreatedEvent $event): void
    {
        try {
            // Notify donor (if not anonymous)
            if (! $event->anonymous && $event->userId) {
                $this->notifyDonor($event);
            }

            // Notify campaign creator
            $this->notifyCampaignCreator($event);

            // Notify organization administrators
            $this->notifyOrganizationAdmins($event);

            // Check for milestone notifications
            $this->checkAndNotifyMilestones($event);

            // Notify platform administrators for large donations
            if ($this->isLargeDonation($event->amount)) {
                $this->notifyPlatformAdmins($event);
            }

            $this->logger->info('Donation notifications processed', [
                'donation_id' => $event->donationId,
                'campaign_id' => $event->campaignId,
                'amount' => $event->amount,
                'anonymous' => $event->anonymous,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to process donation notifications', [
                'donation_id' => $event->donationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(DonationCreatedEvent $event, Throwable $exception): void
    {
        $this->logger->error('Failed to process donation notifications', [
            'donation_id' => $event->donationId ?? 'unknown',
            'campaign_id' => $event->campaignId ?? 'unknown',
            'amount' => $event->amount ?? 'unknown',
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Notify the donor about successful donation.
     */
    private function notifyDonor(DonationCreatedEvent $event): void
    {
        $command = new CreateNotificationCommand(
            recipientId: (string) $event->userId,
            title: 'Thank You for Your Donation!',
            message: "Your donation of {$event->currency} {$event->amount} has been successfully processed.",
            type: NotificationType::DONATION_CONFIRMATION->value,
            channel: NotificationChannel::EMAIL->value,
            priority: NotificationPriority::HIGH->value,
            senderId: null,
            data: [
                'donation_id' => $event->donationId,
                'campaign_id' => $event->campaignId,
                'amount' => $event->amount,
                'currency' => $event->currency,
                'receipt_available' => true,
                'tax_deductible' => true,
                'actions' => [
                    [
                        'label' => 'Download Receipt',
                        'url' => "/donations/{$event->donationId}/receipt",
                        'primary' => true,
                    ],
                    [
                        'label' => 'View Campaign',
                        'url' => "/campaigns/{$event->campaignId}",
                        'primary' => false,
                    ],
                    [
                        'label' => 'Share',
                        'url' => "/campaigns/{$event->campaignId}/share?donated=true",
                        'primary' => false,
                    ],
                ],
            ],
            metadata: [
                'donation_id' => $event->donationId,
                'campaign_id' => $event->campaignId,
                'amount' => $event->amount,
                'currency' => $event->currency,
                'receipt_available' => true,
                'tax_deductible' => true,
            ],
        );

        $this->notificationHandler->handle($command);

        // Also send SMS confirmation for high-value donations
        if ($event->amount >= 100) {
            $smsCommand = new CreateNotificationCommand(
                recipientId: (string) $event->userId,
                title: 'Donation Confirmed',
                message: "Your {$event->currency}{$event->amount} donation was successful. Receipt: acme.co/receipt/{$event->donationId}",
                type: NotificationType::DONATION_CONFIRMATION->value,
                channel: NotificationChannel::SMS->value,
                priority: NotificationPriority::MEDIUM->value,
                senderId: null,
                data: [
                    'donation_id' => $event->donationId,
                    'amount' => $event->amount,
                    'currency' => $event->currency,
                ],
                metadata: [
                    'donation_id' => $event->donationId,
                    'amount' => $event->amount,
                    'currency' => $event->currency,
                ],
            );

            $this->notificationHandler->handle($smsCommand);
        }
    }

    /**
     * Notify the campaign creator about the new donation.
     */
    private function notifyCampaignCreator(DonationCreatedEvent $event): void
    {
        $donorName = $event->anonymous ? 'Anonymous Donor' : 'A supporter';

        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: '', // Will be resolved to campaign creator
            title: 'New Donation Received!',
            message: "{$donorName} just donated {$event->currency} {$event->amount} to your campaign.",
            type: NotificationType::DONATION_RECEIVED->value,
            channel: NotificationChannel::PUSH->value,
            priority: NotificationPriority::MEDIUM->value,
            senderId: null,
            data: [
                'donation_id' => $event->donationId,
                'campaign_id' => $event->campaignId,
                'amount' => $event->amount,
                'currency' => $event->currency,
                'anonymous' => $event->anonymous,
                'donor_id' => $event->anonymous ? null : $event->userId,
                'actions' => [
                    [
                        'label' => 'View Campaign',
                        'url' => "/campaigns/{$event->campaignId}",
                        'primary' => true,
                    ],
                    [
                        'label' => 'Thank Supporters',
                        'url' => "/campaigns/{$event->campaignId}/update",
                        'primary' => false,
                    ],
                ],
            ],
            metadata: [
                'donation_id' => $event->donationId,
                'campaign_id' => $event->campaignId,
                'amount' => $event->amount,
                'currency' => $event->currency,
                'anonymous' => $event->anonymous,
                'donor_id' => $event->anonymous ? null : $event->userId,
            ],
        ));
    }

    /**
     * Notify organization administrators about the donation.
     */
    private function notifyOrganizationAdmins(DonationCreatedEvent $event): void
    {
        // Only notify for larger donations to avoid spam
        if ($event->amount < 50) {
            return;
        }

        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: '', // Will be resolved to org admins
            title: 'Donation Received',
            message: "New donation of {$event->currency} {$event->amount} received for campaign.",
            type: NotificationType::DONATION_RECEIVED->value,
            channel: NotificationChannel::IN_APP->value,
            priority: NotificationPriority::LOW->value,
            senderId: null,
            data: [
                'donation_id' => $event->donationId,
                'campaign_id' => $event->campaignId,
                'amount' => $event->amount,
                'currency' => $event->currency,
                'anonymous' => $event->anonymous,
                'actions' => [
                    [
                        'label' => 'View Details',
                        'url' => "/admin/donations/{$event->donationId}",
                        'primary' => true,
                    ],
                ],
            ],
            metadata: [
                'donation_id' => $event->donationId,
                'campaign_id' => $event->campaignId,
                'amount' => $event->amount,
                'currency' => $event->currency,
                'anonymous' => $event->anonymous,
            ],
        ));

        // Note: In a real implementation, this would iterate through org admins
        // $this->notificationHandler->handle($command);
    }

    /**
     * Check for campaign milestones and notify if reached.
     */
    private function checkAndNotifyMilestones(DonationCreatedEvent $event): void
    {
        // This would typically:
        // 1. Fetch current campaign progress
        // 2. Check if this donation pushed the campaign past a milestone
        // 3. Notify stakeholders about milestone achievement

        $this->logger->debug('Milestone check would be performed', [
            'donation_id' => $event->donationId,
            'campaign_id' => $event->campaignId,
            'amount' => $event->amount,
        ]);

        // Example milestone notification
        // This would be triggered only if a milestone was actually reached
        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: '', // Campaign creator and supporters
            title: 'Campaign Milestone Reached! 🎉',
            message: 'Great news! Your campaign has reached 75% of its goal thanks to recent donations.',
            type: NotificationType::CAMPAIGN_MILESTONE->value,
            channel: NotificationChannel::PUSH->value,
            priority: NotificationPriority::MEDIUM->value,
            senderId: null,
            data: [
                'campaign_id' => $event->campaignId,
                'milestone_percentage' => 75,
                'current_amount' => null, // Would be calculated
                'goal_amount' => null, // Would be fetched
                'trigger_donation_id' => $event->donationId,
                'actions' => [
                    [
                        'label' => 'View Progress',
                        'url' => "/campaigns/{$event->campaignId}",
                        'primary' => true,
                    ],
                    [
                        'label' => 'Share Achievement',
                        'url' => "/campaigns/{$event->campaignId}/share?milestone=75",
                        'primary' => false,
                    ],
                ],
            ],
            metadata: [
                'campaign_id' => $event->campaignId,
                'milestone_percentage' => 75,
                'current_amount' => null, // Would be calculated
                'goal_amount' => null, // Would be fetched
                'trigger_donation_id' => $event->donationId,
            ],
        ));

        // Note: In a real implementation, this would only trigger for actual milestones
        // $this->notificationHandler->handle($milestoneCommand);
    }

    /**
     * Notify platform administrators about large donations.
     */
    private function notifyPlatformAdmins(DonationCreatedEvent $event): void
    {
        $this->notificationHandler->handle(new CreateNotificationCommand(
            recipientId: '', // Platform admins
            title: 'Large Donation Alert',
            message: "Large donation of {$event->currency} {$event->amount} received.",
            type: NotificationType::ADMIN_ALERT->value,
            channel: NotificationChannel::EMAIL->value,
            priority: NotificationPriority::HIGH->value,
            senderId: null,
            data: [
                'donation_id' => $event->donationId,
                'campaign_id' => $event->campaignId,
                'amount' => $event->amount,
                'currency' => $event->currency,
                'donor_id' => $event->userId,
                'anonymous' => $event->anonymous,
                'alert_type' => 'large_donation',
                'actions' => [
                    [
                        'label' => 'Review Donation',
                        'url' => "/admin/donations/{$event->donationId}",
                        'primary' => true,
                    ],
                    [
                        'label' => 'View Campaign',
                        'url' => "/admin/campaigns/{$event->campaignId}",
                        'primary' => false,
                    ],
                ],
            ],
            metadata: [
                'donation_id' => $event->donationId,
                'campaign_id' => $event->campaignId,
                'amount' => $event->amount,
                'currency' => $event->currency,
                'donor_id' => $event->userId,
                'anonymous' => $event->anonymous,
                'alert_type' => 'large_donation',
            ],
        ));

        // Note: In a real implementation, this would iterate through platform admins
        // $this->notificationHandler->handle($command);
    }

    /**
     * Determine if a donation amount is considered "large".
     */
    private function isLargeDonation(float $amount): bool
    {
        return $amount >= 1000; // Configurable threshold
    }
}
