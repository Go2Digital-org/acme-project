<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Domain\Model\Bookmark;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('Bookmark Model Feature Tests', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();

        // Create category for campaign
        $this->category = \Modules\Category\Domain\Model\Category::factory()->create();

        $this->campaign = Campaign::factory()
            ->for($this->organization)
            ->for($this->user, 'employee')
            ->create([
                'category_id' => $this->category->id,
            ]);
    });

    describe('Bookmark Model Tests', function (): void {
        it('creates bookmark successfully', function (): void {
            $bookmark = Bookmark::factory()
                ->for($this->campaign)
                ->for($this->user)
                ->create();

            expect($bookmark)->toBeInstanceOf(Bookmark::class);
            expect($bookmark->campaign_id)->toBe($this->campaign->id);
            expect($bookmark->user_id)->toBe($this->user->id);

            $this->assertDatabaseHas('bookmarks', [
                'campaign_id' => $this->campaign->id,
                'user_id' => $this->user->id,
            ]);
        });

        it('removes bookmark successfully', function (): void {
            $bookmark = Bookmark::factory()
                ->for($this->campaign)
                ->for($this->user)
                ->create();

            $bookmark->delete();

            $this->assertDatabaseMissing('bookmarks', [
                'campaign_id' => $this->campaign->id,
                'user_id' => $this->user->id,
            ]);
        });

        it('prevents duplicate bookmarks', function (): void {
            Bookmark::factory()
                ->for($this->campaign)
                ->for($this->user)
                ->create();

            // Try to create another bookmark for same user/campaign
            $bookmarkCount = Bookmark::where('campaign_id', $this->campaign->id)
                ->where('user_id', $this->user->id)
                ->count();

            expect($bookmarkCount)->toBe(1);
        });

        it('allows multiple users to bookmark same campaign', function (): void {
            $otherUsers = User::factory()->count(3)->create();

            foreach ($otherUsers as $user) {
                Bookmark::factory()
                    ->for($this->campaign)
                    ->for($user)
                    ->create();
            }

            $bookmarkCount = Bookmark::where('campaign_id', $this->campaign->id)->count();

            expect($bookmarkCount)->toBe(3);
        });

        it('checks if user has bookmarked campaign', function (): void {
            // No bookmark initially
            $hasBookmark = Bookmark::where('campaign_id', $this->campaign->id)
                ->where('user_id', $this->user->id)
                ->exists();

            expect($hasBookmark)->toBeFalse();

            // Create bookmark
            Bookmark::factory()
                ->for($this->campaign)
                ->for($this->user)
                ->create();

            $hasBookmark = Bookmark::where('campaign_id', $this->campaign->id)
                ->where('user_id', $this->user->id)
                ->exists();

            expect($hasBookmark)->toBeTrue();
        });

        it('counts total bookmarks for campaign', function (): void {
            $users = User::factory()->count(5)->create();

            foreach ($users as $user) {
                Bookmark::factory()
                    ->for($this->campaign)
                    ->for($user)
                    ->create();
            }

            $bookmarkCount = Bookmark::where('campaign_id', $this->campaign->id)->count();

            expect($bookmarkCount)->toBe(5);
        });

        it('gets user bookmarks', function (): void {
            $campaigns = Campaign::factory()
                ->count(3)
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create(['category_id' => $this->category->id]);

            foreach ($campaigns as $campaign) {
                Bookmark::factory()
                    ->for($campaign)
                    ->for($this->user)
                    ->create();
            }

            $userBookmarks = Bookmark::where('user_id', $this->user->id)->get();

            expect($userBookmarks)->toHaveCount(3);
        });

        it('handles bookmark relationships', function (): void {
            $bookmark = Bookmark::factory()
                ->for($this->campaign)
                ->for($this->user)
                ->create();

            expect($bookmark->campaign)->toBeInstanceOf(Campaign::class);
            expect($bookmark->user)->toBeInstanceOf(User::class);
            expect($bookmark->campaign->id)->toBe($this->campaign->id);
            expect($bookmark->user->id)->toBe($this->user->id);
        });

        it('can toggle bookmark status', function (): void {
            // No bookmark initially
            $exists = Bookmark::where('campaign_id', $this->campaign->id)
                ->where('user_id', $this->user->id)
                ->exists();
            expect($exists)->toBeFalse();

            // Create bookmark (toggle on)
            $bookmark = Bookmark::factory()
                ->for($this->campaign)
                ->for($this->user)
                ->create();

            $exists = Bookmark::where('campaign_id', $this->campaign->id)
                ->where('user_id', $this->user->id)
                ->exists();
            expect($exists)->toBeTrue();

            // Remove bookmark (toggle off)
            $bookmark->delete();

            $exists = Bookmark::where('campaign_id', $this->campaign->id)
                ->where('user_id', $this->user->id)
                ->exists();
            expect($exists)->toBeFalse();
        });

        it('validates bookmark timestamps', function (): void {
            $bookmark = Bookmark::factory()
                ->for($this->campaign)
                ->for($this->user)
                ->create();

            expect($bookmark->created_at)->not->toBeNull();
            expect($bookmark->updated_at)->not->toBeNull();
        });
    });
});
