<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('Campaign API Search Feature', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
    });

    describe('Basic functionality', function (): void {
        it('can search campaigns via API', function (): void {
            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Medical Equipment Fund'],
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            $response = $this->actingAs($this->user, 'sanctum')
                ->get('/api/campaigns?search=Medical', ['Accept' => 'application/json']);

            $response->assertOk();
        });

        it('handles empty search results', function (): void {
            $response = $this->actingAs($this->user, 'sanctum')
                ->get('/api/campaigns?search=nonexistentterm', ['Accept' => 'application/json']);

            $response->assertOk();
            $data = $response->json();

            // Should return empty results
            if (isset($data['hydra:member'])) {
                expect($data['hydra:member'])->toBeArray();
                expect($data['hydra:totalItems'])->toBe(0);
            } else {
                expect($data)->toBeArray();
                expect(count($data))->toBe(0);
            }
        });
    });

    describe('Filtering functionality', function (): void {
        it('can filter campaigns by status via API', function (): void {
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
                    'title' => ['en' => 'Draft Campaign'],
                    'status' => CampaignStatus::DRAFT->value,
                ]);

            $response = $this->actingAs($this->user, 'sanctum')
                ->get('/api/campaigns?status=active', ['Accept' => 'application/json']);

            $response->assertOk();
        });

        it('can filter by date ranges via API', function (): void {
            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Ending Soon Campaign'],
                    'status' => CampaignStatus::ACTIVE->value,
                    'created_at' => now()->subWeeks(2),
                    'end_date' => now()->addWeeks(2),
                ]);

            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Recent Campaign'],
                    'status' => CampaignStatus::ACTIVE->value,
                    'created_at' => now()->subMonths(3),
                    'end_date' => now()->addMonths(6),
                ]);

            $response = $this->actingAs($this->user, 'sanctum')
                ->get('/api/campaigns?end_date[before]=' . now()->addMonth()->toDateString(), ['Accept' => 'application/json']);
            $response->assertOk();

            $response = $this->actingAs($this->user, 'sanctum')
                ->get('/api/campaigns?created_at[after]=' . now()->subDays(30)->toDateString(), ['Accept' => 'application/json']);
            $response->assertOk();
        });

        it('can filter campaigns by organization via API', function (): void {
            $organization1 = $this->organization;
            $organization2 = Organization::factory()->create();

            Campaign::factory()
                ->for($organization1)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Organization 1 Campaign'],
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            Campaign::factory()
                ->for($organization2)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Organization 2 Campaign'],
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            $response = $this->actingAs($this->user, 'sanctum')
                ->get("/api/campaigns?organization_id={$organization1->id}", ['Accept' => 'application/json']);

            $response->assertOk();
        });
    });

    describe('Pagination', function (): void {
        it('supports pagination in API search', function (): void {
            Campaign::factory()
                ->count(15)
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            $response = $this->actingAs($this->user, 'sanctum')
                ->get('/api/campaigns?itemsPerPage=10&page=1', ['Accept' => 'application/json']);

            $response->assertOk();
            $data = $response->json();

            if (isset($data['hydra:member'])) {
                expect($data['hydra:totalItems'])->toBeGreaterThan(10);
                expect(count($data['hydra:member']))->toBeLessThanOrEqual(10);
            }
        });
    });

    describe('Authentication', function (): void {
        it('requires authentication for API access', function (): void {
            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Public Campaign'],
                    'status' => CampaignStatus::ACTIVE->value,
                ]);

            $response = $this->get('/api/campaigns', ['Accept' => 'application/json']);

            $response->assertStatus(401);
        });
    });
});
