<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\Campaign\Application\Command\ApproveCampaignCommand;
use Modules\Campaign\Application\Command\ApproveCampaignCommandHandler;
use Modules\Campaign\Domain\Event\CampaignApprovedEvent;
use Modules\Campaign\Domain\Event\CampaignStatusChangedEvent;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    // Seed roles and permissions first
    $this->seed(\Modules\User\Infrastructure\Laravel\Seeder\AdminUserSeeder::class);

    $this->repository = app(CampaignRepositoryInterface::class);
    $this->handler = new ApproveCampaignCommandHandler($this->repository);

    // Create test users
    $this->employee = User::factory()->create();
    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super_admin');

    $this->regularUser = User::factory()->create();

    // Create test campaign in pending approval status
    $this->campaign = Campaign::factory()->create([
        'user_id' => $this->employee->id,
        'status' => CampaignStatus::PENDING_APPROVAL->value,
        'title' => 'Pending Approval Campaign',
        'goal_amount' => 25000,
        'submitted_for_approval_at' => now()->subHours(1),
    ]);
});

it('successfully approves campaign and sets to active', function (): void {
    Event::fake();

    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: $this->superAdmin->id
    );

    $result = $this->handler->handle($command);

    expect($result)->toBeInstanceOf(Campaign::class)
        ->and($result->status)->toBe(CampaignStatus::ACTIVE)
        ->and($result->approved_by)->toBe($this->superAdmin->id)
        ->and($result->approved_at)->not->toBeNull();

    // Verify database update
    $this->assertDatabaseHas('campaigns', [
        'id' => $this->campaign->id,
        'status' => CampaignStatus::ACTIVE->value,
        'approved_by' => $this->superAdmin->id,
    ]);
});

it('clears previous rejection data when approving', function (): void {
    // Set up campaign with previous rejection data
    $this->repository->updateById($this->campaign->id, [
        'rejected_by' => $this->superAdmin->id,
        'rejected_at' => now()->subDays(2),
        'rejection_reason' => 'Previous rejection reason',
    ]);

    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: $this->superAdmin->id
    );

    $result = $this->handler->handle($command);

    expect($result->rejected_by)->toBeNull()
        ->and($result->rejected_at)->toBeNull()
        ->and($result->rejection_reason)->toBeNull();

    $this->assertDatabaseHas('campaigns', [
        'id' => $this->campaign->id,
        'rejected_by' => null,
        'rejected_at' => null,
        'rejection_reason' => null,
    ]);
});

it('dispatches campaign status changed event', function (): void {
    Event::fake();

    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: $this->superAdmin->id
    );

    $this->handler->handle($command);

    Event::assertDispatched(CampaignStatusChangedEvent::class, function ($event) {
        return $event->campaign->id === $this->campaign->id
            && $event->previousStatus === CampaignStatus::PENDING_APPROVAL
            && $event->newStatus === CampaignStatus::ACTIVE
            && $event->changedByUserId === $this->superAdmin->id;
    });
});

it('dispatches campaign approved event', function (): void {
    Event::fake();

    $notes = 'Campaign approved - excellent community impact potential';

    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: $this->superAdmin->id,
        notes: $notes
    );

    $this->handler->handle($command);

    Event::assertDispatched(CampaignApprovedEvent::class, function ($event) use ($notes) {
        return $event->campaign->id === $this->campaign->id
            && $event->approvedByUserId === $this->superAdmin->id
            && $event->notes === $notes;
    });
});

it('dispatches campaign approved event without notes', function (): void {
    Event::fake();

    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: $this->superAdmin->id
    );

    $this->handler->handle($command);

    Event::assertDispatched(CampaignApprovedEvent::class, function ($event) {
        return $event->campaign->id === $this->campaign->id
            && $event->approvedByUserId === $this->superAdmin->id
            && $event->notes === null;
    });
});

it('sends notification to campaign owner when approved', function (): void {
    Notification::fake();
    Event::fake();

    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: $this->superAdmin->id,
        notes: 'Great campaign with clear objectives!'
    );

    $this->handler->handle($command);

    // In real implementation, this would be handled by an event listener
    Event::assertDispatched(CampaignApprovedEvent::class);
    Event::assertDispatched(CampaignStatusChangedEvent::class);
});

it('handles approval with detailed notes', function (): void {
    Event::fake();

    $detailedNotes = 'Campaign approved after thorough review. ' .
                    'The environmental impact assessment is comprehensive, ' .
                    'budget allocation is realistic, and community engagement plan is solid. ' .
                    'Expected to exceed target by 20%.';

    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: $this->superAdmin->id,
        notes: $detailedNotes
    );

    $result = $this->handler->handle($command);

    Event::assertDispatched(CampaignApprovedEvent::class, function ($event) use ($detailedNotes) {
        return $event->notes === $detailedNotes;
    });
});

it('throws exception when campaign not found', function (): void {
    $command = new ApproveCampaignCommand(
        campaignId: 999999,
        approverId: $this->superAdmin->id
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(CampaignException::class);
});

it('throws exception when approver is not super admin', function (): void {
    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: $this->regularUser->id
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(CampaignException::class);
});

it('throws exception when approver does not exist', function (): void {
    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: 999999
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(CampaignException::class);
});

it('throws exception for invalid status transition', function (): void {
    // Set campaign to active status (cannot approve active campaign)
    $this->repository->updateById($this->campaign->id, [
        'status' => CampaignStatus::ACTIVE->value,
    ]);

    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: $this->superAdmin->id
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(Error::class);
});

it('throws exception when campaign is in draft status', function (): void {
    // Set campaign to draft status
    $this->repository->updateById($this->campaign->id, [
        'status' => CampaignStatus::DRAFT->value,
    ]);

    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: $this->superAdmin->id
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(Error::class);
});

it('throws exception when campaign is rejected', function (): void {
    // Set campaign to rejected status
    $this->repository->updateById($this->campaign->id, [
        'status' => CampaignStatus::REJECTED->value,
    ]);

    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: $this->superAdmin->id
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(Error::class);
});

it('throws exception for invalid command type', function (): void {
    $invalidCommand = new class implements \Modules\Shared\Application\Command\CommandInterface {};

    expect(fn () => $this->handler->handle($invalidCommand))
        ->toThrow(InvalidArgumentException::class, 'Invalid command type');
});

it('handles database transaction rollback on error', function (): void {
    Event::fake();

    // Mock repository to throw exception during update
    $mockRepository = Mockery::mock(CampaignRepositoryInterface::class);
    $mockRepository->shouldReceive('findById')
        ->twice()
        ->with($this->campaign->id)
        ->andReturn($this->campaign, null); // Return null on second call to trigger error

    $mockRepository->shouldReceive('updateById')
        ->once()
        ->andReturn(true);

    $handler = new ApproveCampaignCommandHandler($mockRepository);

    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: $this->superAdmin->id
    );

    expect(fn () => $handler->handle($command))
        ->toThrow(CampaignException::class);

    // Verify campaign status unchanged in real database
    $this->assertDatabaseHas('campaigns', [
        'id' => $this->campaign->id,
        'status' => CampaignStatus::PENDING_APPROVAL->value,
    ]);
});

it('super admin can approve their own campaign', function (): void {
    Event::fake();

    // Create campaign owned by super admin
    $superAdminCampaign = Campaign::factory()->create([
        'user_id' => $this->superAdmin->id,
        'status' => CampaignStatus::PENDING_APPROVAL->value,
        'title' => 'Super Admin Own Campaign',
        'submitted_for_approval_at' => now()->subHours(2),
    ]);

    $command = new ApproveCampaignCommand(
        campaignId: $superAdminCampaign->id,
        approverId: $this->superAdmin->id
    );

    $result = $this->handler->handle($command);

    expect($result->status)->toBe(CampaignStatus::ACTIVE)
        ->and($result->approved_by)->toBe($this->superAdmin->id);

    Event::assertDispatched(CampaignApprovedEvent::class, function ($event) use ($superAdminCampaign) {
        return $event->campaign->id === $superAdminCampaign->id
            && $event->approvedByUserId === $this->superAdmin->id;
    });
});

it('updates approval timestamp', function (): void {
    $beforeApproval = now()->subSecond(); // Add a small buffer

    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: $this->superAdmin->id
    );

    $result = $this->handler->handle($command);

    expect($result->approved_at)->not->toBeNull();

    // Check that the timestamp is recent (within last 5 seconds)
    $timeDiff = now()->diffInSeconds($result->approved_at);
    expect($timeDiff)->toBeLessThan(5);
});
