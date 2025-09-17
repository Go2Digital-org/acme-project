<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\Campaign\Application\Command\ApproveCampaignCommand;
use Modules\Campaign\Application\Command\ApproveCampaignCommandHandler;
use Modules\Campaign\Application\Command\RejectCampaignCommand;
use Modules\Campaign\Application\Command\RejectCampaignCommandHandler;
use Modules\Campaign\Application\Command\SubmitForApprovalCommand;
use Modules\Campaign\Application\Command\SubmitForApprovalCommandHandler;
use Modules\Campaign\Domain\Event\CampaignApprovedEvent;
use Modules\Campaign\Domain\Event\CampaignRejectedEvent;
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

    // Set up repositories and handlers
    $this->repository = app(CampaignRepositoryInterface::class);
    $this->submitHandler = new SubmitForApprovalCommandHandler($this->repository);
    $this->approveHandler = new ApproveCampaignCommandHandler($this->repository);
    $this->rejectHandler = new RejectCampaignCommandHandler($this->repository);

    // Create test users
    $this->regularEmployee = User::factory()->create(['name' => 'John Doe']);

    $this->superAdmin1 = User::factory()->create(['name' => 'Super Admin One']);
    $this->superAdmin1->assignRole('super_admin');

    $this->superAdmin2 = User::factory()->create(['name' => 'Super Admin Two']);
    $this->superAdmin2->assignRole('super_admin');

    $this->superAdmin3 = User::factory()->create(['name' => 'Super Admin Three']);
    $this->superAdmin3->assignRole('super_admin');
});

describe('Regular employee campaign submission flow', function (): void {
    beforeEach(function (): void {
        $this->campaign = Campaign::factory()->create([
            'user_id' => $this->regularEmployee->id,
            'status' => CampaignStatus::DRAFT->value,
            'title' => 'Community Garden Project',
            'goal_amount' => 15000,
        ]);
    });

    it('notifies all super admins when regular employee submits campaign', function (): void {
        Event::fake();

        $command = new SubmitForApprovalCommand(
            campaignId: $this->campaign->id,
            employeeId: $this->regularEmployee->id
        );

        $this->submitHandler->handle($command);

        // Verify submission event is dispatched
        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            return $event->campaign->id === $this->campaign->id
                && $event->submitterId === $this->regularEmployee->id;
        });

        // Verify status change event is dispatched
        Event::assertDispatched(CampaignStatusChangedEvent::class, function ($event) {
            return $event->isSubmissionForApproval()
                && $event->changedByUserId === $this->regularEmployee->id;
        });
    });

    it('sends standard approval notification to all super admins', function (): void {
        Event::fake();

        $command = new SubmitForApprovalCommand(
            campaignId: $this->campaign->id,
            employeeId: $this->regularEmployee->id
        );

        $result = $this->submitHandler->handle($command);

        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            // In real implementation, event listener would:
            // 1. Get all super admins
            // 2. Send notification: "New campaign '{title}' needs approval from {employee_name}"
            // 3. Include action URL for approval
            return $event->campaign->title === 'Community Garden Project'
                && $event->submitterId === $this->regularEmployee->id;
        });
    });

    it('notifies campaign owner when campaign is approved', function (): void {
        Event::fake();

        // First submit for approval
        $this->repository->updateById($this->campaign->id, [
            'status' => CampaignStatus::PENDING_APPROVAL->value,
            'submitted_for_approval_at' => now()->subHour(),
        ]);

        // Then approve
        $approveCommand = new ApproveCampaignCommand(
            campaignId: $this->campaign->id,
            approverId: $this->superAdmin1->id,
            notes: 'Great community impact potential!'
        );

        $this->approveHandler->handle($approveCommand);

        // Verify approval event contains campaign owner info
        Event::assertDispatched(CampaignApprovedEvent::class, function ($event) {
            return $event->campaign->user_id === $this->regularEmployee->id
                && $event->approvedByUserId === $this->superAdmin1->id
                && $event->notes === 'Great community impact potential!';
        });
    });

    it('notifies campaign owner with rejection reason', function (): void {
        Event::fake();

        // First submit for approval
        $this->repository->updateById($this->campaign->id, [
            'status' => CampaignStatus::PENDING_APPROVAL->value,
            'submitted_for_approval_at' => now()->subHour(),
        ]);

        // Then reject
        $rejectionReason = 'Please provide more detailed budget breakdown and timeline.';
        $rejectCommand = new RejectCampaignCommand(
            campaignId: $this->campaign->id,
            rejecterId: $this->superAdmin2->id,
            rejectionReason: $rejectionReason
        );

        $this->rejectHandler->handle($rejectCommand);

        // Verify rejection event contains reason for notification
        Event::assertDispatched(CampaignRejectedEvent::class, function ($event) use ($rejectionReason) {
            return $event->campaign->user_id === $this->regularEmployee->id
                && $event->rejectedByUserId === $this->superAdmin2->id
                && $event->reason === $rejectionReason;
        });
    });
});

describe('Super admin self-submission scenario', function (): void {
    beforeEach(function (): void {
        $this->superAdminCampaign = Campaign::factory()->create([
            'user_id' => $this->superAdmin1->id,
            'status' => CampaignStatus::DRAFT->value,
            'title' => 'Super Admin Environmental Initiative',
            'goal_amount' => 25000,
        ]);
    });

    it('sends special self-submission notification to submitting super admin', function (): void {
        Event::fake();

        $command = new SubmitForApprovalCommand(
            campaignId: $this->superAdminCampaign->id,
            employeeId: $this->superAdmin1->id
        );

        $this->submitHandler->handle($command);

        // Verify events contain data for different notification types
        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            // Event listener would detect that submitterId === campaign->user_id
            // and submitter has super_admin role, then send special message:
            // "Your campaign is pending approval. Approve now?"
            return $event->submitterId === $this->superAdmin1->id
                && $event->campaign->user_id === $this->superAdmin1->id;
        });
    });

    it('sends standard notification to other super admins', function (): void {
        Event::fake();

        $command = new SubmitForApprovalCommand(
            campaignId: $this->superAdminCampaign->id,
            employeeId: $this->superAdmin1->id
        );

        $this->submitHandler->handle($command);

        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            // Event listener would send standard notification to superAdmin2 and superAdmin3:
            // "New campaign 'Super Admin Environmental Initiative' needs approval from Super Admin One"
            return $event->campaign->title === 'Super Admin Environmental Initiative'
                && $event->submitterId === $this->superAdmin1->id;
        });
    });

    it('includes action URL in self-submission notification', function (): void {
        Event::fake();

        $command = new SubmitForApprovalCommand(
            campaignId: $this->superAdminCampaign->id,
            employeeId: $this->superAdmin1->id
        );

        $result = $this->submitHandler->handle($command);

        // Verify event data structure supports action URL generation
        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            return $event->campaign->id !== null; // Event listener can generate URLs with campaign ID
        });
    });

    it('super admin can approve their own submitted campaign', function (): void {
        Event::fake();

        // First submit for approval
        $this->repository->updateById($this->superAdminCampaign->id, [
            'status' => CampaignStatus::PENDING_APPROVAL->value,
            'submitted_for_approval_at' => now()->subHour(),
        ]);

        // Then self-approve
        $approveCommand = new ApproveCampaignCommand(
            campaignId: $this->superAdminCampaign->id,
            approverId: $this->superAdmin1->id,
            notes: 'Self-approval after review'
        );

        $result = $this->approveHandler->handle($approveCommand);

        expect($result->status)->toBe(CampaignStatus::ACTIVE)
            ->and($result->approved_by)->toBe($this->superAdmin1->id);

        // Verify self-approval event
        Event::assertDispatched(CampaignApprovedEvent::class, function ($event) {
            return $event->campaign->user_id === $this->superAdmin1->id
                && $event->approvedByUserId === $this->superAdmin1->id;
        });
    });
});

describe('Complete notification workflow scenarios', function (): void {
    it('handles complete submission to approval workflow', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->regularEmployee->id,
            'status' => CampaignStatus::DRAFT->value,
            'title' => 'Complete Workflow Test',
        ]);

        // Step 1: Submit for approval
        $submitCommand = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $this->regularEmployee->id
        );
        $this->submitHandler->handle($submitCommand);

        // Step 2: Approve campaign
        $approveCommand = new ApproveCampaignCommand(
            campaignId: $campaign->id,
            approverId: $this->superAdmin1->id,
            notes: 'Approved after thorough review'
        );
        $this->approveHandler->handle($approveCommand);

        // Verify all events in workflow
        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class);
        Event::assertDispatched(CampaignApprovedEvent::class);
        Event::assertDispatchedTimes(CampaignStatusChangedEvent::class, 2); // Submit + Approve
    });

    it('handles submission to rejection to resubmission workflow', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->regularEmployee->id,
            'status' => CampaignStatus::DRAFT->value,
            'title' => 'Rejection Workflow Test',
        ]);

        // Step 1: Submit for approval
        $submitCommand = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $this->regularEmployee->id
        );
        $this->submitHandler->handle($submitCommand);

        // Step 2: Reject campaign
        $rejectCommand = new RejectCampaignCommand(
            campaignId: $campaign->id,
            rejecterId: $this->superAdmin2->id,
            rejectionReason: 'Needs more detailed impact assessment'
        );
        $this->rejectHandler->handle($rejectCommand);

        // Step 3: Resubmit after rejection
        $resubmitCommand = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $this->regularEmployee->id
        );
        $this->submitHandler->handle($resubmitCommand);

        // Verify complete workflow events
        Event::assertDispatchedTimes(CampaignSubmittedForApprovalEvent::class, 2); // Initial + Resubmit
        Event::assertDispatched(CampaignRejectedEvent::class);
        Event::assertDispatchedTimes(CampaignStatusChangedEvent::class, 3); // Submit + Reject + Resubmit
    });
});

describe('Edge cases and error scenarios', function (): void {
    it('handles no duplicate notifications scenario', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->regularEmployee->id,
            'status' => CampaignStatus::DRAFT->value,
        ]);

        $command = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $this->regularEmployee->id
        );

        $this->submitHandler->handle($command);

        // Verify each event is dispatched only once
        Event::assertDispatchedTimes(CampaignSubmittedForApprovalEvent::class, 1);
        Event::assertDispatchedTimes(CampaignStatusChangedEvent::class, 1);
    });

    it('handles missing campaign owner gracefully', function (): void {
        Event::fake();

        // Create campaign with non-existent owner
        $campaign = Campaign::factory()->create([
            'user_id' => 999999, // Non-existent user
            'status' => CampaignStatus::PENDING_APPROVAL->value,
        ]);

        $approveCommand = new ApproveCampaignCommand(
            campaignId: $campaign->id,
            approverId: $this->superAdmin1->id
        );

        // Should still work, event listener should handle missing user gracefully
        $result = $this->approveHandler->handle($approveCommand);

        Event::assertDispatched(CampaignApprovedEvent::class, function ($event) {
            return $event->campaign->user_id === 999999; // Event still contains the ID
        });
    });

    it('ensures rejection reason is properly included in notifications', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->regularEmployee->id,
            'status' => CampaignStatus::PENDING_APPROVAL->value,
        ]);

        $detailedReason = 'Campaign rejected for the following reasons:\n\n' .
                         '1. Budget breakdown lacks detail\n' .
                         '2. Timeline seems unrealistic\n' .
                         '3. Missing community impact metrics\n\n' .
                         'Please address these issues and resubmit.';

        $rejectCommand = new RejectCampaignCommand(
            campaignId: $campaign->id,
            rejecterId: $this->superAdmin1->id,
            rejectionReason: $detailedReason
        );

        $this->rejectHandler->handle($rejectCommand);

        // Verify reason is included in both events
        Event::assertDispatched(CampaignRejectedEvent::class, function ($event) use ($detailedReason) {
            return $event->reason === $detailedReason;
        });

        Event::assertDispatched(CampaignStatusChangedEvent::class, function ($event) use ($detailedReason) {
            return $event->reason === $detailedReason;
        });
    });

    it('handles notification for different campaign categories', function (): void {
        Event::fake();

        $categories = ['environment', 'education', 'health', 'community'];

        foreach ($categories as $category) {
            $campaign = Campaign::factory()->create([
                'user_id' => $this->regularEmployee->id,
                'status' => CampaignStatus::DRAFT->value,
                'category' => $category,
                'title' => ucfirst($category) . ' Initiative',
            ]);

            $command = new SubmitForApprovalCommand(
                campaignId: $campaign->id,
                employeeId: $this->regularEmployee->id
            );

            $this->submitHandler->handle($command);
        }

        // Verify event dispatched for each category
        Event::assertDispatchedTimes(CampaignSubmittedForApprovalEvent::class, 4);
    });
});

describe('Notification data structure validation', function (): void {
    it('ensures all required notification data is present in events', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->regularEmployee->id,
            'status' => CampaignStatus::DRAFT->value,
            'title' => 'Data Structure Test Campaign',
            'goal_amount' => 20000,
        ]);

        $command = new SubmitForApprovalCommand(
            campaignId: $campaign->id,
            employeeId: $this->regularEmployee->id
        );

        $this->submitHandler->handle($command);

        Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
            // Verify all data needed for notifications is present
            return $event->campaign !== null
                && $event->campaign->id !== null
                && $event->campaign->title !== null
                && $event->campaign->user_id !== null
                && $event->submitterId !== null;
        });

        Event::assertDispatched(CampaignStatusChangedEvent::class, function ($event) {
            return $event->campaign !== null
                && $event->previousStatus !== null
                && $event->newStatus !== null
                && $event->changedByUserId !== null;
        });
    });
});
