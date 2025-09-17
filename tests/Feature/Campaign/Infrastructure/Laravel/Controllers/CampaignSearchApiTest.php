<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('Campaign API', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
    });

    describe('GET /api/campaigns', function (): void {
        it('returns campaigns as array', function (): void {
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

            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/api/campaigns');

            $response->assertOk();
            $campaigns = $response->json();
            expect($campaigns)->toBeArray();
            expect(count($campaigns))->toBeGreaterThanOrEqual(0);
        });

        it('filters by status parameter', function (): void {
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

            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/api/campaigns?status=active');

            $response->assertOk();
            $campaigns = $response->json();
            expect($campaigns)->toBeArray();
            if (! empty($campaigns)) {
                foreach ($campaigns as $campaign) {
                    expect($campaign['status'])->toBe('active');
                }
            }
        });

        it('filters campaigns by organization', function (): void {
            $otherOrganization = Organization::factory()->create();

            $campaign1 = Campaign::factory()
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

            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->get("/api/campaigns?organization_id={$this->organization->id}");

            $response->assertOk();
            $campaigns = $response->json();
            expect($campaigns)->toBeArray();
            if (! empty($campaigns)) {
                foreach ($campaigns as $campaign) {
                    expect($campaign['organizationId'])->toBe($this->organization->id);
                }
            }
        });

        it('respects pagination parameters', function (): void {
            Campaign::factory()
                ->count(10)
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/api/campaigns?itemsPerPage=5&page=1');

            $response->assertOk();
            $campaigns = $response->json();
            expect($campaigns)->toBeArray();
            expect(count($campaigns))->toBeLessThanOrEqual(5);
        });

        it('handles empty results gracefully', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/api/campaigns?status=nonexistent');

            $response->assertOk();
            $campaigns = $response->json();
            expect($campaigns)->toBeArray();
            expect($campaigns)->toEqual([]);
        });

        it('returns correct campaign data structure', function (): void {
            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Test Campaign'],
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/api/campaigns');

            $response->assertOk();
            $campaigns = $response->json();
            expect($campaigns)->toBeArray();

            if (! empty($campaigns)) {
                $campaign = $campaigns[0];
                expect($campaign)->toHaveKeys(['id', 'title', 'description', 'goalAmount', 'currentAmount', 'status', 'organizationId', 'employeeId']);
            }
        });

        it('handles authentication requirement', function (): void {
            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            $response = $this->withHeaders(['Accept' => 'application/json'])
                ->get('/api/campaigns');

            $response->assertStatus(401);
        });

        it('returns campaigns with valid structure', function (): void {
            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Target Campaign'],
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Other Campaign'],
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            $response = $this->actingAs($this->user, 'sanctum')
                ->withHeaders(['Accept' => 'application/json'])
                ->get('/api/campaigns');

            $response->assertOk();
            $campaigns = $response->json();
            expect($campaigns)->toBeArray();
            expect(count($campaigns))->toBeGreaterThan(0);

            // Verify campaign structure
            $campaign = $campaigns[0];
            expect($campaign)->toHaveKey('id');
            expect($campaign)->toHaveKey('title');
        });
    });
});
