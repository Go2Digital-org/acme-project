<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('Campaign Search Tests (Database)', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();

        // Create test campaigns with searchable content
        Campaign::factory()
            ->for($this->organization)
            ->for($this->user, 'employee')
            ->create(['title' => ['en' => 'Environmental Conservation Project']]);

        Campaign::factory()
            ->for($this->organization)
            ->for($this->user, 'employee')
            ->create(['title' => ['en' => 'Healthcare Initiative for Rural Areas']]);

        Campaign::factory()
            ->for($this->organization)
            ->for($this->user, 'employee')
            ->create(['title' => ['en' => 'Education Support Program']]);
    });

    describe('Search Functionality', function (): void {
        it('can search campaigns by title', function (): void {
            $results = Campaign::where('title->en', 'like', '%Environmental%')->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->getTitle())->toContain('Environmental');
        });

        it('handles empty search results', function (): void {
            $results = Campaign::where('title->en', 'like', '%NonExistent%')->get();

            expect($results)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
            expect($results->count())->toBe(0);
            expect($results->isEmpty())->toBeTrue();
        });

        it('can search across multiple fields', function (): void {
            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create([
                    'title' => ['en' => 'Special Project'],
                    'description' => ['en' => 'This project focuses on healthcare improvements'],
                ]);

            $titleResults = Campaign::where('title->en', 'like', '%Special%')->get();
            $descriptionResults = Campaign::where('description->en', 'like', '%healthcare%')->get();

            expect($titleResults)->toHaveCount(1);
            expect($descriptionResults)->toHaveCount(1);
        });

        it('supports case-insensitive search', function (): void {
            $upperCaseResults = Campaign::whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, "$.en"))) LIKE ?', ['%environmental%'])->get();
            $lowerCaseResults = Campaign::whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(title, "$.en"))) LIKE ?', ['%environmental%'])->get();

            expect($upperCaseResults)->toHaveCount(1);
            expect($lowerCaseResults)->toHaveCount(1);
        });

        it('can filter search results by date ranges', function (): void {
            $recentCampaigns = Campaign::where('created_at', '>=', now()->subMonth())->get();

            expect($recentCampaigns->count())->toBeGreaterThanOrEqual(3);
        });

        it('can combine search with status filtering', function (): void {
            // Use unique identifiers to avoid test interference
            $uniqueId = uniqid();

            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->active()
                ->create(['title' => ['en' => "Active HealthProject {$uniqueId}"]]);

            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->draft()
                ->create(['title' => ['en' => "Draft HealthProject {$uniqueId}"]]);

            $activeHealthCampaigns = Campaign::where('title->en', 'like', "%HealthProject {$uniqueId}%")
                ->where('status', 'active')
                ->get();

            expect($activeHealthCampaigns)->toHaveCount(1);
            expect($activeHealthCampaigns->first()->status->value)->toBe('active');
        });

        it('handles search with organization filtering', function (): void {
            $otherOrganization = Organization::factory()->create();
            Campaign::factory()
                ->for($otherOrganization)
                ->for($this->user, 'employee')
                ->create(['title' => ['en' => 'Other Org Environmental Project']]);

            $orgResults = Campaign::where('title->en', 'like', '%Environmental%')
                ->where('organization_id', $this->organization->id)
                ->get();

            expect($orgResults)->toHaveCount(1);
        });
    });

    describe('Pagination', function (): void {
        it('supports paginated search results', function (): void {
            // Create more campaigns for pagination
            Campaign::factory()
                ->count(20)
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create();

            $paginatedResults = Campaign::where('organization_id', $this->organization->id)
                ->paginate(10);

            expect($paginatedResults->total())->toBe(23); // 3 from setup + 20 created
            expect($paginatedResults->perPage())->toBe(10);
            expect($paginatedResults->count())->toBe(10);
        });
    });

    describe('Authentication Requirements', function (): void {
        it('validates search permissions at model level', function (): void {
            // Test that we can only see campaigns for our organization
            $userCampaigns = Campaign::where('organization_id', $this->organization->id)->get();
            $allCampaigns = Campaign::all();

            expect($userCampaigns)->toHaveCount(3);
            expect($allCampaigns->count())->toBeGreaterThanOrEqual(3);
        });
    });

    describe('Advanced Search Service Tests', function (): void {
        it('handles multiple search terms with AND logic', function (): void {
            Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create(['title' => ['en' => 'Environmental Healthcare Initiative']]);

            $results = Campaign::where('title->en', 'like', '%Environmental%')
                ->where('title->en', 'like', '%Healthcare%')
                ->get();

            expect($results)->toHaveCount(1);
            expect($results->first()->getTitle())->toContain('Environmental Healthcare');
        });

        it('supports search term highlighting simulation', function (): void {
            $campaign = Campaign::where('title->en', 'like', '%Environmental%')->first();

            $title = $campaign->getTitle();
            $highlightedTitle = str_replace('Environmental', '<mark>Environmental</mark>', $title);

            expect($highlightedTitle)->toContain('<mark>Environmental</mark>');
        });

        it('validates search result sorting by relevance simulation', function (): void {
            // Create campaigns with different relevance scores
            $exactMatch = Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create(['title' => ['en' => 'Environment']]);

            $partialMatch = Campaign::factory()
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create(['title' => ['en' => 'Environmental Conservation Project']]);

            $results = Campaign::where('title->en', 'like', '%Environment%')
                ->orderByRaw("CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) = 'Environment' THEN 1 ELSE 2 END")
                ->get();

            expect($results->first()->getTitle())->toBe('Environment');
        });

        it('handles search with custom filters and sorting', function (): void {
            Campaign::factory()
                ->count(5)
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create();

            $filteredResults = Campaign::where('organization_id', $this->organization->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            expect($filteredResults)->toHaveCount(5);
            expect($filteredResults->first()->created_at)
                ->toBeGreaterThanOrEqual($filteredResults->last()->created_at);
        });

        it('tests search performance with indexes', function (): void {
            $startTime = microtime(true);

            Campaign::where('organization_id', $this->organization->id)
                ->where('title->en', 'like', '%Environmental%')
                ->get();

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            // Search should complete quickly
            expect($executionTime)->toBeLessThan(0.5);
        });

        it('validates search facets and aggregations', function (): void {
            // Create campaigns with different statuses for faceting
            Campaign::factory()->active()->count(2)
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create();

            Campaign::factory()->draft()->count(3)
                ->for($this->organization)
                ->for($this->user, 'employee')
                ->create();

            $statusCounts = Campaign::selectRaw('status, count(*) as count')
                ->where('organization_id', $this->organization->id)
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->status->value => $item->count];
                });

            expect($statusCounts['active'])->toBeGreaterThanOrEqual(2);
            expect($statusCounts['draft'])->toBeGreaterThanOrEqual(3);
        });
    });
});
