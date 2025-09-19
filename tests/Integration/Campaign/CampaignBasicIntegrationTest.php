<?php

declare(strict_types=1);

use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create();
});

describe('Campaign Basic Integration', function (): void {
    it('creates and retrieves a campaign from database', function (): void {
        $campaign = Campaign::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'status' => CampaignStatus::ACTIVE->value,
        ]);

        expect($campaign->exists)->toBeTrue()
            ->and($campaign->organization_id)->toBe($this->organization->id)
            ->and($campaign->user_id)->toBe($this->user->id)
            ->and($campaign->status)->toBe(CampaignStatus::ACTIVE);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'organization_id' => $this->organization->id,
        ]);
    });

    it('loads campaign relationships', function (): void {
        $campaign = Campaign::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
        ]);

        $campaignWithRelations = Campaign::with(['organization', 'creator'])->find($campaign->id);

        expect($campaignWithRelations->organization)->not->toBeNull()
            ->and($campaignWithRelations->creator)->not->toBeNull()
            ->and($campaignWithRelations->organization->id)->toBe($this->organization->id)
            ->and($campaignWithRelations->creator->id)->toBe($this->user->id);
    });

    it('finds campaigns by organization', function (): void {
        $campaign1 = Campaign::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'status' => CampaignStatus::ACTIVE->value,
        ]);

        $otherOrg = Organization::factory()->create();
        Campaign::factory()->create([
            'organization_id' => $otherOrg->id,
            'user_id' => $this->user->id,
            'status' => CampaignStatus::ACTIVE->value,
        ]);

        $orgCampaigns = Campaign::where('organization_id', $this->organization->id)->get();

        expect($orgCampaigns)->toHaveCount(1)
            ->and($orgCampaigns->first()->id)->toBe($campaign1->id);
    });

    it('updates campaign status', function (): void {
        $campaign = Campaign::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'status' => CampaignStatus::DRAFT->value,
        ]);

        $campaign->update(['status' => CampaignStatus::ACTIVE->value]);

        expect($campaign->fresh()->status)->toBe(CampaignStatus::ACTIVE);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => CampaignStatus::ACTIVE->value,
        ]);
    });

    it('soft deletes campaigns', function (): void {
        $campaign = Campaign::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
        ]);

        $campaign->delete();

        expect(Campaign::find($campaign->id))->toBeNull();
        expect(Campaign::withTrashed()->find($campaign->id))->not->toBeNull();
    });
});
