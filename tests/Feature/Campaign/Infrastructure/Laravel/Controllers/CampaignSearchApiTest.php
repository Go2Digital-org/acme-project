<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('Campaign Search and Filtering (Database Tests)', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
    });

    describe('Campaign Database Search Operations', function (): void {
        it('can retrieve campaigns from database as collection', function (): void {
            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Medical Equipment Fund'],
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Education Support'],
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            $campaigns = Campaign::all();
            expect($campaigns)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
            expect($campaigns->count())->toBeGreaterThanOrEqual(2);
        });

        it('filters campaigns by status using database queries', function (): void {
            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Active Campaign'],
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Completed Campaign'],
                    'status' => CampaignStatus::COMPLETED->value,
                ]);

            $activeCampaigns = Campaign::where('status', 'active')->get();
            $completedCampaigns = Campaign::where('status', 'completed')->get();

            expect($activeCampaigns->count())->toBe(1);
            expect($completedCampaigns->count())->toBe(1);
            expect($activeCampaigns->first()->status->value)->toBe('active');
            expect($completedCampaigns->first()->status->value)->toBe('completed');
        });

        it('filters campaigns by organization using database queries', function (): void {
            $otherOrganization = Organization::factory()->create();

            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Our Organization Campaign'],
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            Campaign::factory()
                ->for($otherOrganization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Other Organization Campaign'],
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            $ourCampaigns = Campaign::where('organization_id', $this->organization->id)->get();
            $otherCampaigns = Campaign::where('organization_id', $otherOrganization->id)->get();

            expect($ourCampaigns->count())->toBe(1);
            expect($otherCampaigns->count())->toBe(1);
            expect($ourCampaigns->first()->organization_id)->toBe($this->organization->id);
            expect($otherCampaigns->first()->organization_id)->toBe($otherOrganization->id);
        });

        it('tests pagination using database queries', function (): void {
            Campaign::factory()
                ->count(10)
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            $paginatedCampaigns = Campaign::paginate(5);

            expect($paginatedCampaigns->total())->toBe(10);
            expect($paginatedCampaigns->perPage())->toBe(5);
            expect($paginatedCampaigns->count())->toBe(5);
        });

        it('handles empty search results gracefully', function (): void {
            $nonExistentCampaigns = Campaign::where('status', 'nonexistent_status')->get();

            expect($nonExistentCampaigns)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
            expect($nonExistentCampaigns->count())->toBe(0);
            expect($nonExistentCampaigns->isEmpty())->toBeTrue();
        });

        it('tests campaign data structure and attributes', function (): void {
            $campaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Test Campaign'],
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            expect($campaign)->toHaveKey('id');
            expect($campaign)->toHaveKey('title');
            expect($campaign)->toHaveKey('status');
            expect($campaign)->toHaveKey('organization_id');
            expect($campaign)->toHaveKey('user_id');
            expect($campaign->getTitle())->toContain('Test Campaign');
            expect($campaign->status->value)->toBe('active');
        });

        it('tests search functionality using title field', function (): void {
            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Target Campaign for Tests'],
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Other Campaign Description'],
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            $targetCampaigns = Campaign::where('title->en', 'like', '%Target%')->get();
            $otherCampaigns = Campaign::where('title->en', 'like', '%Other%')->get();

            expect($targetCampaigns->count())->toBe(1);
            expect($otherCampaigns->count())->toBe(1);
            expect($targetCampaigns->first()->getTitle())->toContain('Target Campaign');
        });

        it('tests complex filtering combinations', function (): void {
            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Active Organization Campaign'],
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Completed Organization Campaign'],
                    'status' => CampaignStatus::COMPLETED->value,
                ]);

            $specificCampaigns = Campaign::where('organization_id', $this->organization->id)
                ->where('status', 'active')
                ->get();

            expect($specificCampaigns->count())->toBe(1);
            expect($specificCampaigns->first()->status->value)->toBe('active');
            expect($specificCampaigns->first()->organization_id)->toBe($this->organization->id);
        });
    });

});
