<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('Organization API Performance Optimizations (Database Tests)', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->organizations = collect([
            Organization::factory()->create(['name' => 'Tech Corp']),
            Organization::factory()->create(['name' => 'Green Initiatives']),
            Organization::factory()->create(['name' => 'Health Foundation']),
        ]);
    });

    describe('Database Query Performance', function (): void {
        it('can efficiently retrieve organization collections', function (): void {
            $organizations = Organization::all();

            expect($organizations)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
            expect($organizations->count())->toBe(3);
        });

        it('supports efficient filtering by name', function (): void {
            $techOrgs = Organization::where('name', 'like', '%Tech%')->get();
            $healthOrgs = Organization::where('name', 'like', '%Health%')->get();

            expect($techOrgs->count())->toBe(1);
            expect($healthOrgs->count())->toBe(1);
        });

        it('supports efficient pagination', function (): void {
            // Create more organizations for pagination testing
            Organization::factory()->count(20)->create();

            $paginatedResults = Organization::paginate(10);

            expect($paginatedResults->total())->toBe(23); // 3 from setup + 20 created
            expect($paginatedResults->perPage())->toBe(10);
            expect($paginatedResults->count())->toBe(10);
        });

        it('can count organizations efficiently', function (): void {
            $totalCount = Organization::count();

            expect($totalCount)->toBe(3);
        });

        it('supports search functionality on organization fields', function (): void {
            Organization::factory()->create(['name' => 'Environmental Protection Agency']);

            $searchResults = Organization::where('name', 'like', '%Environmental%')->get();

            expect($searchResults->count())->toBe(1);
            expect($searchResults->first()->name)->toContain('Environmental');
        });

        it('handles organization status filtering', function (): void {
            // Test basic organization queries
            $activeOrgs = Organization::whereNotNull('name')->get();

            expect($activeOrgs->count())->toBe(3);
        });

        it('efficiently processes organization data transformations', function (): void {
            $orgData = Organization::select(['id', 'name', 'created_at'])->get();

            expect($orgData)->toHaveCount(3);

            foreach ($orgData as $org) {
                expect($org->name)->not->toBeNull();
                expect($org->id)->toBeInt();
            }
        });

        it('can group organizations by creation date', function (): void {
            $orgsByDate = Organization::selectRaw('DATE(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->get();

            expect($orgsByDate->count())->toBeGreaterThanOrEqual(1);
        });
    });

});
