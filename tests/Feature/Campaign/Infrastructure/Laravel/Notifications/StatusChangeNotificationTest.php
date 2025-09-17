<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Campaign\Application\Command\ApproveCampaignCommand;
use Modules\Campaign\Application\Command\ApproveCampaignCommandHandler;
use Modules\Campaign\Application\Command\RejectCampaignCommand;
use Modules\Campaign\Application\Command\RejectCampaignCommandHandler;
use Modules\Campaign\Application\Command\SubmitForApprovalCommandHandler;
use Modules\Campaign\Domain\Event\CampaignApprovedEvent;
use Modules\Campaign\Domain\Event\CampaignRejectedEvent;
use Modules\Campaign\Domain\Event\CampaignStatusChangedEvent;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Seed roles and permissions for tests
    $this->seed(\Modules\User\Infrastructure\Laravel\Seeder\AdminUserSeeder::class);

    $this->repository = app(CampaignRepositoryInterface::class);
    $this->submitHandler = new SubmitForApprovalCommandHandler($this->repository);
    $this->approveHandler = new ApproveCampaignCommandHandler($this->repository);
    $this->rejectHandler = new RejectCampaignCommandHandler($this->repository);

    // Create test users
    $this->employee = User::factory()->create(['name' => 'Employee User']);
    $this->superAdmin = User::factory()->create(['name' => 'Super Admin User']);
    $this->superAdmin->assignRole('super_admin');
});

describe('Campaign Status Change Notifications', function (): void {
    it('dispatches event when campaign is approved', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->employee->id,
            'status' => 'pending_approval',
        ]);

        $command = new ApproveCampaignCommand($campaign->id, $this->superAdmin->id);
        $this->approveHandler->handle($command);

        Event::assertDispatched(CampaignApprovedEvent::class);
        Event::assertDispatched(CampaignStatusChangedEvent::class);
    });

    it('dispatches event when campaign is rejected', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->employee->id,
            'status' => 'pending_approval',
        ]);

        $command = new RejectCampaignCommand(
            $campaign->id,
            $this->superAdmin->id,
            'Needs more detail'
        );
        $this->rejectHandler->handle($command);

        Event::assertDispatched(CampaignRejectedEvent::class);
        Event::assertDispatched(CampaignStatusChangedEvent::class);
    });

    it('sends notification to campaign owner when approved', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->employee->id,
            'status' => 'pending_approval',
        ]);

        $command = new ApproveCampaignCommand($campaign->id, $this->superAdmin->id);
        $this->approveHandler->handle($command);

        // Verify that events are dispatched which would trigger notifications
        Event::assertDispatched(CampaignApprovedEvent::class, function ($event) use ($campaign) {
            return $event->campaign->id === $campaign->id
                && $event->approvedByUserId === $this->superAdmin->id;
        });
    });

    it('sends notification to campaign owner when rejected', function (): void {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'user_id' => $this->employee->id,
            'status' => 'pending_approval',
        ]);

        $command = new RejectCampaignCommand(
            $campaign->id,
            $this->superAdmin->id,
            'Needs improvement'
        );
        $this->rejectHandler->handle($command);

        // Verify that events are dispatched which would trigger notifications
        Event::assertDispatched(CampaignRejectedEvent::class, function ($event) use ($campaign) {
            return $event->campaign->id === $campaign->id
                && $event->rejectedByUserId === $this->superAdmin->id
                && $event->reason === 'Needs improvement';
        });
    });
});
