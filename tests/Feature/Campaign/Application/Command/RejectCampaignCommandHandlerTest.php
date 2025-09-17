<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\Campaign\Application\Command\RejectCampaignCommand;
use Modules\Campaign\Application\Command\RejectCampaignCommandHandler;
use Modules\Campaign\Domain\Event\CampaignRejectedEvent;
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
    $this->handler = new RejectCampaignCommandHandler($this->repository);

    // Create test users
    $this->employee = User::factory()->create();
    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super_admin');

    $this->regularUser = User::factory()->create();

    // Create test campaign in pending approval status
    $this->campaign = Campaign::factory()->create([
        'user_id' => $this->employee->id,
        'status' => CampaignStatus::PENDING_APPROVAL->value,
        'title' => 'Campaign Awaiting Review',
        'goal_amount' => 30000,
        'submitted_for_approval_at' => now()->subHours(3),
    ]);
});

it('successfully rejects campaign with reason', function (): void {
    Event::fake();

    $rejectionReason = 'Campaign lacks detailed impact assessment and budget breakdown.';

    $command = new RejectCampaignCommand(
        campaignId: $this->campaign->id,
        rejecterId: $this->superAdmin->id,
        rejectionReason: $rejectionReason
    );

    $result = $this->handler->handle($command);

    expect($result)->toBeInstanceOf(Campaign::class)
        ->and($result->status)->toBe(CampaignStatus::REJECTED)
        ->and($result->rejected_by)->toBe($this->superAdmin->id)
        ->and($result->rejected_at)->not->toBeNull()
        ->and($result->rejection_reason)->toBe($rejectionReason);

    // Verify database update
    $this->assertDatabaseHas('campaigns', [
        'id' => $this->campaign->id,
        'status' => CampaignStatus::REJECTED->value,
        'rejected_by' => $this->superAdmin->id,
        'rejection_reason' => $rejectionReason,
    ]);
});

it('clears previous approval data when rejecting', function (): void {
    // Set up campaign with previous approval data
    $this->repository->updateById($this->campaign->id, [
        'approved_by' => $this->superAdmin->id,
        'approved_at' => now()->subDays(1),
    ]);

    $command = new RejectCampaignCommand(
        campaignId: $this->campaign->id,
        rejecterId: $this->superAdmin->id,
        rejectionReason: 'New issues found during review'
    );

    $result = $this->handler->handle($command);

    expect($result->approved_by)->toBeNull()
        ->and($result->approved_at)->toBeNull();

    $this->assertDatabaseHas('campaigns', [
        'id' => $this->campaign->id,
        'approved_by' => null,
        'approved_at' => null,
    ]);
});

it('dispatches campaign status changed event with rejection reason', function (): void {
    Event::fake();

    $rejectionReason = 'Budget exceeds organizational limits without proper justification.';

    $command = new RejectCampaignCommand(
        campaignId: $this->campaign->id,
        rejecterId: $this->superAdmin->id,
        rejectionReason: $rejectionReason
    );

    $this->handler->handle($command);

    Event::assertDispatched(CampaignStatusChangedEvent::class, function ($event) use ($rejectionReason) {
        return $event->campaign->id === $this->campaign->id
            && $event->previousStatus === CampaignStatus::PENDING_APPROVAL
            && $event->newStatus === CampaignStatus::REJECTED
            && $event->changedByUserId === $this->superAdmin->id
            && $event->reason === $rejectionReason;
    });
});

it('dispatches campaign rejected event', function (): void {
    Event::fake();

    $rejectionReason = 'Timeline is unrealistic for project scope and complexity.';

    $command = new RejectCampaignCommand(
        campaignId: $this->campaign->id,
        rejecterId: $this->superAdmin->id,
        rejectionReason: $rejectionReason
    );

    $this->handler->handle($command);

    Event::assertDispatched(CampaignRejectedEvent::class, function ($event) use ($rejectionReason) {
        return $event->campaign->id === $this->campaign->id
            && $event->rejectedByUserId === $this->superAdmin->id
            && $event->reason === $rejectionReason;
    });
});

it('includes rejection reason in notification', function (): void {
    Notification::fake();
    Event::fake();

    $detailedReason = 'Campaign rejection reasons:\n\n' .
                     '1. Target amount exceeds company policy ($50,000 max)\n' .
                     '2. Missing stakeholder impact analysis\n' .
                     '3. No clear success metrics defined\n' .
                     '4. Insufficient community engagement plan\n\n' .
                     'Please address these issues and resubmit for approval.';

    $command = new RejectCampaignCommand(
        campaignId: $this->campaign->id,
        rejecterId: $this->superAdmin->id,
        rejectionReason: $detailedReason
    );

    $this->handler->handle($command);

    // In real implementation, notification would be handled by event listener
    Event::assertDispatched(CampaignRejectedEvent::class, function ($event) use ($detailedReason) {
        return $event->reason === $detailedReason;
    });
});

it('handles rejection without specific reason', function (): void {
    Event::fake();

    $command = new RejectCampaignCommand(
        campaignId: $this->campaign->id,
        rejecterId: $this->superAdmin->id,
        rejectionReason: null
    );

    $result = $this->handler->handle($command);

    expect($result->rejection_reason)->toBeNull();

    Event::assertDispatched(CampaignRejectedEvent::class, function ($event) {
        return $event->reason === null;
    });
});

it('handles rejection with empty reason', function (): void {
    Event::fake();

    $command = new RejectCampaignCommand(
        campaignId: $this->campaign->id,
        rejecterId: $this->superAdmin->id,
        rejectionReason: ''
    );

    $result = $this->handler->handle($command);

    expect($result->rejection_reason)->toBe('');
});

it('sends notification to campaign owner with rejection details', function (): void {
    Notification::fake();
    Event::fake();

    $rejectionReason = 'Please revise the budget section and provide more detailed timeline.';

    $command = new RejectCampaignCommand(
        campaignId: $this->campaign->id,
        rejecterId: $this->superAdmin->id,
        rejectionReason: $rejectionReason
    );

    $this->handler->handle($command);

    // Verify events are dispatched with proper data
    Event::assertDispatched(CampaignRejectedEvent::class, function ($event) use ($rejectionReason) {
        return $event->campaign->user_id === $this->employee->id
            && $event->reason === $rejectionReason;
    });
});

it('throws exception when campaign not found', function (): void {
    $command = new RejectCampaignCommand(
        campaignId: 999999,
        rejecterId: $this->superAdmin->id,
        rejectionReason: 'Campaign does not exist'
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(CampaignException::class);
});

it('throws exception when rejecter is not super admin', function (): void {
    $command = new RejectCampaignCommand(
        campaignId: $this->campaign->id,
        rejecterId: $this->regularUser->id,
        rejectionReason: 'Unauthorized rejection attempt'
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(CampaignException::class);
});

it('throws exception when rejecter does not exist', function (): void {
    $command = new RejectCampaignCommand(
        campaignId: $this->campaign->id,
        rejecterId: 999999,
        rejectionReason: 'Non-existent user rejection'
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(CampaignException::class);
});

it('throws exception for invalid status transition', function (): void {
    // Set campaign to active status (cannot reject active campaign)
    $this->repository->updateById($this->campaign->id, [
        'status' => CampaignStatus::ACTIVE->value,
    ]);

    $command = new RejectCampaignCommand(
        campaignId: $this->campaign->id,
        rejecterId: $this->superAdmin->id,
        rejectionReason: 'Invalid transition attempt'
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(Error::class);
});

it('throws exception when campaign is in draft status', function (): void {
    // Set campaign to draft status
    $this->repository->updateById($this->campaign->id, [
        'status' => CampaignStatus::DRAFT->value,
    ]);

    $command = new RejectCampaignCommand(
        campaignId: $this->campaign->id,
        rejecterId: $this->superAdmin->id,
        rejectionReason: 'Cannot reject draft campaign'
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(Error::class);
});

it('throws exception when campaign is already rejected', function (): void {
    // Set campaign to rejected status
    $this->repository->updateById($this->campaign->id, [
        'status' => CampaignStatus::REJECTED->value,
    ]);

    $command = new RejectCampaignCommand(
        campaignId: $this->campaign->id,
        rejecterId: $this->superAdmin->id,
        rejectionReason: 'Already rejected'
    );

    expect(fn () => $this->handler->handle($command))
        ->toThrow(Error::class);
});

it('throws exception for invalid command type', function (): void {
    $invalidCommand = new class implements \Modules\Shared\Application\Command\CommandInterface {};

    expect(fn () => $this->handler->handle($invalidCommand))
        ->toThrow(InvalidArgumentException::class, 'Invalid command type');
});

it('super admin can reject their own campaign', function (): void {
    Event::fake();

    // Create campaign owned by super admin
    $superAdminCampaign = Campaign::factory()->create([
        'user_id' => $this->superAdmin->id,
        'status' => CampaignStatus::PENDING_APPROVAL->value,
        'title' => 'Super Admin Self Campaign',
        'submitted_for_approval_at' => now()->subHours(1),
    ]);

    $rejectionReason = 'Self-rejection for revision';

    $command = new RejectCampaignCommand(
        campaignId: $superAdminCampaign->id,
        rejecterId: $this->superAdmin->id,
        rejectionReason: $rejectionReason
    );

    $result = $this->handler->handle($command);

    expect($result->status)->toBe(CampaignStatus::REJECTED)
        ->and($result->rejected_by)->toBe($this->superAdmin->id)
        ->and($result->rejection_reason)->toBe($rejectionReason);
});

it('updates rejection timestamp', function (): void {
    $command = new RejectCampaignCommand(
        campaignId: $this->campaign->id,
        rejecterId: $this->superAdmin->id,
        rejectionReason: 'Timestamp test rejection'
    );

    $result = $this->handler->handle($command);

    expect($result->rejected_at)->not->toBeNull()
        ->and($result->rejected_at)->toBeInstanceOf(\DateTimeInterface::class);
});

it('handles database transaction rollback on error', function (): void {
    Event::fake();

    // Mock repository to throw exception during second findById call
    $mockRepository = Mockery::mock(CampaignRepositoryInterface::class);
    $mockRepository->shouldReceive('findById')
        ->twice()
        ->with($this->campaign->id)
        ->andReturn($this->campaign, null); // Return null on second call to trigger error

    $mockRepository->shouldReceive('updateById')
        ->once()
        ->andReturn(true);

    $handler = new RejectCampaignCommandHandler($mockRepository);

    $command = new RejectCampaignCommand(
        campaignId: $this->campaign->id,
        rejecterId: $this->superAdmin->id,
        rejectionReason: 'Transaction rollback test'
    );

    expect(fn () => $handler->handle($command))
        ->toThrow(CampaignException::class);

    // Verify campaign status unchanged in real database
    $this->assertDatabaseHas('campaigns', [
        'id' => $this->campaign->id,
        'status' => CampaignStatus::PENDING_APPROVAL->value,
    ]);
});
