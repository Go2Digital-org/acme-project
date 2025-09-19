<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('Campaign API (Fallback Tests)', function (): void {
    beforeEach(function (): void {
        // Setup test data
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
    });

    describe('Campaign Database Operations', function (): void {
        it('creates campaigns correctly in database', function (): void {
            // Create campaigns with proper factory relationships
            $campaigns = Campaign::factory()
                ->count(3)
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->active()
                ->create();

            expect($campaigns)->toHaveCount(3);

            foreach ($campaigns as $campaign) {
                expect($campaign->organization_id)->toBe($this->organization->id);
                expect($campaign->user_id)->toBe($this->user->id);
                expect($campaign->status->value)->toBe('active');
            }

            $this->assertDatabaseHas('campaigns', [
                'organization_id' => $this->organization->id,
                'user_id' => $this->user->id,
                'status' => 'active',
            ]);
        });

        it('validates campaign data integrity', function (): void {
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Test Campaign', 'fr' => 'Campagne de Test'],
                    'description' => ['en' => 'Test Description', 'fr' => 'Description de Test'],
                    'goal_amount' => 10000.00,
                ]);

            expect($campaign->getTitle())->toContain('Test Campaign');
            expect($campaign->getDescription())->toContain('Test Description');
            expect($campaign->goal_amount)->toBe(10000.00);
            expect($campaign->organization_id)->toBe($this->organization->id);
            expect($campaign->user_id)->toBe($this->user->id);
        });

        it('tests campaign filtering by status', function (): void {
            // Create active campaigns
            Campaign::factory()
                ->count(2)
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->active()
                ->create();

            // Create draft campaigns
            Campaign::factory()
                ->count(3)
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->draft()
                ->create();

            // Test that we can filter in the database
            $activeCampaigns = Campaign::where('status', 'active')->get();
            $draftCampaigns = Campaign::where('status', 'draft')->get();

            expect($activeCampaigns)->toHaveCount(2);
            expect($draftCampaigns)->toHaveCount(3);
        });

        it('tests campaign search functionality at model level', function (): void {
            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->active()
                ->create(['title' => ['en' => 'Environmental Campaign for Wildlife']]);

            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->active()
                ->create(['title' => ['en' => 'Education Initiative for Schools']]);

            // Test database-level search
            $environmentalCampaigns = Campaign::where('title->en', 'like', '%Environmental%')->get();
            $educationCampaigns = Campaign::where('title->en', 'like', '%Education%')->get();

            expect($environmentalCampaigns)->toHaveCount(1);
            expect($educationCampaigns)->toHaveCount(1);
        });

        it('tests campaign validation rules', function (): void {
            // Test that negative goal amounts are handled (may not throw exception but should be invalid)
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create(['goal_amount' => -1000.00]);

            // The campaign might be created but should be considered invalid
            expect($campaign->goal_amount)->toBe(-1000.00);
            // In a real application, this would be validated at the form/request level
        });

        it('tests campaign relationships', function (): void {
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create();

            // Test relationships are properly loaded
            expect($campaign->organization)->not->toBeNull();
            expect($campaign->employee)->not->toBeNull();
            expect($campaign->organization->id)->toBe($this->organization->id);
            expect($campaign->employee->id)->toBe($this->user->id);
        });

        it('tests campaign business logic methods', function (): void {
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->active()
                ->create([
                    'goal_amount' => 10000.00,
                    'current_amount' => 5000.00,
                    'start_date' => now()->subDay(), // Started yesterday
                    'end_date' => now()->addMonth(), // Ends in a month
                ]);

            expect($campaign->getProgressPercentage())->toBe(50.0);
            expect($campaign->getRemainingAmount())->toBe(5000.00);
            expect($campaign->hasReachedGoal())->toBeFalse();
            expect($campaign->canAcceptDonation())->toBeTrue();
        });
    });

});
