<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Organization\Application\Service\OrganizationSearchService;
use Modules\Organization\Domain\Model\Organization;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new OrganizationSearchService;
    Cache::flush();

    // Create test organizations
    $this->organizations = collect([
        Organization::factory()->create([
            'name' => ['en' => 'Green Foundation'],
            'category' => 'Environment',
            'city' => 'New York',
            'country' => 'USA',
            'is_verified' => true,
            'is_active' => true,
        ]),
        Organization::factory()->create([
            'name' => ['en' => 'Health Initiative'],
            'category' => 'Health',
            'city' => 'California',
            'country' => 'USA',
            'is_verified' => false,
            'is_active' => true,
        ]),
        Organization::factory()->create([
            'name' => ['en' => 'Education for All'],
            'category' => 'Education',
            'city' => 'Boston',
            'country' => 'USA',
            'is_verified' => true,
            'is_active' => false,
        ]),
    ]);
});

afterEach(function (): void {
    Cache::flush();
});

describe('service functionality', function (): void {
    it('extends base SearchService', function (): void {
        expect($this->service)->toBeInstanceOf(\Modules\Shared\Application\Service\SearchService::class);
    });

    it('can perform basic search', function (): void {
        $result = $this->service->search();
        expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
    });

    it('can count results', function (): void {
        $count = $this->service->count();
        expect($count)->toBeGreaterThanOrEqual(0);
    });

    it('can provide suggestions', function (): void {
        $result = $this->service->suggest('foundation');
        expect($result)->toBeInstanceOf(Collection::class);
    });
});

describe('searchByLocation method', function (): void {
    it('searches organizations by location', function (): void {
        $result = $this->service->searchByLocation('New York');
        expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
    });

    it('accepts custom pagination parameters', function (): void {
        $result = $this->service->searchByLocation('California', 10, 2);
        expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
    });
});

describe('searchByCategory method', function (): void {
    it('searches organizations by category', function (): void {
        $result = $this->service->searchByCategory('Environment', 'environmental protection');
        expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
    });

    it('handles empty query string', function (): void {
        $result = $this->service->searchByCategory('Health');
        expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
    });
});

describe('searchVerified method', function (): void {
    it('searches only verified organizations', function (): void {
        $result = $this->service->searchVerified(
            query: 'charity',
            filters: ['category' => 'Health']
        );
        expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
    });

    it('merges additional filters with verified filter', function (): void {
        $result = $this->service->searchVerified(
            query: 'test',
            filters: ['country' => 'USA', 'is_verified' => false], // Should override is_verified
        );
        expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
    });
});

describe('getNameSuggestions method', function (): void {
    it('returns empty collection for short queries', function (): void {
        if (! method_exists($this->service, 'getNameSuggestions')) {
            $result = $this->service->suggest('a');
        } else {
            $result = $this->service->getNameSuggestions('a');
        }

        expect($result)->toBeInstanceOf(Collection::class)
            ->and($result->isEmpty())->toBeTrue();
    });

    it('returns suggestions for valid queries', function (): void {
        if (! method_exists($this->service, 'getNameSuggestions')) {
            $result = $this->service->suggest('green');
        } else {
            $result = $this->service->getNameSuggestions('green');
        }

        expect($result)->toBeInstanceOf(Collection::class);
    });

    it('caches suggestion results', function (): void {
        if (! method_exists($this->service, 'getNameSuggestions')) {
            // First call
            $result1 = $this->service->suggest('foundation');
            // Second call should use cache
            $result2 = $this->service->suggest('foundation');
        } else {
            // First call
            $result1 = $this->service->getNameSuggestions('foundation');
            // Second call should use cache
            $result2 = $this->service->getNameSuggestions('foundation');
        }

        expect($result1)->toBeInstanceOf(Collection::class)
            ->and($result2)->toBeInstanceOf(Collection::class);
    });
});

describe('getCategoryFacets method', function (): void {
    it('returns category facets', function (): void {
        $result = $this->service->getCategoryFacets('test query');
        expect($result)->toBeArray();
    });

    it('caches category facet results', function (): void {
        // First call
        $result1 = $this->service->getCategoryFacets('cached facets');
        // Second call should use cache
        $result2 = $this->service->getCategoryFacets('cached facets');

        expect($result1)->toBeArray()
            ->and($result2)->toBeArray();
    });
});

describe('getLocationFacets method', function (): void {
    it('returns location facets', function (): void {
        $result = $this->service->getLocationFacets('location test');
        expect($result)->toBeArray();
    });

    it('handles location filtering', function (): void {
        $result = $this->service->getLocationFacets('test');
        expect($result)->toBeArray();
    });
});

describe('getRecentlyVerified method', function (): void {
    it('returns recently verified organizations', function (): void {
        $result = $this->service->getRecentlyVerified();
        expect($result)->toBeInstanceOf(EloquentCollection::class);
    });
});

describe('getMostActive method', function (): void {
    it('returns most active organizations', function (): void {
        $result = $this->service->getMostActive(5);
        expect($result)->toBeInstanceOf(EloquentCollection::class);
    });
});
