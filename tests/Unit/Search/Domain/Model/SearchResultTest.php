<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Modules\Search\Domain\Model\SearchResult;

describe('SearchResult Model', function (): void {
    it('creates search result with required parameters', function (): void {
        $hits = [
            ['id' => 1, 'title' => 'Campaign 1', 'type' => 'campaign'],
            ['id' => 2, 'title' => 'Campaign 2', 'type' => 'campaign'],
        ];

        $result = new SearchResult(
            hits: $hits,
            totalHits: 2,
            processingTime: 15.5
        );

        expect($result->hits)->toBe($hits)
            ->and($result->totalHits)->toBe(2)
            ->and($result->processingTime)->toBe(15.5)
            ->and($result->facets)->toBe([]) // Default
            ->and($result->query)->toBe('') // Default
            ->and($result->limit)->toBe(20) // Default
            ->and($result->offset)->toBe(0) // Default
            ->and($result->estimatedTotalHits)->toBeNull() // Default
            ->and($result->suggestions)->toBe([]) // Default
            ->and($result->engine)->toBeNull(); // Default
    });

    it('creates search result with all parameters', function (): void {
        $hits = [
            ['id' => 1, 'title' => 'Environmental Campaign', 'score' => 0.95],
            ['id' => 2, 'title' => 'Climate Action', 'score' => 0.87],
        ];
        $facets = [
            'category' => ['environment' => 15, 'health' => 8],
            'status' => ['active' => 20, 'draft' => 3],
        ];
        $suggestions = ['environment', 'environmental', 'ecology'];

        $result = new SearchResult(
            hits: $hits,
            totalHits: 23,
            processingTime: 8.2,
            facets: $facets,
            query: 'environment',
            limit: 10,
            offset: 20,
            estimatedTotalHits: 25,
            suggestions: $suggestions,
            engine: 'meilisearch'
        );

        expect($result->hits)->toBe($hits)
            ->and($result->totalHits)->toBe(23)
            ->and($result->processingTime)->toBe(8.2)
            ->and($result->facets)->toBe($facets)
            ->and($result->query)->toBe('environment')
            ->and($result->limit)->toBe(10)
            ->and($result->offset)->toBe(20)
            ->and($result->estimatedTotalHits)->toBe(25)
            ->and($result->suggestions)->toBe($suggestions)
            ->and($result->engine)->toBe('meilisearch');
    });

    it('gets hits as collection', function (): void {
        $hits = [
            ['id' => 1, 'title' => 'First'],
            ['id' => 2, 'title' => 'Second'],
            ['id' => 3, 'title' => 'Third'],
        ];

        $result = new SearchResult($hits, 3, 5.0);
        $collection = $result->getHitsCollection();

        expect($collection)->toBeInstanceOf(Collection::class)
            ->and($collection->count())->toBe(3)
            ->and($collection->first())->toBe(['id' => 1, 'title' => 'First'])
            ->and($collection->last())->toBe(['id' => 3, 'title' => 'Third']);
    });

    it('detects if there are results', function (): void {
        $resultWithHits = new SearchResult([['id' => 1]], 1, 5.0);
        $resultWithoutHits = new SearchResult([], 0, 2.0);

        expect($resultWithHits->hasResults())->toBeTrue()
            ->and($resultWithoutHits->hasResults())->toBeFalse();
    });

    it('calculates current page correctly', function (): void {
        $hits = [['id' => 1]];

        $page1Result = new SearchResult($hits, 50, 5.0, [], '', 20, 0);
        $page2Result = new SearchResult($hits, 50, 5.0, [], '', 20, 20);
        $page3Result = new SearchResult($hits, 50, 5.0, [], '', 20, 40);
        $page5Result = new SearchResult($hits, 50, 5.0, [], '', 10, 40);

        expect($page1Result->getCurrentPage())->toBe(1)
            ->and($page2Result->getCurrentPage())->toBe(2)
            ->and($page3Result->getCurrentPage())->toBe(3)
            ->and($page5Result->getCurrentPage())->toBe(5);
    });

    it('handles zero limit when calculating current page', function (): void {
        $result = new SearchResult([['id' => 1]], 50, 5.0, [], '', 0, 100);

        expect($result->getCurrentPage())->toBe(1);
    });

    it('calculates total pages correctly', function (): void {
        $hits = [['id' => 1]];

        $result20Per20 = new SearchResult($hits, 100, 5.0, [], '', 20, 0); // 100/20 = 5 pages
        $result20Per15 = new SearchResult($hits, 100, 5.0, [], '', 15, 0); // 100/15 = 6.67 -> 7 pages
        $result5Per10 = new SearchResult($hits, 5, 5.0, [], '', 10, 0); // 5/10 = 0.5 -> 1 page
        $resultZeroHits = new SearchResult([], 0, 5.0, [], '', 20, 0); // 0/20 = 0 pages

        expect($result20Per20->getTotalPages())->toBe(5)
            ->and($result20Per15->getTotalPages())->toBe(7)
            ->and($result5Per10->getTotalPages())->toBe(1)
            ->and($resultZeroHits->getTotalPages())->toBe(0);
    });

    it('handles zero limit when calculating total pages', function (): void {
        $result = new SearchResult([['id' => 1]], 100, 5.0, [], '', 0, 0);

        expect($result->getTotalPages())->toBe(1);
    });

    it('detects if there are more pages', function (): void {
        $hits = [['id' => 1]];

        $lastPageResult = new SearchResult($hits, 20, 5.0, [], '', 20, 0); // Page 1 of 1
        $firstPageResult = new SearchResult($hits, 100, 5.0, [], '', 20, 0); // Page 1 of 5
        $middlePageResult = new SearchResult($hits, 100, 5.0, [], '', 20, 40); // Page 3 of 5
        $finalPageResult = new SearchResult($hits, 100, 5.0, [], '', 20, 80); // Page 5 of 5

        expect($lastPageResult->hasMorePages())->toBeFalse()
            ->and($firstPageResult->hasMorePages())->toBeTrue()
            ->and($middlePageResult->hasMorePages())->toBeTrue()
            ->and($finalPageResult->hasMorePages())->toBeFalse();
    });

    it('gets facet distribution for specific attribute', function (): void {
        $facets = [
            'category' => ['environment' => 15, 'health' => 8, 'education' => 5],
            'status' => ['active' => 20, 'draft' => 3],
        ];

        $result = new SearchResult([], 0, 5.0, $facets);

        expect($result->getFacetDistribution('category'))->toBe(['environment' => 15, 'health' => 8, 'education' => 5])
            ->and($result->getFacetDistribution('status'))->toBe(['active' => 20, 'draft' => 3])
            ->and($result->getFacetDistribution('nonexistent'))->toBe([]);
    });

    it('detects if facets are available', function (): void {
        $resultWithFacets = new SearchResult([], 0, 5.0, ['category' => ['env' => 10]]);
        $resultWithoutFacets = new SearchResult([], 0, 5.0, []);

        expect($resultWithFacets->hasFacets())->toBeTrue()
            ->and($resultWithoutFacets->hasFacets())->toBeFalse();
    });

    it('gets processing time in milliseconds', function (): void {
        $result = new SearchResult([], 0, 123.45);

        expect($result->getProcessingTimeMs())->toBe(123.45);
    });

    it('formats processing time correctly', function (): void {
        $subMillisecondResult = new SearchResult([], 0, 0.25);
        $millisecondsResult = new SearchResult([], 0, 125.0);
        $secondsResult = new SearchResult([], 0, 1500.0);
        $preciseSecondsResult = new SearchResult([], 0, 2350.75);

        expect($subMillisecondResult->getFormattedProcessingTime())->toBe('0.25ms')
            ->and($millisecondsResult->getFormattedProcessingTime())->toBe('125ms')
            ->and($secondsResult->getFormattedProcessingTime())->toBe('1.50s')
            ->and($preciseSecondsResult->getFormattedProcessingTime())->toBe('2.35s');
    });

    it('provides query getter alias', function (): void {
        $result = new SearchResult([], 0, 5.0, [], 'environment campaign');

        expect($result->getQuery())->toBe('environment campaign')
            ->and($result->getQuery())->toBe($result->query);
    });

    it('provides search time getter alias', function (): void {
        $result = new SearchResult([], 0, 42.5);

        expect($result->getSearchTime())->toBe(42.5)
            ->and($result->getSearchTime())->toBe($result->processingTime);
    });

    it('provides total getter alias', function (): void {
        $result = new SearchResult([], 150, 5.0);

        expect($result->getTotal())->toBe(150)
            ->and($result->getTotal())->toBe($result->totalHits);
    });

    it('provides engine getter alias', function (): void {
        $result = new SearchResult([], 0, 5.0, [], '', 20, 0, null, [], 'elasticsearch');

        expect($result->getEngine())->toBe('elasticsearch')
            ->and($result->getEngine())->toBe($result->engine);
    });

    it('provides results getter alias', function (): void {
        $hits = [['id' => 1, 'title' => 'Test']];
        $result = new SearchResult($hits, 1, 5.0);

        expect($result->getResults())->toBe($hits)
            ->and($result->getResults())->toBe($result->hits);
    });

    it('provides facets getter alias', function (): void {
        $facets = ['category' => ['env' => 5]];
        $result = new SearchResult([], 0, 5.0, $facets);

        expect($result->getFacets())->toBe($facets)
            ->and($result->getFacets())->toBe($result->facets);
    });

    it('provides suggestions getter alias', function (): void {
        $suggestions = ['environment', 'environmental'];
        $result = new SearchResult([], 0, 5.0, [], '', 20, 0, null, $suggestions);

        expect($result->getSuggestions())->toBe($suggestions)
            ->and($result->getSuggestions())->toBe($result->suggestions);
    });

    it('converts to array for API response', function (): void {
        $hits = [['id' => 1, 'title' => 'Test Campaign']];
        $facets = ['status' => ['active' => 10]];
        $result = new SearchResult(
            hits: $hits,
            totalHits: 45,
            processingTime: 12.8,
            facets: $facets,
            query: 'test',
            limit: 15,
            offset: 30
        );

        $array = $result->toArray();

        expect($array)->toBe([
            'hits' => $hits,
            'totalHits' => 45,
            'processingTime' => 12.8,
            'facets' => $facets,
            'query' => 'test',
            'limit' => 15,
            'offset' => 30,
            'currentPage' => 3, // (30/15) + 1
            'totalPages' => 3, // ceil(45/15)
            'hasMorePages' => false, // Page 3 of 3
        ]);
    });

    it('handles complex pagination in array conversion', function (): void {
        $result = new SearchResult(
            hits: [['id' => 1]],
            totalHits: 100,
            processingTime: 5.0,
            facets: [],
            query: 'search',
            limit: 12,
            offset: 24
        );

        $array = $result->toArray();

        expect($array['currentPage'])->toBe(3) // (24/12) + 1
            ->and($array['totalPages'])->toBe(9) // ceil(100/12)
            ->and($array['hasMorePages'])->toBeTrue(); // Page 3 of 9
    });

    it('handles empty results gracefully', function (): void {
        $result = new SearchResult([], 0, 1.5);

        expect($result->hasResults())->toBeFalse()
            ->and($result->getHitsCollection()->isEmpty())->toBeTrue()
            ->and($result->getCurrentPage())->toBe(1)
            ->and($result->getTotalPages())->toBe(0)
            ->and($result->hasMorePages())->toBeFalse()
            ->and($result->hasFacets())->toBeFalse()
            ->and($result->getFacetDistribution('any'))->toBe([]);
    });

    it('preserves readonly properties immutability', function (): void {
        $hits = [['id' => 1, 'title' => 'Test']];
        $facets = ['category' => ['env' => 5]];
        $suggestions = ['test', 'testing'];

        $result = new SearchResult(
            hits: $hits,
            totalHits: 10,
            processingTime: 5.5,
            facets: $facets,
            query: 'test query',
            limit: 20,
            offset: 0,
            estimatedTotalHits: 12,
            suggestions: $suggestions,
            engine: 'meilisearch'
        );

        expect($result->hits)->toBe($hits)
            ->and($result->totalHits)->toBe(10)
            ->and($result->processingTime)->toBe(5.5)
            ->and($result->facets)->toBe($facets)
            ->and($result->query)->toBe('test query')
            ->and($result->limit)->toBe(20)
            ->and($result->offset)->toBe(0)
            ->and($result->estimatedTotalHits)->toBe(12)
            ->and($result->suggestions)->toBe($suggestions)
            ->and($result->engine)->toBe('meilisearch');
    });

    it('handles edge cases in pagination calculations', function (): void {
        $hits = [['id' => 1]];

        // Edge case: exactly on page boundary
        $exactBoundary = new SearchResult($hits, 40, 5.0, [], '', 20, 20);
        expect($exactBoundary->getCurrentPage())->toBe(2)
            ->and($exactBoundary->getTotalPages())->toBe(2)
            ->and($exactBoundary->hasMorePages())->toBeFalse();

        // Edge case: one item per page
        $onePerPage = new SearchResult($hits, 5, 5.0, [], '', 1, 3);
        expect($onePerPage->getCurrentPage())->toBe(4)
            ->and($onePerPage->getTotalPages())->toBe(5)
            ->and($onePerPage->hasMorePages())->toBeTrue();

        // Edge case: large limit, small total
        $largeLimit = new SearchResult($hits, 3, 5.0, [], '', 100, 0);
        expect($largeLimit->getCurrentPage())->toBe(1)
            ->and($largeLimit->getTotalPages())->toBe(1)
            ->and($largeLimit->hasMorePages())->toBeFalse();
    });

    it('maintains consistent array serialization', function (): void {
        $result = new SearchResult(
            hits: [['id' => 1, 'title' => 'Test']],
            totalHits: 25,
            processingTime: 8.5,
            facets: ['status' => ['active' => 20]],
            query: 'test query',
            limit: 10,
            offset: 10
        );

        $array1 = $result->toArray();
        $array2 = $result->toArray();

        expect($array1)->toBe($array2) // Multiple calls return same result
            ->and($array1['hits'])->toBe([['id' => 1, 'title' => 'Test']])
            ->and($array1['totalHits'])->toBe(25)
            ->and($array1['currentPage'])->toBe(2)
            ->and($array1['totalPages'])->toBe(3)
            ->and($array1['hasMorePages'])->toBeTrue();
    });

    it('handles special processing time values', function (): void {
        $zeroTime = new SearchResult([], 0, 0.0);
        $negativeTime = new SearchResult([], 0, -1.0);
        $veryLargeTime = new SearchResult([], 0, 999999.9);

        expect($zeroTime->getFormattedProcessingTime())->toBe('0.00ms')
            ->and($negativeTime->getFormattedProcessingTime())->toBe('-1.00ms')
            ->and($veryLargeTime->getFormattedProcessingTime())->toBe('1000.00s');
    });

    it('handles complex facet structures', function (): void {
        $complexFacets = [
            'category' => [
                'environment' => 150,
                'health' => 89,
                'education' => 67,
                'technology' => 45,
                'social' => 23,
            ],
            'status' => [
                'active' => 300,
                'draft' => 45,
                'completed' => 89,
                'archived' => 12,
            ],
            'organization_type' => [
                'nonprofit' => 200,
                'corporate' => 150,
                'government' => 50,
                'individual' => 46,
            ],
        ];

        $result = new SearchResult([], 446, 15.2, $complexFacets);

        expect($result->hasFacets())->toBeTrue()
            ->and($result->getFacetDistribution('category'))->toHaveCount(5)
            ->and($result->getFacetDistribution('status'))->toHaveCount(4)
            ->and($result->getFacetDistribution('organization_type'))->toHaveCount(4)
            ->and($result->getFacetDistribution('category')['environment'])->toBe(150)
            ->and($result->getFacetDistribution('status')['active'])->toBe(300)
            ->and($result->getFacetDistribution('nonexistent'))->toBe([]);
    });

    it('validates immutability of arrays', function (): void {
        $hits = [['id' => 1, 'title' => 'Original']];
        $facets = ['category' => ['env' => 5]];
        $suggestions = ['original'];

        $result = new SearchResult($hits, 1, 5.0, $facets, '', 20, 0, null, $suggestions);

        // Modify original arrays
        $hits[0]['title'] = 'Modified';
        $facets['category']['env'] = 10;
        $suggestions[] = 'added';

        expect($result->hits[0]['title'])->toBe('Original') // Unchanged
            ->and($result->facets['category']['env'])->toBe(5) // Unchanged
            ->and($result->suggestions)->toBe(['original']); // Unchanged
    });
});
