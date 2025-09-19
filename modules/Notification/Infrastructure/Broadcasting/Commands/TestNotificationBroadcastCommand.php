<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Broadcasting\Commands;

use Exception;
use Illuminate\Console\Command;
use Modules\Notification\Infrastructure\Broadcasting\Service\NotificationBroadcaster;

/**
 * Command to test notification broadcasting functionality.
 *
 * This command allows testing of WebSocket broadcasting without
 * requiring actual domain events to occur.
 */
class TestNotificationBroadcastCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:test-broadcast 
                            {--type=system : Type of test notification (system, donation, campaign)}
                            {--severity=medium : Severity level for system notifications}
                            {--user= : Target specific user ID}
                            {--count=1 : Number of test notifications to send}';

    /**
     * The console command description.
     */
    protected $description = 'Test notification broadcasting functionality';

    public function __construct(
        private readonly NotificationBroadcaster $broadcaster,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $count = (int) $this->option('count');

        $this->info("Testing {$type} notification broadcasting...");

        for ($i = 1; $i <= $count; $i++) {
            try {
                match ($type) {
                    'system' => $this->testSystemNotification($i),
                    'donation' => $this->testDonationNotification($i),
                    'campaign' => $this->testCampaignNotification($i),
                    'security' => $this->testSecurityNotification($i),
                    'maintenance' => $this->testMaintenanceNotification($i),
                    default => $this->testSystemNotification($i),
                };

                $this->line("✓ Test notification #{$i} broadcasted");

                if ($count > 1 && $i < $count) {
                    sleep(1); // Avoid overwhelming
                }
            } catch (Exception $e) {
                $this->error("✗ Failed to broadcast test notification #{$i}: " . $e->getMessage());

                return self::FAILURE;
            }
        }

        $this->info("\n🎉 Successfully broadcasted {$count} test notification(s)!");
        $this->line('Check your admin dashboard or browser console to see the notifications.');

        return self::SUCCESS;
    }

    /**
     * Test system notification broadcasting.
     */
    private function testSystemNotification(int $number): void
    {
        $severity = $this->option('severity');
        $userId = $this->option('user');

        $this->broadcaster->broadcastSystemNotification(
            'system.test',
            "Test System Notification #{$number}",
            'This is a test system notification to verify broadcasting is working correctly.',
            $severity ?? 'info',
            [
                'test_number' => $number,
                'timestamp' => now()->toISOString(),
                'triggered_by' => 'artisan_command',
            ],
            $userId ? [(string) $userId] : null,
        );
    }

    /**
     * Test donation notification broadcasting.
     */
    private function testDonationNotification(int $number): void
    {
        // Create mock donation for testing
        $donationData = [
            'id' => "test-donation-{$number}",
            'amount' => mt_rand(100, 5000),
            'donor_name' => 'Test Donor ' . $number,
            'donor_email' => "test{$number}@example.com",
            'campaign_id' => 'test-campaign-1',
            'campaign_title' => 'Test Campaign for Broadcasting',
            'status' => 'completed',
            'created_at' => now()->toISOString(),
        ];

        $this->broadcaster->broadcastSystemNotification(
            'donation.large',
            'Large Test Donation Received!',
            'Test donor donated $' . number_format($donationData['amount']) . " to {$donationData['campaign_title']}",
            'high',
            $donationData,
        );
    }

    /**
     * Test campaign notification broadcasting.
     */
    private function testCampaignNotification(int $number): void
    {
        $milestones = ['25', '50', '75', '100'];
        $milestone = $milestones[($number - 1) % count($milestones)];

        $campaignData = [
            'campaign_id' => "test-campaign-{$number}",
            'campaign_title' => "Test Campaign #{$number}",
            'milestone' => $milestone,
            'progress_percentage' => (int) $milestone,
            'goal_amount' => 10000,
            'current_amount' => (10000 * (int) $milestone) / 100,
            'organization_name' => 'Test Organization',
        ];

        $this->broadcaster->broadcastSystemNotification(
            'campaign.milestone',
            'Campaign Milestone Reached!',
            "{$campaignData['campaign_title']} reached {$milestone}% of its goal!",
            'medium',
            $campaignData,
        );
    }

    /**
     * Test security alert broadcasting.
     */
    private function testSecurityNotification(int $number): void
    {
        $alertTypes = ['login_attempt', 'data_breach', 'unauthorized_access', 'suspicious_activity'];
        $alertType = $alertTypes[($number - 1) % count($alertTypes)];

        $this->broadcaster->broadcastSecurityAlert(
            $alertType,
            "Test security alert #{$number}: Suspicious {$alertType} detected.",
            'high',
            [
                'test_number' => $number,
                'ip_address' => '192.168.1.' . mt_rand(1, 254),
                'user_agent' => 'Test User Agent',
                'blocked' => true,
            ],
        );
    }

    /**
     * Test maintenance notification broadcasting.
     */
    private function testMaintenanceNotification(int $number): void
    {
        $maintenanceTypes = ['scheduled', 'emergency', 'security_update', 'performance'];
        $maintenanceType = $maintenanceTypes[($number - 1) % count($maintenanceTypes)];

        $scheduledFor = now()->addHours(mt_rand(1, 24));

        $this->broadcaster->broadcastMaintenanceNotification(
            $maintenanceType,
            $scheduledFor,
            mt_rand(30, 180), // 30 minutes to 3 hours
            ['api', 'dashboard', 'notifications'],
        );
    }
}
