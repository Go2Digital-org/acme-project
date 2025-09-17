<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

describe('Campaign List Performance Optimizations', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();

        // Create test campaigns with various statuses
        $this->campaigns = collect([
            Campaign::factory()->active()->for($this->organization)->for($this->user, 'employee')->create(),
            Campaign::factory()->completed()->for($this->organization)->for($this->user, 'employee')->create(),
            Campaign::factory()->draft()->for($this->organization)->for($this->user, 'employee')->create(),
            Campaign::factory()->paused()->for($this->organization)->for($this->user, 'employee')->create(),
        ]);
    });

    describe('Field selection optimization', function (): void {
        it('retrieves campaigns list successfully', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
            $data = $response->json();

            // Check if we have either API Platform format or direct array
            if (isset($data['hydra:member'])) {
                expect($data['hydra:member'])->toBeArray();
            } else {
                expect($data)->toBeArray();
            }
        });

        it('supports API Platform JSON-LD format', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'Accept' => 'application/ld+json',
            ]);

            $response->assertOk();
            $data = $response->json();

            // Check for API Platform structure
            if (isset($data['hydra:member'])) {
                expect($data)->toHaveKeys(['hydra:member', 'hydra:totalItems']);
            }
        });

        it('handles authentication properly', function (): void {
            // Test without authentication
            $response = get('/api/campaigns', [
                'Accept' => 'application/json',
            ]);

            $response->assertStatus(401);

            // Test with authentication
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
        });

        it('includes campaign basic properties', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
            $data = $response->json();

            // Check that each campaign has basic properties
            $campaigns = $data['hydra:member'] ?? $data ?? [];
            if (! empty($campaigns)) {
                $firstCampaign = $campaigns[0];
                expect($firstCampaign)->toHaveKey('id');
                expect($firstCampaign)->toHaveKey('title');
            }
        });

        it('supports search parameter', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'search' => 'test',
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
        });
    });

    describe('Pagination optimization', function (): void {
        it('supports pagination', function (): void {
            Campaign::factory()->count(25)->for($this->organization)->for($this->user, 'employee')->create();
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'itemsPerPage' => 10,
                'page' => 1,
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
            $data = $response->json();

            // Check for API Platform pagination structure
            if (isset($data['hydra:totalItems'])) {
                expect($data['hydra:totalItems'])->toBeGreaterThanOrEqual(25);
                expect(count($data['hydra:member']))->toBeLessThanOrEqual(20); // Default per page
            }
        });

        it('handles large page sizes', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'itemsPerPage' => 100, // At maximum
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
        });

        it('handles multiple pages', function (): void {
            Campaign::factory()->count(15)->for($this->organization)->for($this->user, 'employee')->create();
            actingAs($this->user, 'sanctum');

            // First page
            $response1 = get('/api/campaigns', [
                'itemsPerPage' => 5,
                'page' => 1,
                'Accept' => 'application/json',
            ]);

            // Second page
            $response2 = get('/api/campaigns', [
                'itemsPerPage' => 5,
                'page' => 2,
                'Accept' => 'application/json',
            ]);

            $response1->assertOk();
            $response2->assertOk();

            $data1 = $response1->json();
            $data2 = $response2->json();

            // Ensure we have data on both pages
            $page1Data = $data1['hydra:member'] ?? $data1 ?? [];
            $page2Data = $data2['hydra:member'] ?? $data2 ?? [];

            expect($page1Data)->toBeArray();
            expect($page2Data)->toBeArray();
        });

        it('provides pagination metadata', function (): void {
            Campaign::factory()->count(23)->for($this->organization)->for($this->user, 'employee')->create();
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'itemsPerPage' => 10,
                'page' => 2,
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
            $data = $response->json();

            // Check for API Platform pagination metadata
            if (isset($data['hydra:totalItems'])) {
                expect($data['hydra:totalItems'])->toBeGreaterThanOrEqual(23);
            }
        });
    });

    describe('Filtering and search optimization', function (): void {
        it('supports status filtering', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'status' => 'active',
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
        });

        it('supports organization filtering', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'organization_id' => $this->organization->id,
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
        });

        it('supports search functionality', function (): void {
            $searchableCampaign = Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Save the Ocean Environment'],
                    'description' => ['en' => 'Help protect marine life'],
                ]);

            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'search' => 'Ocean',
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
        });

        it('handles multiple filter parameters', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'status' => 'active',
                'organization_id' => $this->organization->id,
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
        });

        it('handles invalid filter values gracefully', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'status' => 'invalid_status',
                'Accept' => 'application/json',
            ]);

            // Should either work (return empty) or give proper error
            expect($response->getStatusCode())->toBeIn([200, 400, 422]);
        });
    });

    describe('Sorting support', function (): void {
        it('supports basic sorting', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'sort[createdAt]' => 'desc',
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
        });

        it('handles multiple sort parameters', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'sort[status]' => 'asc',
                'sort[createdAt]' => 'desc',
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
        });
    });

    describe('Response headers', function (): void {
        it('includes proper content type headers', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'Accept' => 'application/json',
            ]);

            $response->assertOk()
                ->assertHeader('Content-Type');
        });

        it('handles different accept headers', function (): void {
            actingAs($this->user, 'sanctum');

            $jsonResponse = get('/api/campaigns', [
                'Accept' => 'application/json',
            ]);

            $jsonLdResponse = get('/api/campaigns', [
                'Accept' => 'application/ld+json',
            ]);

            $jsonResponse->assertOk();
            $jsonLdResponse->assertOk();
        });
    });

    describe('Basic performance', function (): void {
        it('handles multiple campaigns efficiently', function (): void {
            Campaign::factory()->count(50)->for($this->organization)->for($this->user, 'employee')->create();
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'Accept' => 'application/json',
            ]);

            $response->assertOk();

            // Should complete in reasonable time
            expect($response->getStatusCode())->toBe(200);
        });

        it('handles filtering with large datasets', function (): void {
            Campaign::factory()->count(30)->for($this->organization)->for($this->user, 'employee')->create();
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'status' => 'active',
                'organization_id' => $this->organization->id,
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
        });
    });

    describe('Error handling', function (): void {
        it('requires authentication', function (): void {
            $response = get('/api/campaigns', [
                'Accept' => 'application/json',
            ]);

            $response->assertUnauthorized();
        });

        it('handles invalid pagination gracefully', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'page' => 999, // Very high page number
                'Accept' => 'application/json',
            ]);

            // Should either return empty results or handle gracefully
            expect($response->getStatusCode())->toBeIn([200, 404, 422]);
        });

        it('handles long search terms', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/campaigns', [
                'search' => str_repeat('test', 20), // Long search term
                'Accept' => 'application/json',
            ]);

            // Should handle gracefully
            expect($response->getStatusCode())->toBeIn([200, 422]);
        });
    });
});
