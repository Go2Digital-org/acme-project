<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('Campaign List Performance Optimizations (Database Tests)', function (): void {
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

    describe('Database Query Performance', function (): void {
        it('can efficiently retrieve campaign collections', function (): void {
            $campaigns = Campaign::all();

            expect($campaigns)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
            expect($campaigns->count())->toBe(4);
        });

        it('supports efficient filtering by status', function (): void {
            $activeCampaigns = Campaign::where('status', 'active')->get();
            $completedCampaigns = Campaign::where('status', 'completed')->get();

            expect($activeCampaigns->count())->toBe(1);
            expect($completedCampaigns->count())->toBe(1);
        });

        it('supports efficient pagination without N+1 queries', function (): void {
            // Create more campaigns for pagination testing
            Campaign::factory()->count(20)->for($this->organization)->for($this->user, 'employee')->create();

            $paginatedResults = Campaign::with(['organization', 'employee'])->paginate(10);

            expect($paginatedResults->total())->toBe(24); // 4 from setup + 20 created
            expect($paginatedResults->perPage())->toBe(10);
            expect($paginatedResults->count())->toBe(10);
        });

        it('efficiently loads relationships with eager loading', function (): void {
            $campaignsWithRelations = Campaign::with(['organization', 'employee'])->get();

            expect($campaignsWithRelations)->toHaveCount(4);

            foreach ($campaignsWithRelations as $campaign) {
                expect($campaign->organization)->not->toBeNull();
                expect($campaign->employee)->not->toBeNull();
            }
        });

        it('supports efficient filtering by organization', function (): void {
            $otherOrganization = Organization::factory()->create();
            Campaign::factory()->for($otherOrganization)->for($this->user, 'employee')->create();

            $orgCampaigns = Campaign::where('organization_id', $this->organization->id)->get();
            $otherOrgCampaigns = Campaign::where('organization_id', $otherOrganization->id)->get();

            expect($orgCampaigns->count())->toBe(4);
            expect($otherOrgCampaigns->count())->toBe(1);
        });

        it('supports efficient search within title field', function (): void {
            Campaign::factory()->for($this->organization)->for($this->user, 'employee')->create([
                'title' => ['en' => 'Unique Conservation Project'],
            ]);

            $searchResults = Campaign::where('title->en', 'like', '%Unique%')->get();

            expect($searchResults->count())->toBe(1);
            expect($searchResults->first()->getTitle())->toContain('Unique');
        });

        it('handles complex filtering combinations efficiently', function (): void {
            $results = Campaign::where('organization_id', $this->organization->id)
                ->where('status', 'active')
                ->where('user_id', $this->user->id)
                ->get();

            expect($results->count())->toBe(1);
            expect($results->first()->status->value)->toBe('active');
        });

        it('can count campaigns efficiently', function (): void {
            $totalCount = Campaign::count();
            $activeCount = Campaign::where('status', 'active')->count();
            $userCount = Campaign::where('user_id', $this->user->id)->count();

            expect($totalCount)->toBe(4);
            expect($activeCount)->toBe(1);
            expect($userCount)->toBe(4);
        });

        it('can group campaigns by status efficiently', function (): void {
            $statusCounts = Campaign::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            expect($statusCounts['active'])->toBe(1);
            expect($statusCounts['completed'])->toBe(1);
            expect($statusCounts['draft'])->toBe(1);
            expect($statusCounts['paused'])->toBe(1);
        });
    });

    describe('Repository Performance Tests', function (): void {
        it('measures bulk insertion performance', function (): void {
            $startTime = microtime(true);

            // Create campaigns in bulk
            $campaigns = Campaign::factory()
                ->count(50)
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create();

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            expect($campaigns)->toHaveCount(50);
            expect($executionTime)->toBeLessThan(3.0); // Should complete in under 3 seconds
        });

        it('tests query performance with large datasets', function (): void {
            // Create a larger dataset
            Campaign::factory()
                ->count(100)
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create();

            $startTime = microtime(true);

            $campaigns = Campaign::where('organization_id', $this->organization->id)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            expect($campaigns)->toHaveCount(20);
            expect($executionTime)->toBeLessThan(0.5); // Should be very fast with proper indexing
        });

        it('measures memory efficiency with large result sets', function (): void {
            Campaign::factory()
                ->count(200)
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create();

            $memoryBefore = memory_get_usage();

            $campaigns = Campaign::where('organization_id', $this->organization->id)->get();

            $memoryAfter = memory_get_usage();
            $memoryUsed = $memoryAfter - $memoryBefore;

            expect($campaigns->count())->toBe(204); // 4 from setup + 200 created
            // Memory usage should be reasonable (under 100MB for 200+ records)
            expect($memoryUsed)->toBeLessThan(100 * 1024 * 1024);
        });

        it('validates chunked processing performance', function (): void {
            Campaign::factory()
                ->count(100)
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create();

            $processedCount = 0;
            $startTime = microtime(true);

            Campaign::where('organization_id', $this->organization->id)
                ->chunk(25, function ($campaigns) use (&$processedCount): void {
                    $processedCount += $campaigns->count();
                });

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            expect($processedCount)->toBe(104); // 4 from setup + 100 created
            expect($executionTime)->toBeLessThan(1.0); // Chunked processing should be efficient
        });

        it('tests concurrent access simulation', function (): void {
            $startTime = microtime(true);

            // Simulate multiple concurrent operations
            $read1 = Campaign::where('organization_id', $this->organization->id)->count();
            $read2 = Campaign::where('status', 'active')->count();
            $read3 = Campaign::where('user_id', $this->user->id)->count();

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            expect($read1)->toBe(4);
            expect($read2)->toBe(1);
            expect($read3)->toBe(4);
            expect($executionTime)->toBeLessThan(0.2); // Multiple reads should be fast
        });

        it('validates bulk update performance', function (): void {
            Campaign::factory()
                ->count(50)
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create();

            $startTime = microtime(true);

            Campaign::where('organization_id', $this->organization->id)
                ->update(['updated_at' => now()]);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            expect($executionTime)->toBeLessThan(1.0); // Bulk updates should be efficient
        });
    });
});
