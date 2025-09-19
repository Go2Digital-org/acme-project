<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('Donation API (Database Tests)', function (): void {
    beforeEach(function (): void {
        // Mock external services to prevent actual API calls
        Event::fake();
        Mail::fake();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->campaign = Campaign::factory()
            ->for($this->organization)
            ->for($this->user, 'employee')
            ->active()
            ->create();
    });

    describe('Donation Database Operations', function (): void {
        it('can create donations in database', function (): void {
            $donations = Donation::factory()
                ->count(3)
                ->for($this->campaign)
                ->for($this->user, 'user')
                ->completed()
                ->create();

            expect($donations)->toHaveCount(3);

            foreach ($donations as $donation) {
                expect($donation->campaign_id)->toBe($this->campaign->id);
                expect($donation->user_id)->toBe($this->user->id);
                expect($donation->status->value)->toBe('completed');
            }

            $this->assertDatabaseHas('donations', [
                'campaign_id' => $this->campaign->id,
                'user_id' => $this->user->id,
                'status' => 'completed',
            ]);
        });

        it('handles empty donation collections', function (): void {
            $donations = Donation::all();

            expect($donations)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
            expect($donations->count())->toBe(0);
        });

        it('can filter donations by campaign', function (): void {
            $otherCampaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create();

            Donation::factory()
                ->for($this->campaign)
                ->for($this->user, 'user')
                ->create();

            Donation::factory()
                ->for($otherCampaign)
                ->for($this->user, 'user')
                ->create();

            $campaignDonations = Donation::where('campaign_id', $this->campaign->id)->get();
            $otherDonations = Donation::where('campaign_id', $otherCampaign->id)->get();

            expect($campaignDonations->count())->toBe(1);
            expect($otherDonations->count())->toBe(1);
        });

        it('can retrieve specific donation by id', function (): void {
            $donation = Donation::factory()
                ->for($this->campaign)
                ->for($this->user, 'user')
                ->create(['amount' => 100.00]);

            $foundDonation = Donation::find($donation->id);

            expect($foundDonation)->not->toBeNull();
            expect($foundDonation->id)->toBe($donation->id);
            expect($foundDonation->amount)->toBe(100.00);
        });

        it('tests donation relationships', function (): void {
            $donation = Donation::factory()
                ->for($this->campaign)
                ->for($this->user, 'user')
                ->create();

            expect($donation->campaign)->not->toBeNull();
            expect($donation->user)->not->toBeNull();
            expect($donation->campaign->id)->toBe($this->campaign->id);
            expect($donation->user->id)->toBe($this->user->id);
        });

        it('can count donations efficiently', function (): void {
            Donation::factory()
                ->count(5)
                ->for($this->campaign)
                ->for($this->user, 'user')
                ->create();

            $totalCount = Donation::count();
            $campaignCount = Donation::where('campaign_id', $this->campaign->id)->count();

            expect($totalCount)->toBe(5);
            expect($campaignCount)->toBe(5);
        });

        it('can calculate donation totals', function (): void {
            Donation::factory()
                ->for($this->campaign)
                ->for($this->user, 'user')
                ->create(['amount' => 100.00]);

            Donation::factory()
                ->for($this->campaign)
                ->for($this->user, 'user')
                ->create(['amount' => 250.00]);

            $totalAmount = Donation::where('campaign_id', $this->campaign->id)->sum('amount');

            expect((float) $totalAmount)->toBe(350.00);
        });

        it('tests donation status filtering', function (): void {
            Donation::factory()
                ->for($this->campaign)
                ->for($this->user, 'user')
                ->completed()
                ->create();

            Donation::factory()
                ->for($this->campaign)
                ->for($this->user, 'user')
                ->pending()
                ->create();

            $completedDonations = Donation::where('status', 'completed')->get();
            $pendingDonations = Donation::where('status', 'pending')->get();

            expect($completedDonations->count())->toBe(1);
            expect($pendingDonations->count())->toBe(1);
        });
    });

});
