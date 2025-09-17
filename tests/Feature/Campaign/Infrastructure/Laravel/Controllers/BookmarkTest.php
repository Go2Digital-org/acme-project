<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Campaign\Domain\Model\Bookmark;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('Bookmark API', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();

        // Create Sanctum token for API authentication
        $this->user->createToken('test-token');

        // Create category for campaign
        $this->category = \Modules\Category\Domain\Model\Category::factory()->create();

        $this->campaign = Campaign::factory()
            ->for($this->organization)
            ->for($this->user, 'employee')
            ->create([
                'category_id' => $this->category->id,
            ]);
    });

    describe('POST /api/campaigns/{id}/bookmark', function (): void {
        it('bookmarks campaign successfully', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'])
                ->postJson("/api/campaigns/{$this->campaign->id}/bookmark");

            $response->assertOk()
                ->assertJson(
                    fn (AssertableJson $json) => $json->where('campaignId', $this->campaign->id)
                        ->where('bookmarked', true)
                        ->where('bookmarkCount', 1)
                        ->has('message')
                        ->etc(),
                );

            $this->assertDatabaseHas('bookmarks', [
                'campaign_id' => $this->campaign->id,
                'user_id' => $this->user->id,
            ]);
        });

        it('unbookmarks already bookmarked campaign', function (): void {
            // First, bookmark the campaign
            Bookmark::factory()
                ->for($this->campaign)
                ->for($this->user)
                ->create();

            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'])
                ->postJson("/api/campaigns/{$this->campaign->id}/bookmark");

            $response->assertOk()
                ->assertJson(
                    fn (AssertableJson $json) => $json->where('campaignId', $this->campaign->id)
                        ->where('bookmarked', false)
                        ->where('bookmarkCount', 0)
                        ->has('message')
                        ->etc(),
                );

            $this->assertDatabaseMissing('bookmarks', [
                'campaign_id' => $this->campaign->id,
                'user_id' => $this->user->id,
            ]);
        });

        it('requires authentication', function (): void {
            $response = $this->withHeaders(['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'])
                ->post("/api/campaigns/{$this->campaign->id}/bookmark", []);

            $response->assertStatus(401);
        });

    });

    describe('DELETE /api/campaigns/{id}/bookmark', function (): void {
        it('removes bookmark successfully', function (): void {
            Bookmark::factory()
                ->for($this->campaign)
                ->for($this->user)
                ->create();

            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'])
                ->delete("/api/campaigns/{$this->campaign->id}/bookmark");

            $response->assertOk()
                ->assertJson(
                    fn (AssertableJson $json) => $json->where('campaignId', $this->campaign->id)
                        ->where('bookmarked', false)
                        ->where('bookmarkCount', 0)
                        ->has('message')
                        ->etc(),
                );

            $this->assertDatabaseMissing('bookmarks', [
                'campaign_id' => $this->campaign->id,
                'user_id' => $this->user->id,
            ]);
        });

        it('requires authentication', function (): void {
            $response = $this->withHeaders(['Accept' => 'application/ld+json'])
                ->delete("/api/campaigns/{$this->campaign->id}/bookmark");

            $response->assertStatus(401);
        });

        it('returns 404 for non-existent campaigns', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
                ->delete('/api/campaigns/999999/bookmark');

            $response->assertNotFound();
        });
    });

    describe('GET /api/campaigns/{id}/bookmark/status', function (): void {
        it('returns bookmark status for bookmarked campaign', function (): void {
            Bookmark::factory()
                ->for($this->campaign)
                ->for($this->user)
                ->create();

            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'])
                ->get("/api/campaigns/{$this->campaign->id}/bookmark/status");

            $response->assertOk()
                ->assertJson(
                    fn (AssertableJson $json) => $json->where('campaignId', $this->campaign->id)
                        ->where('bookmarked', true)
                        ->where('bookmarkCount', 1)
                        ->etc(),
                );
        });

        it('returns bookmark status for non-bookmarked campaign', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'])
                ->get("/api/campaigns/{$this->campaign->id}/bookmark/status");

            $response->assertOk()
                ->assertJson(
                    fn (AssertableJson $json) => $json->where('campaignId', $this->campaign->id)
                        ->where('bookmarked', false)
                        ->where('bookmarkCount', 0)
                        ->etc(),
                );
        });

        it('shows correct bookmark count with multiple users', function (): void {
            $otherUsers = User::factory()->count(3)->create();

            foreach ($otherUsers as $user) {
                Bookmark::factory()
                    ->for($this->campaign)
                    ->for($user)
                    ->create();
            }

            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/ld+json', 'Content-Type' => 'application/ld+json'])
                ->get("/api/campaigns/{$this->campaign->id}/bookmark/status");

            $response->assertOk()
                ->assertJson(
                    fn (AssertableJson $json) => $json->where('campaignId', $this->campaign->id)
                        ->where('bookmarked', false)
                        ->where('bookmarkCount', 3)
                        ->etc(),
                );
        });

        it('requires authentication', function (): void {
            $response = $this->withHeaders(['Accept' => 'application/ld+json'])
                ->get("/api/campaigns/{$this->campaign->id}/bookmark/status");

            $response->assertStatus(401);
        });

        it('returns 404 for non-existent campaigns', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
                ->get('/api/campaigns/999999/bookmark/status');

            $response->assertNotFound();
        });
    });

});
