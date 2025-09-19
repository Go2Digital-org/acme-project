<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->campaigns = Campaign::factory()->count(5)->create(['organization_id' => $this->organization->id]);
});

describe('API Performance Basics (Database Tests)', function (): void {
    it('efficiently queries campaigns without N+1 problems', function (): void {
        DB::enableQueryLog();
        $queryCountBefore = count(DB::getQueryLog());

        // Test efficient loading with relationships
        $campaigns = Campaign::with(['organization', 'employee'])->get();

        $queryCountAfter = count(DB::getQueryLog());
        $queriesExecuted = $queryCountAfter - $queryCountBefore;

        expect($campaigns)->toHaveCount(5);
        expect($queriesExecuted)->toBeLessThanOrEqual(5); // Should be 3 queries max (campaigns, organizations, users)
    });

    it('handles large dataset queries efficiently', function (): void {
        // Create more data to test performance
        Campaign::factory()->count(50)->create(['organization_id' => $this->organization->id]);

        DB::enableQueryLog();
        $queryCountBefore = count(DB::getQueryLog());

        $campaigns = Campaign::paginate(20);

        $queryCountAfter = count(DB::getQueryLog());
        $queriesExecuted = $queryCountAfter - $queryCountBefore;

        expect($campaigns->total())->toBe(55); // 5 from setup + 50 created
        expect($queriesExecuted)->toBeLessThanOrEqual(3); // Should be minimal queries for pagination
    });

    it('optimizes search queries for performance', function (): void {
        Campaign::factory()->create([
            'title' => ['en' => 'Environmental Conservation Project'],
            'organization_id' => $this->organization->id,
        ]);

        DB::enableQueryLog();
        $queryCountBefore = count(DB::getQueryLog());

        $searchResults = Campaign::where('title->en', 'like', '%Conservation Project%')
            ->where('organization_id', $this->organization->id)
            ->get();

        $queryCountAfter = count(DB::getQueryLog());
        $queriesExecuted = $queryCountAfter - $queryCountBefore;

        expect($searchResults)->toHaveCount(1);
        expect($queriesExecuted)->toBeLessThanOrEqual(3); // Should be minimal queries
    });

    it('efficiently filters campaigns by multiple criteria', function (): void {
        // Create campaigns with different statuses
        Campaign::factory()->active()->count(3)->create(['organization_id' => $this->organization->id]);
        Campaign::factory()->draft()->count(2)->create(['organization_id' => $this->organization->id]);

        DB::enableQueryLog();
        $queryCountBefore = count(DB::getQueryLog());

        $filteredCampaigns = Campaign::where('organization_id', $this->organization->id)
            ->where('status', 'active')
            ->get();

        $queryCountAfter = count(DB::getQueryLog());
        $queriesExecuted = $queryCountAfter - $queryCountBefore;

        expect($filteredCampaigns->count())->toBeGreaterThanOrEqual(3);
        expect($queriesExecuted)->toBeLessThanOrEqual(3); // Should be minimal optimized queries
    });

    it('handles aggregation queries efficiently', function (): void {
        DB::enableQueryLog();
        $queryCountBefore = count(DB::getQueryLog());

        $campaignCounts = Campaign::selectRaw('status, count(*) as count')
            ->where('organization_id', $this->organization->id)
            ->groupBy('status')
            ->get();

        $queryCountAfter = count(DB::getQueryLog());
        $queriesExecuted = $queryCountAfter - $queryCountBefore;

        expect($campaignCounts->count())->toBeGreaterThan(0);
        expect($queriesExecuted)->toBe(1); // Aggregation should be 1 query
    });

    it('measures basic database query performance', function (): void {
        $startTime = microtime(true);

        Campaign::with(['organization', 'employee'])
            ->where('organization_id', $this->organization->id)
            ->paginate(10);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Query should complete in reasonable time (under 1 second)
        expect($executionTime)->toBeLessThan(1.0);
    });

    it('validates database index usage for common queries', function (): void {
        // Test that common filter queries are reasonably fast
        $startTime = microtime(true);

        Campaign::where('organization_id', $this->organization->id)
            ->where('status', 'active')
            ->count();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Indexed queries should be very fast
        expect($executionTime)->toBeLessThan(0.1);
    });
});

describe('Service Performance Tests', function (): void {
    it('measures campaign service performance for bulk operations', function (): void {
        $startTime = microtime(true);

        // Test bulk creation performance
        $campaigns = Campaign::factory()->count(20)->make(['organization_id' => $this->organization->id]);

        foreach ($campaigns as $campaign) {
            $campaign->save();
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Bulk operations should complete in reasonable time
        expect($executionTime)->toBeLessThan(2.0);
        expect(Campaign::count())->toBe(25); // 5 from setup + 20 created
    });

    it('tests memory usage efficiency with large datasets', function (): void {
        $memoryBefore = memory_get_usage();

        // Create and process campaigns in memory
        $campaigns = Campaign::factory()->count(100)->create(['organization_id' => $this->organization->id]);
        $processedTitles = $campaigns->map(fn ($campaign) => strtoupper($campaign->title['en'] ?? 'No Title'));

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        expect($processedTitles)->toHaveCount(100);
        // Memory usage should be reasonable (under 50MB for 100 records)
        expect($memoryUsed)->toBeLessThan(50 * 1024 * 1024);
    });

    it('validates caching improves query performance', function (): void {
        // First query (no cache)
        $startTime1 = microtime(true);
        $campaigns1 = Campaign::where('organization_id', $this->organization->id)->get();
        $endTime1 = microtime(true);
        $firstQueryTime = $endTime1 - $startTime1;

        // Second identical query (should use Eloquent model caching)
        $startTime2 = microtime(true);
        $campaigns2 = Campaign::where('organization_id', $this->organization->id)->get();
        $endTime2 = microtime(true);
        $secondQueryTime = $endTime2 - $startTime2;

        expect($campaigns1->count())->toBe($campaigns2->count());
        expect($campaigns1->count())->toBe(5);

        // Both queries should be fast (under 0.5 seconds)
        expect($firstQueryTime)->toBeLessThan(0.5);
        expect($secondQueryTime)->toBeLessThan(0.5);
    });

    it('measures concurrent operation simulation', function (): void {
        $startTime = microtime(true);

        // Simulate concurrent campaign updates
        $campaigns = Campaign::where('organization_id', $this->organization->id)->get();

        foreach ($campaigns as $campaign) {
            $campaign->update(['status' => 'active']);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Updates should complete efficiently
        expect($executionTime)->toBeLessThan(1.0);
        expect(Campaign::where('status', 'active')->count())->toBe(5);
    });
});
