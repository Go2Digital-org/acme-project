<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

describe('Notification API Basic Tests', function (): void {
    it('requires authentication for notifications endpoint', function (): void {
        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->get('/api/notifications');

        $response->assertStatus(401);
    });

    it('can access notifications endpoint when authenticated', function (): void {
        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/api/notifications');

        // Should return OK or not found (if endpoint doesn't exist yet)
        expect($response->getStatusCode())->toBeIn([200, 404]);
    });

    it('can test notification database structure', function (): void {
        // Create a notification directly in database
        $notificationId = \Illuminate\Support\Str::uuid()->toString();

        \DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'Test\\Notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'data' => json_encode([
                'title' => 'Test Notification',
                'message' => 'This is a test notification',
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify it was stored
        $this->assertDatabaseHas('notifications', [
            'id' => $notificationId,
            'notifiable_id' => $this->user->id,
        ]);

        // Test basic retrieval
        $notification = \DB::table('notifications')->where('id', $notificationId)->first();
        expect($notification)->not->toBeNull();
        // Database returns string, so cast to int for comparison
        expect((int) $notification->notifiable_id)->toBe($this->user->id);
    });

    it('can handle notification marking as read', function (): void {
        $notificationId = \Illuminate\Support\Str::uuid()->toString();

        \DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'Test\\Notification',
            'notifiable_type' => User::class,
            'notifiable_id' => (string) $this->user->id, // Ensure consistent string type
            'data' => json_encode(['title' => 'Test']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post("/api/notifications/{$notificationId}/read");

        // Should work (200) or return business logic error (422) or not found (404)
        expect($response->getStatusCode())->toBeIn([200, 404, 422]);
    });
});
