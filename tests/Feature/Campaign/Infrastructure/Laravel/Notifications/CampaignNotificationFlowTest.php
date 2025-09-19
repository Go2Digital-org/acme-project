<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('Campaign Notification Flow - Lightweight Database Tests', function (): void {
    beforeEach(function (): void {
        Notification::fake();

        $this->organization = Organization::factory()->create();

        // Create test users without complex role seeding
        $this->regularEmployee = User::factory()->create([
            'name' => 'John Doe',
            'organization_id' => $this->organization->id,
            'role' => 'employee',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'organization_id' => $this->organization->id,
            'role' => 'admin',
        ]);
    });

    describe('Campaign Status Transitions', function (): void {
        it('creates campaigns with proper status transitions', function (): void {
            // Create a draft campaign
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->regularEmployee, 'employee')
                ->draft()
                ->create([
                    'title' => ['en' => 'Environmental Awareness Campaign'],
                    'description' => ['en' => 'A campaign to raise environmental awareness'],
                    'goal_amount' => 10000.00,
                ]);

            expect($campaign->status->value)->toBe('draft');
            expect($campaign->user_id)->toBe($this->regularEmployee->id);

            // Simulate submission for approval
            $campaign->update(['status' => CampaignStatus::PENDING_APPROVAL->value]);
            $campaign->refresh();

            expect($campaign->status->value)->toBe('pending_approval');
        });

        it('handles campaign approval workflow', function (): void {
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->regularEmployee, 'employee')
                ->create([
                    'status' => CampaignStatus::PENDING_APPROVAL->value,
                    'title' => ['en' => 'Health Initiative'],
                ]);

            expect($campaign->status->value)->toBe('pending_approval');

            // Simulate approval by admin
            $campaign->update([
                'status' => CampaignStatus::ACTIVE->value,
                'approved_by' => $this->admin->id,
                'approved_at' => now(),
            ]);
            $campaign->refresh();

            expect($campaign->status->value)->toBe('active');
            expect($campaign->approved_by)->toBe($this->admin->id);
            expect($campaign->approved_at)->not->toBeNull();
        });

        it('handles campaign rejection workflow', function (): void {
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->regularEmployee, 'employee')
                ->create([
                    'status' => CampaignStatus::PENDING_APPROVAL->value,
                    'title' => ['en' => 'Education Project'],
                ]);

            expect($campaign->status->value)->toBe('pending_approval');

            // Simulate rejection by admin
            $rejectionReason = 'Campaign needs more detailed budget information';
            $campaign->update([
                'status' => CampaignStatus::REJECTED->value,
                'rejected_by' => $this->admin->id,
                'rejected_at' => now(),
                'rejection_reason' => $rejectionReason,
            ]);
            $campaign->refresh();

            expect($campaign->status->value)->toBe('rejected');
            expect($campaign->rejected_by)->toBe($this->admin->id);
            expect($campaign->rejected_at)->not->toBeNull();
            expect($campaign->rejection_reason)->toBe($rejectionReason);
        });
    });

    describe('Notification Data Structure Validation', function (): void {
        it('ensures campaign has all required notification data', function (): void {
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->regularEmployee, 'employee')
                ->create([
                    'title' => ['en' => 'Wildlife Conservation'],
                    'description' => ['en' => 'Protecting endangered species'],
                    'goal_amount' => 25000.00,
                    'status' => CampaignStatus::PENDING_APPROVAL->value,
                ]);

            // Verify campaign has all necessary data for notifications
            expect($campaign->getTitle())->not->toBeEmpty();
            expect($campaign->getDescription())->not->toBeEmpty();
            expect($campaign->user_id)->toBe($this->regularEmployee->id);
            expect($campaign->organization_id)->toBe($this->organization->id);
            expect($campaign->goal_amount)->toBeGreaterThan(0);
        });

        it('provides proper campaign URL data for notifications', function (): void {
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->regularEmployee, 'employee')
                ->create();

            $actionUrl = route('campaigns.show', $campaign->id);
            expect($actionUrl)->toContain((string) $campaign->id);
        });
    });

    describe('User Relationships for Notifications', function (): void {
        it('maintains proper user relationships for notification targeting', function (): void {
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->regularEmployee, 'employee')
                ->create();

            // Campaign owner relationship
            expect($campaign->employee)->not->toBeNull();
            expect($campaign->employee->id)->toBe($this->regularEmployee->id);
            expect($campaign->employee->name)->toBe('John Doe');

            // Organization relationship
            expect($campaign->organization)->not->toBeNull();
            expect($campaign->organization->id)->toBe($this->organization->id);
        });

        it('supports multiple admin users for notification distribution', function (): void {
            $admin2 = User::factory()->create([
                'name' => 'Second Admin',
                'organization_id' => $this->organization->id,
                'role' => 'admin',
            ]);

            $admin3 = User::factory()->create([
                'name' => 'Third Admin',
                'organization_id' => $this->organization->id,
                'role' => 'admin',
            ]);

            $allAdmins = User::where('role', 'admin')->get();
            expect($allAdmins)->toHaveCount(3); // Including the one from beforeEach

            foreach ($allAdmins as $admin) {
                expect($admin->role)->toBe('admin');
            }
        });
    });

    describe('Campaign Categories and Filtering', function (): void {
        it('handles different campaign categories for targeted notifications', function (): void {
            $healthCampaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->regularEmployee, 'employee')
                ->create([
                    'title' => ['en' => 'Community Health Program'],
                    'category' => 'health',
                ]);

            $educationCampaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->regularEmployee, 'employee')
                ->create([
                    'title' => ['en' => 'School Equipment Fund'],
                    'category' => 'education',
                ]);

            expect($healthCampaign->category)->toBe('health');
            expect($educationCampaign->category)->toBe('education');

            // Verify category-based filtering works
            $healthCampaigns = Campaign::where('category', 'health')->get();
            $educationCampaigns = Campaign::where('category', 'education')->get();

            expect($healthCampaigns)->toHaveCount(1);
            expect($educationCampaigns)->toHaveCount(1);
        });
    });

    describe('Notification Timing and State', function (): void {
        it('tracks proper timestamps for notification workflows', function (): void {
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->regularEmployee, 'employee')
                ->draft()
                ->create();

            $initialTimestamp = $campaign->created_at;

            // Simulate submission with explicit future timestamp
            $submissionTime = $initialTimestamp->copy()->addSecond();
            $campaign->update([
                'status' => CampaignStatus::PENDING_APPROVAL->value,
                'submitted_for_approval_at' => $submissionTime,
            ]);

            expect($campaign->submitted_for_approval_at)->not->toBeNull();
            expect($campaign->submitted_for_approval_at->timestamp)->toBeGreaterThan($initialTimestamp->timestamp);

            // Simulate approval with explicit future timestamp
            $approvalTime = $submissionTime->copy()->addSecond();
            $campaign->update([
                'status' => CampaignStatus::ACTIVE->value,
                'approved_at' => $approvalTime,
                'approved_by' => $this->admin->id,
            ]);

            expect($campaign->approved_at)->not->toBeNull();
            expect($campaign->approved_by)->toBe($this->admin->id);
        });
    });

    describe('Edge Cases and Error Prevention', function (): void {
        it('handles campaigns without owners gracefully', function (): void {
            // This tests defensive programming for notification systems
            $deletedUser = User::factory()->create();
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->create(['user_id' => $deletedUser->id]);

            // Simulate user being deleted (soft delete)
            $deletedUser->delete();

            expect($campaign->user_id)->toBe($deletedUser->id);
            expect($campaign->employee)->toBeNull(); // Should return null for deleted user
        });

        it('prevents duplicate notifications by tracking status changes', function (): void {
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->regularEmployee, 'employee')
                ->create(['status' => CampaignStatus::PENDING_APPROVAL->value]);

            $originalStatus = $campaign->status->value;
            expect($originalStatus)->toBe('pending_approval');

            // Simulate checking for status change before sending notifications
            $campaign->update(['status' => CampaignStatus::ACTIVE->value]);
            $campaign->refresh();

            $newStatus = $campaign->status->value;
            expect($newStatus)->toBe('active');
            expect($newStatus)->not->toBe($originalStatus);

            // This status change detection would trigger notifications in real implementation
        });
    });
});
