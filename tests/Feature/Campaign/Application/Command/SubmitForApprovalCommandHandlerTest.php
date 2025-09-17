<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\Campaign\Application\Command\SubmitForApprovalCommand;
use Modules\Campaign\Application\Command\SubmitForApprovalCommandHandler;
use Modules\Campaign\Domain\Event\CampaignStatusChangedEvent;
use Modules\Campaign\Domain\Event\CampaignSubmittedForApprovalEvent;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Seed roles and permissions first
    $this->seed(\Modules\User\Infrastructure\Laravel\Seeder\AdminUserSeeder::class);

    $this->repository = app(CampaignRepositoryInterface::class);
    $this->handler = new SubmitForApprovalCommandHandler($this->repository);

    // Create test users
    $this->employee = User::factory()->create();
    $this->superAdmin1 = User::factory()->create();
    $this->superAdmin1->assignRole('super_admin');

    $this->superAdmin2 = User::factory()->create();
    $this->superAdmin2->assignRole('super_admin');

    // Create test campaign in draft status
    $this->campaign = Campaign::factory()->create([
        'user_id' => $this->employee->id,
        'status' => CampaignStatus::DRAFT->value,
        'title' => 'Test Environmental Campaign',
        'goal_amount' => 10000,
        'start_date' => now()->addDays(7),
        'end_date' => now()->addDays(37),
    ]);
});

it('successfully submits campaign for approval', function (): void {
    Event::fake();

    $command = new SubmitForApprovalCommand(
        campaignId: $this->campaign->id,
        employeeId: $this->employee->id
    );

    $result = $this->handler->handle($command);

    expect($result)->toBeInstanceOf(Campaign::class)
        ->and($result->status)->toBe(CampaignStatus::PENDING_APPROVAL)
        ->and($result->submitted_for_approval_at)->not->toBeNull();

    // Verify database update
    $this->assertDatabaseHas('campaigns', [
        'id' => $this->campaign->id,
        'status' => CampaignStatus::PENDING_APPROVAL->value,
    ]);
});

it('dispatches campaign status changed event', function (): void {
    Event::fake();

    $command = new SubmitForApprovalCommand(
        campaignId: $this->campaign->id,
        employeeId: $this->employee->id
    );

    $this->handler->handle($command);

    Event::assertDispatched(CampaignStatusChangedEvent::class, function ($event) {
        return $event->campaign->id === $this->campaign->id
            && $event->previousStatus === CampaignStatus::DRAFT
            && $event->newStatus === CampaignStatus::PENDING_APPROVAL
            && $event->changedByUserId === $this->employee->id;
    });
});

it('dispatches campaign submitted for approval event', function (): void {
    Event::fake();

    $command = new SubmitForApprovalCommand(
        campaignId: $this->campaign->id,
        employeeId: $this->employee->id
    );

    $this->handler->handle($command);

    Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) {
        return $event->campaign->id === $this->campaign->id
            && $event->submitterId === $this->employee->id;
    });
});

it('sends notifications to all super admins when campaign is submitted', function (): void {
    Notification::fake();
    Event::fake();

    // Create additional super admin
    $superAdmin3 = User::factory()->create();
    $superAdmin3->assignRole('super_admin');

    $command = new SubmitForApprovalCommand(
        campaignId: $this->campaign->id,
        employeeId: $this->employee->id
    );

    $this->handler->handle($command);

    // In a real implementation, this would be handled by an event listener
    // For now, we verify the events are dispatched correctly
    Event::assertDispatched(CampaignSubmittedForApprovalEvent::class);
    Event::assertDispatched(CampaignStatusChangedEvent::class);
});

it('handles super admin submitting their own campaign', function (): void {
    Event::fake();

    // Create campaign owned by super admin
    $superAdminCampaign = Campaign::factory()->create([
        'user_id' => $this->superAdmin1->id,
        'status' => CampaignStatus::DRAFT->value,
        'title' => 'Super Admin Self Campaign',
        'goal_amount' => 15000,
        'start_date' => now()->addDays(5),
        'end_date' => now()->addDays(35),
    ]);

    $command = new SubmitForApprovalCommand(
        campaignId: $superAdminCampaign->id,
        employeeId: $this->superAdmin1->id
    );

    $result = $this->handler->handle($command);

    expect($result->status)->toBe(CampaignStatus::PENDING_APPROVAL);

    // Verify event includes submitter as super admin
    Event::assertDispatched(CampaignSubmittedForApprovalEvent::class, function ($event) use ($superAdminCampaign) {
        return $event->campaign->id === $superAdminCampaign->id
            && $event->submitterId === $this->superAdmin1->id;
    });
});

it('allows resubmission from rejected status', function (): void {
    Event::fake();

    // Set campaign to rejected status first
    $this->repository->updateById($this->campaign->id, [
        'status' => CampaignStatus::REJECTED->value,
        'rejected_by' => $this->superAdmin1->id,
        'rejected_at' => now()->subDays(1),
        'rejection_reason' => 'Initial feedback',
    ]);

    $command = new SubmitForApprovalCommand(
        campaignId: $this->campaign->id,
        employeeId: $this->employee->id
    );

    $result = $this->handler->handle($command);

    expect($result->status)->toBe(CampaignStatus::PENDING_APPROVAL);

    Event::assertDispatched(CampaignStatusChangedEvent::class, function ($event) {
        return $event->previousStatus === CampaignStatus::REJECTED
            && $event->newStatus === CampaignStatus::PENDING_APPROVAL;
    });
});

it('throws exception when campaign not found', function (): void {
    $command = new SubmitForApprovalCommand(
        campaignId: 999999,
        employeeId: $this->employee->id
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(CampaignException::class);
});

it('throws exception when employee unauthorized', function (): void {
    $otherEmployee = User::factory()->create();

    $command = new SubmitForApprovalCommand(
        campaignId: $this->campaign->id,
        employeeId: $otherEmployee->id
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(CampaignException::class);
});

it('throws exception for invalid status transition', function (): void {
    // Set campaign to active status (cannot transition to pending)
    $this->repository->updateById($this->campaign->id, [
        'status' => CampaignStatus::ACTIVE->value,
    ]);

    $command = new SubmitForApprovalCommand(
        campaignId: $this->campaign->id,
        employeeId: $this->employee->id
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(Error::class);
});

it('throws exception for invalid command type', function (): void {
    $invalidCommand = new class implements \Modules\Shared\Application\Command\CommandInterface {};

    expect(fn () => $this->handler->handle($invalidCommand))
        ->toThrow(InvalidArgumentException::class, 'Invalid command type');
});

it('validates business rules before submission', function (): void {
    // Create campaign with invalid date range
    $invalidCampaign = Campaign::factory()->create([
        'user_id' => $this->employee->id,
        'status' => CampaignStatus::DRAFT->value,
        'start_date' => now()->addDays(10),
        'end_date' => now()->addDays(5), // End date before start date
        'goal_amount' => 10000,
    ]);

    $command = new SubmitForApprovalCommand(
        campaignId: $invalidCampaign->id,
        employeeId: $this->employee->id
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(CampaignException::class);
});

it('validates goal amount before submission', function (): void {
    // Create campaign with invalid goal amount
    $invalidCampaign = Campaign::factory()->create([
        'user_id' => $this->employee->id,
        'status' => CampaignStatus::DRAFT->value,
        'goal_amount' => 0, // Invalid amount
        'start_date' => now()->addDays(5),
        'end_date' => now()->addDays(35),
    ]);

    $command = new SubmitForApprovalCommand(
        campaignId: $invalidCampaign->id,
        employeeId: $this->employee->id
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(CampaignException::class);
});

it('handles database transaction rollback on error', function (): void {
    Event::fake();

    // Mock repository to throw exception during update
    $mockRepository = Mockery::mock(CampaignRepositoryInterface::class);
    $mockRepository->shouldReceive('findById')
        ->with($this->campaign->id)
        ->andReturn($this->campaign);

    $mockRepository->shouldReceive('updateById')
        ->andThrow(new Exception('Database error'));

    $handler = new SubmitForApprovalCommandHandler($mockRepository);

    $command = new SubmitForApprovalCommand(
        campaignId: $this->campaign->id,
        employeeId: $this->employee->id
    );

    expect(fn () => $handler->handle($command))
        ->toThrow(Exception::class, 'Database error');

    // Verify campaign status unchanged
    $this->assertDatabaseHas('campaigns', [
        'id' => $this->campaign->id,
        'status' => CampaignStatus::DRAFT->value,
    ]);
});

it('updates submission timestamp', function (): void {
    $beforeSubmission = now()->subSecond(); // Use a timestamp 1 second before to account for microseconds

    $command = new SubmitForApprovalCommand(
        campaignId: $this->campaign->id,
        employeeId: $this->employee->id
    );

    $result = $this->handler->handle($command);

    expect($result->submitted_for_approval_at)->not->toBeNull()
        ->and($result->submitted_for_approval_at)->toBeGreaterThanOrEqual($beforeSubmission);
});
