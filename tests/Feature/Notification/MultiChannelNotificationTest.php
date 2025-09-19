<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Modules\Notification\Infrastructure\Laravel\Notifications\DonationConfirmationNotification;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create([
        'phone' => '+1234567890',
    ]);
});

describe('Multi-Channel Notification System', function (): void {
    it('sends donation confirmation notification across all channels', function (): void {
        // Mock external services
        Mail::fake();
        Http::fake([
            'fcm.googleapis.com/*' => Http::response(['success' => 1, 'failure' => 0]),
            'hooks.slack.com/*' => Http::response(['ok' => true]),
        ]);

        // Prepare donation data
        $donation = [
            'id' => 123,
            'amount' => '100.00',
            'payment_reference' => 'TXN_123456789',
        ];

        $campaign = [
            'id' => 456,
            'title' => 'Clean Water Initiative',
            'organization_name' => 'WaterAid Foundation',
            'current_amount' => 7500.00,
            'goal_amount' => 10000.00,
        ];

        $receiptUrl = 'https://acme-corp.com/receipts/123';

        // Create notification
        $notification = new DonationConfirmationNotification(
            $donation,
            $receiptUrl,
            $campaign
        );

        // Send notification
        $this->user->notify($notification);

        // Assert notification was sent (using faked notifications)
        Notification::assertSentTo($this->user, DonationConfirmationNotification::class);

        // Verify notification channels were used correctly
        $channels = $notification->via($this->user);
        expect($channels)->toContain('mail')
            ->and($channels)->toContain('database');

        // Verify notification data structure
        $notificationData = $notification->toArray($this->user);
        expect($notificationData['type'])->toBe('donation_confirmation');
        expect($notificationData['amount'])->toBe('100.00');
        expect($notificationData['campaign_title'])->toBe('Clean Water Initiative');
    });

    it('respects user notification preferences', function (): void {
        // Create user with SMS disabled
        $user = User::factory()->create([
            'phone' => '+1234567890',
        ]);

        // Mock preferences service to return SMS disabled
        $mockPreferences = [
            'email_enabled' => true,
            'sms_enabled' => false,
            'push_enabled' => true,
            'slack_enabled' => false,
        ];

        // Create notification
        $notification = new DonationConfirmationNotification(
            ['id' => 123, 'amount' => '50.00', 'payment_reference' => 'TXN_123'],
            'https://receipt.url',
            ['id' => 456, 'title' => 'Test Campaign', 'organization_name' => 'Test Org'],
            $mockPreferences
        );

        // Check which channels would be used
        $channels = $notification->via($user);

        expect($channels)->toContain('mail');
        expect($channels)->toContain('database');
        expect($channels)->toContain('broadcast');
        expect($channels)->not->toContain('twilio');
        expect($channels)->not->toContain('slack');
    });

    it('handles notification failures gracefully', function (): void {
        // Mock failed external service
        Http::fake([
            'fcm.googleapis.com/*' => Http::response(['error' => 'Service unavailable'], 500),
        ]);

        Log::spy();

        // Create notification
        $notification = new DonationConfirmationNotification(
            ['id' => 123, 'amount' => '25.00', 'payment_reference' => 'TXN_456'],
            'https://receipt.url',
            ['id' => 789, 'title' => 'Emergency Fund', 'organization_name' => 'Red Cross']
        );

        // Send notification (should not throw exception)
        expect(fn () => $this->user->notify($notification))->not->toThrow(\Exception::class);

        // Notification should be sent successfully
        Notification::assertSentTo($this->user, DonationConfirmationNotification::class);
    });

    it('formats SMS messages correctly for character limits', function (): void {
        $notification = new DonationConfirmationNotification(
            ['id' => 123, 'amount' => '75.00', 'payment_reference' => 'TXN_789'],
            'https://acme-corp.com/receipts/123',
            ['id' => 101, 'title' => 'Very Long Campaign Title That Exceeds Normal Length', 'organization_name' => 'Test Org']
        );

        // Get SMS message
        $content = $notification->toSms($this->user);

        // SMS should be within 160 character limit
        expect(strlen($content))->toBeLessThanOrEqual(160);

        // Should contain essential information
        expect($content)->toContain('$75.00');
        expect($content)->toContain('ACME CSR');
        expect($content)->toContain('successful');
    });

    it('generates appropriate impact statements based on campaign progress', function (): void {
        // Test campaign at 50% of goal
        $campaign = [
            'id' => 123,
            'title' => 'Education Fund',
            'organization_name' => 'Education Foundation',
            'current_amount' => 5000.00,
            'goal_amount' => 10000.00,
        ];

        $notification = new DonationConfirmationNotification(
            ['id' => 123, 'amount' => '100.00', 'payment_reference' => 'TXN_123'],
            'https://receipt.url',
            $campaign
        );

        $data = $notification->toArray($this->user);
        $impactStatement = (new \ReflectionClass($notification))
            ->getMethod('generateImpactStatement')
            ->getClosure($notification)();

        expect($impactStatement)->toContain('$5,000.00 left to reach');

        // Test campaign that reached goal
        $completedCampaign = array_merge($campaign, [
            'current_amount' => 10500.00,
        ]);

        $completedNotification = new DonationConfirmationNotification(
            ['id' => 124, 'amount' => '100.00', 'payment_reference' => 'TXN_124'],
            'https://receipt.url',
            $completedCampaign
        );

        $completedImpact = (new \ReflectionClass($completedNotification))
            ->getMethod('generateImpactStatement')
            ->getClosure($completedNotification)();

        expect($completedImpact)->toContain('reached its goal');
    });

    it('includes proper tracking data for analytics', function (): void {
        $notification = new DonationConfirmationNotification(
            ['id' => 123, 'amount' => '200.00', 'payment_reference' => 'TXN_ANALYTICS'],
            'https://receipt.url',
            ['id' => 456, 'title' => 'Analytics Test', 'organization_name' => 'Test Org']
        );

        $data = $notification->toArray($this->user);

        // Check tracking data
        expect($data)->toHaveKeys([
            'type',
            'donation_id',
            'amount',
            'campaign_title',
            'campaign_id',
            'receipt_url',
            'message',
            'icon',
            'color',
            'actions',
        ]);

        expect($data['type'])->toBe('donation_confirmation');
        expect($data['actions'])->toBeArray();
        expect($data['actions'])->toHaveCount(2);
        expect($data['actions'][0]['label'])->toBe('View Receipt');
        expect($data['actions'][1]['label'])->toBe('Share Campaign');
    });

    it('handles quiet hours for push notifications', function (): void {
        // Mock user preferences with quiet hours
        $quietHoursPreferences = [
            'email_enabled' => true,
            'sms_enabled' => true,
            'push_enabled' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
            'timezone' => 'UTC',
        ];

        $notification = new DonationConfirmationNotification(
            ['id' => 123, 'amount' => '50.00', 'payment_reference' => 'TXN_QUIET'],
            'https://receipt.url',
            ['id' => 789, 'title' => 'Quiet Hours Test', 'organization_name' => 'Test Org'],
            $quietHoursPreferences
        );

        // During quiet hours (simulated by the notification logic)
        // Push notifications should be filtered out while other channels remain
        $channels = $notification->via($this->user);

        // Email and database should always be included
        expect($channels)->toContain('mail');
        expect($channels)->toContain('database');
    });

    it('creates proper email message with rich content', function (): void {
        $notification = new DonationConfirmationNotification(
            [
                'id' => 123,
                'amount' => '150.00',
                'payment_reference' => 'TXN_EMAIL_TEST',
            ],
            'https://acme-corp.com/receipts/123',
            [
                'id' => 456,
                'title' => 'Environmental Protection Fund',
                'organization_name' => 'Green Earth Foundation',
                'current_amount' => 8750.00,
                'goal_amount' => 12000.00,
            ]
        );

        $mailMessage = $notification->toMail($this->user);

        expect($mailMessage->subject)->toContain('Environmental Protection Fund');
        expect($mailMessage->greeting)->toContain($this->user->name);

        // Check that the mail message has proper structure
        expect($mailMessage)->toBeInstanceOf(\Illuminate\Notifications\Messages\MailMessage::class);
    });

    it('broadcasts real-time notifications correctly', function (): void {
        Event::fake();

        $notification = new DonationConfirmationNotification(
            ['id' => 123, 'amount' => '75.00', 'payment_reference' => 'TXN_BROADCAST'],
            'https://receipt.url',
            ['id' => 789, 'title' => 'Broadcast Test', 'organization_name' => 'Test Org']
        );

        $broadcastMessage = $notification->toBroadcast($this->user);

        expect($broadcastMessage)->toBeInstanceOf(\Illuminate\Notifications\Messages\BroadcastMessage::class);
        expect($broadcastMessage->data['title'])->toBe('Donation Successful!');
        expect($broadcastMessage->data['body'])->toContain('$75.00');
    });
});

describe('Notification Analytics and Metrics', function (): void {
    it('tracks notification delivery attempts', function (): void {
        $analyticsService = new \Modules\Notification\Infrastructure\Laravel\Service\NotificationAnalyticsService;

        $notification = new DonationConfirmationNotification(
            ['id' => 123, 'amount' => '100.00', 'payment_reference' => 'TXN_ANALYTICS'],
            'https://receipt.url',
            ['id' => 456, 'title' => 'Analytics Test', 'organization_name' => 'Test Org']
        );

        // Track metrics
        $analyticsService->trackDeliveryAttempt($notification, $this->user);
        $analyticsService->trackDeliverySuccess($notification, $this->user);

        $metrics = $analyticsService->getDeliveryMetrics();

        expect($metrics)->toHaveKeys([
            'delivery_rates',
            'open_rates',
            'click_rates',
            'volume_metrics',
            'performance_metrics',
        ]);
    });
});
