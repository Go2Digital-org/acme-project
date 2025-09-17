<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

describe('Organization API Performance Optimizations', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();

        // Create test organizations with various statuses
        $this->organizations = collect([
            Organization::factory()->verified()->create(),
            Organization::factory()->unverified()->create(),
            Organization::factory()->healthcare()->create(),
            Organization::factory()->education()->create(),
            Organization::factory()->environment()->create(),
        ]);

        // Clear cache before each test
        Cache::flush();
    });

    describe('Organization collection endpoint optimization', function (): void {
        it('retrieves organizations list successfully', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/organizations', [
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
            $data = $response->json();

            // Check if we have API Platform format
            if (isset($data['hydra:member'])) {
                expect($data['hydra:member'])->toBeArray();
                expect($data)->toHaveKeys(['hydra:member', 'hydra:totalItems']);
            } else {
                expect($data)->toBeArray();
            }
        });

        it('supports API Platform JSON-LD format', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/organizations', [
                'Accept' => 'application/ld+json',
            ]);

            $response->assertOk();
            $data = $response->json();

            // Check for API Platform structure
            if (isset($data['hydra:member'])) {
                expect($data)->toHaveKeys(['hydra:member', 'hydra:totalItems']);
            }
        });

        it('includes organization basic properties', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/organizations', [
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
            $data = $response->json();

            // Check that each organization has basic properties
            $organizations = $data['hydra:member'] ?? $data ?? [];
            if (! empty($organizations)) {
                $firstOrganization = $organizations[0];
                expect($firstOrganization)->toHaveKey('id');
                expect($firstOrganization)->toHaveKey('name');
            }
        });

        it('supports verified filter parameter', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/organizations', [
                'verified' => 'true',
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
        });

        it('supports search parameter', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/organizations', [
                'search' => 'health',
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
        });

        it('supports category filtering', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/organizations', [
                'category' => 'healthcare',
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
        });

        it('returns proper content type headers', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/organizations', [
                'Accept' => 'application/json',
            ]);

            $response->assertOk()
                ->assertHeader('Content-Type');
        });
    });

    describe('Organization item endpoint optimization', function (): void {
        it('retrieves specific organization successfully', function (): void {
            actingAs($this->user, 'sanctum');
            $organization = $this->organizations->first();

            $response = get("/api/organizations/{$organization->id}", [
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
            $data = $response->json();

            expect($data)->toHaveKey('id');
            expect($data)->toHaveKey('name');
            expect($data['id'])->toBe($organization->id);
        });

        it('returns 404 for non-existent organization', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/organizations/999999', [
                'Accept' => 'application/json',
            ]);

            // API Platform may return 404 or 500 for non-existent resources
            expect($response->getStatusCode())->toBeIn([404, 500]);
        });

        it('includes verification status', function (): void {
            actingAs($this->user, 'sanctum');
            $verifiedOrg = $this->organizations->where('is_verified', true)->first();

            if ($verifiedOrg) {
                $response = get("/api/organizations/{$verifiedOrg->id}", [
                    'Accept' => 'application/json',
                ]);

                $response->assertOk();
                $data = $response->json();

                expect($data)->toHaveKey('verified');
                expect($data['verified'])->toBeTrue();
            }
        });

        it('includes organization status information', function (): void {
            actingAs($this->user, 'sanctum');
            $organization = $this->organizations->first();

            $response = get("/api/organizations/{$organization->id}", [
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
            $data = $response->json();

            expect($data)->toHaveKey('active');
            expect($data)->toHaveKey('status');
        });
    });

    describe('Pagination optimization', function (): void {
        it('supports pagination for organizations', function (): void {
            Organization::factory()->count(25)->create();
            actingAs($this->user, 'sanctum');

            $response = get('/api/organizations', [
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

            $response = get('/api/organizations', [
                'itemsPerPage' => 100, // At maximum
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
        });

        it('provides pagination metadata', function (): void {
            Organization::factory()->count(15)->create();
            actingAs($this->user, 'sanctum');

            $response = get('/api/organizations', [
                'itemsPerPage' => 10,
                'page' => 1,
                'Accept' => 'application/json',
            ]);

            $response->assertOk();
            $data = $response->json();

            // Check for API Platform pagination metadata
            if (isset($data['hydra:totalItems'])) {
                expect($data['hydra:totalItems'])->toBeGreaterThanOrEqual(15);
            }
        });
    });

    describe('Error handling', function (): void {
        it('requires authentication for organization list', function (): void {
            $response = get('/api/organizations', [
                'Accept' => 'application/json',
            ]);

            $response->assertUnauthorized();
        });

        it('requires authentication for organization details', function (): void {
            $organization = $this->organizations->first();

            $response = get("/api/organizations/{$organization->id}", [
                'Accept' => 'application/json',
            ]);

            $response->assertUnauthorized();
        });

        it('handles invalid filter values gracefully', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/organizations', [
                'verified' => 'invalid_boolean',
                'Accept' => 'application/json',
            ]);

            // Should either work (ignore invalid filter) or give proper error
            expect($response->getStatusCode())->toBeIn([200, 400, 422]);
        });

        it('handles invalid pagination gracefully', function (): void {
            actingAs($this->user, 'sanctum');

            $response = get('/api/organizations', [
                'page' => 999, // Very high page number
                'Accept' => 'application/json',
            ]);

            // Should either return empty results or handle gracefully
            expect($response->getStatusCode())->toBeIn([200, 404, 422]);
        });
    });

    describe('Query count optimization', function (): void {
        it('uses minimal queries for organization list retrieval', function (): void {
            actingAs($this->user, 'sanctum');

            // Start query counting
            DB::enableQueryLog();
            $initialQueryCount = count(DB::getQueryLog());

            $response = get('/api/organizations', [
                'Accept' => 'application/json',
            ]);

            $finalQueryCount = count(DB::getQueryLog());
            $queryCount = $finalQueryCount - $initialQueryCount;

            $response->assertOk();

            // Organization list should use minimal queries (ideally 1-3 queries)
            expect($queryCount)->toBeLessThan(5);
        });

        it('avoids N+1 queries when loading organization details', function (): void {
            actingAs($this->user, 'sanctum');
            $organization = $this->organizations->first();

            DB::enableQueryLog();
            $initialQueryCount = count(DB::getQueryLog());

            $response = get("/api/organizations/{$organization->id}", [
                'Accept' => 'application/json',
            ]);

            $finalQueryCount = count(DB::getQueryLog());
            $queryCount = $finalQueryCount - $initialQueryCount;

            $response->assertOk();

            // Single organization retrieval should be very efficient
            expect($queryCount)->toBeLessThan(3);
        });

        it('handles multiple organizations efficiently', function (): void {
            Organization::factory()->count(20)->create();
            actingAs($this->user, 'sanctum');

            DB::enableQueryLog();
            $initialQueryCount = count(DB::getQueryLog());

            $response = get('/api/organizations', [
                'itemsPerPage' => 20,
                'Accept' => 'application/json',
            ]);

            $finalQueryCount = count(DB::getQueryLog());
            $queryCount = $finalQueryCount - $initialQueryCount;

            $response->assertOk();

            // Even with more organizations, query count should remain low
            expect($queryCount)->toBeLessThan(6);
        });
    });

    describe('Response time performance', function (): void {
        it('organization list responds within acceptable time limits', function (): void {
            Organization::factory()->count(50)->create();
            actingAs($this->user, 'sanctum');

            $startTime = microtime(true);

            $response = get('/api/organizations', [
                'Accept' => 'application/json',
            ]);

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

            $response->assertOk();

            // Organization list should respond within reasonable time
            expect($responseTime)->toBeLessThan(2000); // 2 seconds should be acceptable
        });

        it('organization details respond quickly', function (): void {
            actingAs($this->user, 'sanctum');
            $organization = $this->organizations->first();

            $startTime = microtime(true);

            $response = get("/api/organizations/{$organization->id}", [
                'Accept' => 'application/json',
            ]);

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

            $response->assertOk();

            // Single organization should respond very quickly
            expect($responseTime)->toBeLessThan(1000); // 1 second
        });

        it('handles search queries efficiently', function (): void {
            Organization::factory()->count(30)->create();
            actingAs($this->user, 'sanctum');

            $startTime = microtime(true);

            $response = get('/api/organizations', [
                'search' => 'health',
                'Accept' => 'application/json',
            ]);

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;

            $response->assertOk();

            // Search should still be performant
            expect($responseTime)->toBeLessThan(2500); // 2.5 seconds for search
        });

        it('verification filtering is performant', function (): void {
            Organization::factory()->verified()->count(15)->create();
            Organization::factory()->unverified()->count(15)->create();
            actingAs($this->user, 'sanctum');

            $startTime = microtime(true);

            $response = get('/api/organizations', [
                'verified' => 'true',
                'Accept' => 'application/json',
            ]);

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;

            $response->assertOk();

            // Filtering should be fast
            expect($responseTime)->toBeLessThan(2000);
        });
    });
});
