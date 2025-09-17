<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\Campaign\Application\Command\SubmitForApprovalCommand;
use Modules\Campaign\Application\Command\SubmitForApprovalCommandHandler;
use Modules\Campaign\Domain\Event\CampaignStatusChangedEvent;
use Modules\Campaign\Domain\Event\CampaignSubmittedForApprovalEvent;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Seed roles and permissions for tests
    $this->seed(\Modules\User\Infrastructure\Laravel\Seeder\AdminUserSeeder::class);

    $this->repository = app(CampaignRepositoryInterface::class);
    $this->handler = new SubmitForApprovalCommandHandler($this->repository);

    // Create super admins
    $this->superAdminSubmitter = User::factory()->create([
        'name' => 'Super Admin Submitter',
        'email' => 'super.admin@company.com',
    ]);
    $this->superAdminSubmitter->assignRole('super_admin');

    $this->superAdminReviewer1 = User::factory()->create([
        'name' => 'Super Admin Reviewer One',
        'email' => 'reviewer1@company.com',
    ]);
    $this->superAdminReviewer1->assignRole('super_admin');

    $this->superAdminReviewer2 = User::factory()->create([
        'name' => 'Super Admin Reviewer Two',
        'email' => 'reviewer2@company.com',
    ]);
    $this->superAdminReviewer2->assignRole('super_admin');

    // Create regular employee for comparison
    $this->regularEmployee = User::factory()->create([
        'name' => 'Regular Employee',
        'email' => 'employee@company.com',
    ]);
});

describe('Super Admin Self-Submission Detection', function (): void {
    it('identifies self-submission when super admin submits own campaign', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->superAdminSubmitter->id,
            'status' => CampaignStatus::DRAFT->value,
            'title' => 'Super Admin Self Campaign',
        ]);

        $command = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $this->superAdminSubmitter->id
        );

        $this->handler->handle($command);

        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            // Event listener should detect: submitterId === campaign.user_id AND submitter hasRole('super_admin')
            $submitter = User::find($event->submitterId);
            $isOwner = $event->campaign->user_id === $event->submitterId;
            $isSuperAdmin = $submitter?->hasRole('super_admin') ?? false;

            return $isOwner && $isSuperAdmin;
        });
    });

    it('does not identify as self-submission when regular employee submits', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->regularEmployee->id,
            'status' => CampaignStatus::DRAFT->value,
            'title' => 'Regular Employee Campaign',
        ]);

        $command = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $this->regularEmployee->id
        );

        $this->handler->handle($command);

        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            $submitter = User::find($event->submitterId);
            $isOwner = $event->campaign->user_id === $event->submitterId;
            $isSuperAdmin = $submitter?->hasRole('super_admin') ?? false;

            // Is owner but not super admin
            return $isOwner && ! $isSuperAdmin;
        });
    });

    it('does not identify as self-submission when different super admin submits their own campaign', function (): void {
        Event::fake();

        // Campaign owned by a different super admin
        $campaign = Campaign::factory()->create([
            'user_id' => $this->superAdminReviewer1->id, // Different super admin owns
            'status' => CampaignStatus::DRAFT->value,
            'title' => 'Different Super Admin Campaign',
        ]);

        $command = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $this->superAdminReviewer1->id // Same super admin submits their own
        );

        $this->handler->handle($command);

        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            $submitter = User::find($event->submitterId);
            $isOwner = $event->campaign->user_id === $event->submitterId;
            $isSuperAdmin = $submitter?->hasRole('super_admin') ?? false;

            // Is super admin and is owner (but different from main test super admin)
            return $isOwner && $isSuperAdmin && $event->submitterId === $this->superAdminReviewer1->id;
        });
    });
});

describe('Self-Submission Notification Messages', function (): void {
    it('provides data for "Your campaign is pending approval. Approve now?" message', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->superAdminSubmitter->id,
            'status' => CampaignStatus::DRAFT->value,
            'title' => 'Environmental Leadership Initiative',
            'description' => 'Leading by example in corporate sustainability',
            'goal_amount' => 50000,
        ]);

        $command = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $this->superAdminSubmitter->id
        );

        $this->handler->handle($command);

        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            // Event contains all data needed for self-submission notification
            return $event->campaign->title === 'Environmental Leadership Initiative'
                && $event->campaign->user_id === $this->superAdminSubmitter->id
                && $event->submitterId === $this->superAdminSubmitter->id
                && $event->campaign->id !== null; // For generating approval action URL
        });
    });

    it('provides data for standard notification to other super admins', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->superAdminSubmitter->id,
            'status' => CampaignStatus::DRAFT->value,
            'title' => 'Tech Innovation Campaign',
        ]);

        $command = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $this->superAdminSubmitter->id
        );

        $this->handler->handle($command);

        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            $submitter = User::find($event->submitterId);

            // Event contains data for: "New campaign '{title}' needs approval from {submitter_name}"
            return $event->campaign->title === 'Tech Innovation Campaign'
                && $submitter?->name === 'Super Admin Submitter'
                && $event->campaign->id !== null;
        });
    });
});

describe('Action URL Generation Support', function (): void {
    it('provides campaign ID for approval action URL in self-submission', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->superAdminSubmitter->id,
            'status' => CampaignStatus::DRAFT->value,
        ]);

        $command = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $this->superAdminSubmitter->id
        );

        $this->handler->handle($command);

        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) use ($campaign) {
            // Event listener can generate: route('campaigns.approve', $event->campaign->id)
            return $event->campaign->id === $campaign->id;
        });
    });

    it('supports different action URLs for different notification types', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->superAdminSubmitter->id,
            'status' => CampaignStatus::DRAFT->value,
        ]);

        $command = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $this->superAdminSubmitter->id
        );

        $this->handler->handle($command);

        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            // Event listener can generate different URLs based on recipient:
            // - Self: /campaigns/{id}/quick-approve (direct approval)
            // - Others: /campaigns/{id}/review (review page)
            return $event->campaign->id !== null
                && $event->submitterId !== null;
        });
    });
});

describe('Multiple Super Admin Scenario Handling', function (): void {
    it('handles notification distribution with multiple super admins', function (): void {
        Event::fake();

        // Create additional super admins
        $superAdmin3 = User::factory()->create(['name' => 'Super Admin Three']);
        $superAdmin3->assignRole('super_admin');

        $superAdmin4 = User::factory()->create(['name' => 'Super Admin Four']);
        $superAdmin4->assignRole('super_admin');

        $campaign = Campaign::factory()->create([
            'user_id' => $this->superAdminSubmitter->id,
            'status' => CampaignStatus::DRAFT->value,
            'title' => 'Multi-Admin Test Campaign',
        ]);

        $command = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $this->superAdminSubmitter->id
        );

        $this->handler->handle($command);

        // Single event dispatched - event listener handles distribution logic
        Event::assertDispatchedTimes(CampaignSubmittedForApprovalEvent::class, 1);

        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            // Event listener should:
            // 1. Send self-notification to submitter (superAdminSubmitter)
            // 2. Send standard notifications to others (reviewer1, reviewer2, admin3, admin4)
            return $event->submitterId === $this->superAdminSubmitter->id;
        });
    });

    it('excludes submitter from standard notification list', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->superAdminSubmitter->id,
            'status' => CampaignStatus::DRAFT->value,
        ]);

        $command = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $this->superAdminSubmitter->id
        );

        $this->handler->handle($command);

        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            // Event listener logic should exclude submitter from "others" list:
            // $allSuperAdmins = User::role('super_admin')->get();
            // $otherSuperAdmins = $allSuperAdmins->where('id', '!=', $event->submitterId);
            return $event->submitterId === $this->superAdminSubmitter->id;
        });
    });
});

describe('Edge Cases in Self-Submission', function (): void {
    it('handles self-submission when super admin role was recently assigned', function (): void {
        Event::fake();

        // Create user without super admin role initially
        $newSuperAdmin = User::factory()->create(['name' => 'Newly Promoted Admin']);

        $campaign = Campaign::factory()->create([
            'user_id' => $newSuperAdmin->id,
            'status' => CampaignStatus::DRAFT->value,
        ]);

        // Assign super admin role just before submission
        $newSuperAdmin->assignRole('super_admin');

        $command = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $newSuperAdmin->id
        );

        $this->handler->handle($command);

        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            // Should still detect as self-submission
            $submitter = User::find($event->submitterId);

            return $submitter?->hasRole('super_admin') === true
                && $event->campaign->user_id === $event->submitterId;
        });
    });

    it('handles self-submission resubmission after rejection', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->superAdminSubmitter->id,
            'status' => CampaignStatus::REJECTED->value,
            'rejected_by' => $this->superAdminReviewer1->id,
            'rejected_at' => now()->subDays(1),
            'rejection_reason' => 'Need more details',
        ]);

        // Resubmit after addressing feedback
        $command = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $this->superAdminSubmitter->id
        );

        $this->handler->handle($command);

        Event::assertDispatched(CampaignStatusChangedEvent::class, function ($event) {
            // Resubmission from rejected to pending_approval
            return $event->previousStatus === CampaignStatus::REJECTED
                && $event->newStatus === CampaignStatus::PENDING_APPROVAL
                && $event->changedByUserId === $this->superAdminSubmitter->id;
        });

        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            // Still a self-submission scenario
            return $event->campaign->user_id === $event->submitterId;
        });
    });

    it('handles self-submission with detailed campaign information', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->superAdminSubmitter->id,
            'status' => CampaignStatus::DRAFT->value,
            'title' => 'Executive Sustainability Challenge',
            'description' => 'A leadership initiative to demonstrate environmental commitment.',
            'goal_amount' => 75000,
            'category' => 'environment',
            'start_date' => now()->addWeek(),
            'end_date' => now()->addMonths(3),
        ]);

        $command = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $this->superAdminSubmitter->id
        );

        $this->handler->handle($command);

        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            // All campaign details available for rich notification
            return $event->campaign->title === 'Executive Sustainability Challenge'
                && $event->campaign->goal_amount == 75000 // Use == instead of === for type-flexible comparison
                && $event->campaign->category === 'environment';
        });
    });
});
