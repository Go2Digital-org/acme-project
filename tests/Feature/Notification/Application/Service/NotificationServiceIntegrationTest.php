<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notification\Domain\Model\Notification;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

describe('Notification Service Integration', function (): void {
    it('creates notification and stores in database', function (): void {
        $notification = Notification::factory()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'type' => 'campaign.created',
            'data' => [
                'title' => 'Test Notification',
                'message' => 'This is a test notification',
            ],
        ]);

        expect($notification)->toBeInstanceOf(Notification::class)
            ->and($notification->data['title'])->toBe('Test Notification')
            ->and($notification->notifiable_id)->toBe($this->user->id);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'notifiable_id' => $this->user->id,
            'type' => 'campaign.created',
        ]);
    });

    it('creates notification with relationships loaded', function (): void {
        $notification = Notification::factory()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'type' => 'donation.received',
            'data' => [
                'title' => 'Relationship Test',
                'message' => 'Testing relationships',
            ],
        ]);

        $loadedNotification = Notification::with('notifiable')->find($notification->id);

        expect($loadedNotification->notifiable)->not->toBeNull()
            ->and($loadedNotification->notifiable->id)->toBe($this->user->id);
    });

    it('persists complex notification data correctly', function (): void {
        $complexData = [
            'title' => 'Complex Data Test',
            'message' => 'Testing complex data persistence',
            'campaign_id' => 123,
            'milestone_percentage' => 75,
            'current_amount' => 15000.50,
            'nested' => [
                'deep' => [
                    'value' => 'test',
                ],
            ],
        ];

        $notification = Notification::factory()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'type' => 'campaign.milestone',
            'data' => $complexData,
        ]);

        $reloaded = Notification::find($notification->id);

        expect($reloaded->data['campaign_id'])->toBe(123)
            ->and($reloaded->data['milestone_percentage'])->toBe(75)
            ->and($reloaded->data['current_amount'])->toBe(15000.5)
            ->and($reloaded->data['nested']['deep']['value'])->toBe('test');
    });

    it('handles timestamps correctly', function (): void {
        $notification = Notification::factory()->create([
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'type' => 'system.maintenance',
            'data' => [
                'title' => 'Timestamp Test',
                'message' => 'Testing timestamp handling',
            ],
            'read_at' => null,
        ]);

        expect($notification->read_at)->toBeNull()
            ->and($notification->created_at)->not->toBeNull()
            ->and($notification->updated_at)->not->toBeNull();
    });
});
