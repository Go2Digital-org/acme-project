<?php

declare(strict_types=1);

use Modules\Search\Domain\Model\SearchQuery;
use Modules\Search\Domain\ValueObject\SearchFilters;
use Modules\Search\Domain\ValueObject\SearchSort;

describe('SearchQuery Model', function () {
    it('creates search query with required parameters', function () {
        $filters = new SearchFilters;
        $sort = new SearchSort;
        $query = new SearchQuery(
            query: 'environment campaign',
            indexes: ['campaigns', 'donations'],
            filters: $filters,
            sort: $sort
        );

        expect($query->query)->toBe('environment campaign')
            ->and($query->indexes)->toBe(['campaigns', 'donations'])
            ->and($query->filters)->toBe($filters)
            ->and($query->sort)->toBe($sort)
            ->and($query->limit)->toBe(20) // Default
            ->and($query->offset)->toBe(0) // Default
            ->and($query->locale)->toBeNull() // Default
            ->and($query->enableHighlighting)->toBeTrue() // Default
            ->and($query->enableFacets)->toBeFalse() // Default
            ->and($query->enableTypoTolerance)->toBeTrue(); // Default
    });

    it('creates search query with custom parameters', function () {
        $filters = new SearchFilters(entityTypes: ['campaign']);
        $sort = new SearchSort(SearchSort::FIELD_CREATED_AT, SearchSort::DIRECTION_DESC);
        $query = new SearchQuery(
            query: 'education project',
            indexes: ['campaigns'],
            filters: $filters,
            sort: $sort,
            limit: 50,
            offset: 100,
            locale: 'en-US',
            enableHighlighting: false,
            enableFacets: true,
            enableTypoTolerance: false
        );

        expect($query->query)->toBe('education project')
            ->and($query->indexes)->toBe(['campaigns'])
            ->and($query->filters)->toBe($filters)
            ->and($query->sort)->toBe($sort)
            ->and($query->limit)->toBe(50)
            ->and($query->offset)->toBe(100)
            ->and($query->locale)->toBe('en-US')
            ->and($query->enableHighlighting)->toBeFalse()
            ->and($query->enableFacets)->toBeTrue()
            ->and($query->enableTypoTolerance)->toBeFalse();
    });

    it('converts to array for search engine', function () {
        $filters = new SearchFilters(entityTypes: ['campaign'], isActive: true);
        $sort = new SearchSort(SearchSort::FIELD_AMOUNT, SearchSort::DIRECTION_ASC);
        $query = new SearchQuery(
            query: 'environmental protection',
            indexes: ['campaigns', 'organizations'],
            filters: $filters,
            sort: $sort,
            limit: 25,
            offset: 50,
            locale: 'fr-FR',
            enableHighlighting: true,
            enableFacets: false,
            enableTypoTolerance: true
        );

        $array = $query->toArray();

        expect($array)->toBe([
            'q' => 'environmental protection',
            'indexes' => ['campaigns', 'organizations'],
            'filters' => $filters->toArray(),
            'sort' => $sort->toArray(),
            'limit' => 25,
            'offset' => 50,
            'locale' => 'fr-FR',
            'highlight' => true,
            'facets' => false,
            'typoTolerance' => true,
        ]);
    });

    it('creates query for single index', function () {
        $query = SearchQuery::forIndex(
            index: 'campaigns',
            query: 'climate change',
            filters: null,
            sort: null,
            limit: 15,
            offset: 30
        );

        expect($query->query)->toBe('climate change')
            ->and($query->indexes)->toBe(['campaigns'])
            ->and($query->filters)->toBeInstanceOf(SearchFilters::class)
            ->and($query->sort)->toBeInstanceOf(SearchSort::class)
            ->and($query->limit)->toBe(15)
            ->and($query->offset)->toBe(30);
    });

    it('creates query for single index with custom filters and sort', function () {
        $filters = new SearchFilters(statuses: ['active']);
        $sort = new SearchSort(SearchSort::FIELD_DATE, SearchSort::DIRECTION_DESC);

        $query = SearchQuery::forIndex(
            index: 'donations',
            query: 'healthcare support',
            filters: $filters,
            sort: $sort
        );

        expect($query->indexes)->toBe(['donations'])
            ->and($query->filters)->toBe($filters)
            ->and($query->sort)->toBe($sort)
            ->and($query->limit)->toBe(20) // Default
            ->and($query->offset)->toBe(0); // Default
    });

    it('creates query for multiple indexes', function () {
        $indexes = ['campaigns', 'donations', 'organizations'];
        $query = SearchQuery::forIndexes(
            indexes: $indexes,
            query: 'sustainability',
            filters: null,
            sort: null,
            limit: 40,
            offset: 80
        );

        expect($query->query)->toBe('sustainability')
            ->and($query->indexes)->toBe($indexes)
            ->and($query->filters)->toBeInstanceOf(SearchFilters::class)
            ->and($query->sort)->toBeInstanceOf(SearchSort::class)
            ->and($query->limit)->toBe(40)
            ->and($query->offset)->toBe(80);
    });

    it('creates query for multiple indexes with custom filters and sort', function () {
        $indexes = ['campaigns', 'donations'];
        $filters = new SearchFilters(organizationIds: [1, 2, 3]);
        $sort = new SearchSort(SearchSort::FIELD_UPDATED_AT, SearchSort::DIRECTION_ASC);

        $query = SearchQuery::forIndexes(
            indexes: $indexes,
            query: 'education initiative',
            filters: $filters,
            sort: $sort
        );

        expect($query->indexes)->toBe($indexes)
            ->and($query->filters)->toBe($filters)
            ->and($query->sort)->toBe($sort);
    });

    it('calculates page number from offset correctly', function () {
        $filters = new SearchFilters;
        $sort = new SearchSort;

        $page1Query = new SearchQuery('test', ['campaigns'], $filters, $sort, 20, 0);
        $page2Query = new SearchQuery('test', ['campaigns'], $filters, $sort, 20, 20);
        $page3Query = new SearchQuery('test', ['campaigns'], $filters, $sort, 20, 40);
        $page5Query = new SearchQuery('test', ['campaigns'], $filters, $sort, 10, 40);

        expect($page1Query->getPage())->toBe(1)
            ->and($page2Query->getPage())->toBe(2)
            ->and($page3Query->getPage())->toBe(3)
            ->and($page5Query->getPage())->toBe(5);
    });

    it('handles zero limit when calculating page', function () {
        $filters = new SearchFilters;
        $sort = new SearchSort;
        $query = new SearchQuery('test', ['campaigns'], $filters, $sort, 0, 100);

        expect($query->getPage())->toBe(1); // Should return 1 for zero limit
    });

    it('calculates page for fractional results', function () {
        $filters = new SearchFilters;
        $sort = new SearchSort;
        $query = new SearchQuery('test', ['campaigns'], $filters, $sort, 15, 25);

        expect($query->getPage())->toBe(2); // floor(25/15) + 1 = floor(1.67) + 1 = 2
    });

    it('detects empty query correctly', function () {
        $filters = new SearchFilters;
        $sort = new SearchSort;

        $emptyQuery = new SearchQuery('', ['campaigns'], $filters, $sort);
        $nonEmptyQuery = new SearchQuery('climate', ['campaigns'], $filters, $sort);
        $spaceQuery = new SearchQuery(' ', ['campaigns'], $filters, $sort);

        expect($emptyQuery->isEmpty())->toBeTrue()
            ->and($nonEmptyQuery->isEmpty())->toBeFalse()
            ->and($spaceQuery->isEmpty())->toBeFalse(); // Space is not empty
    });

    it('generates cache key consistently', function () {
        $filters = new SearchFilters(entityTypes: ['campaign']);
        $sort = new SearchSort(SearchSort::FIELD_CREATED_AT, SearchSort::DIRECTION_DESC);

        $query1 = new SearchQuery(
            'environmental protection',
            ['campaigns'],
            $filters,
            $sort,
            20,
            0
        );

        $query2 = new SearchQuery(
            'environmental protection',
            ['campaigns'],
            $filters,
            $sort,
            20,
            0
        );

        $query3 = new SearchQuery(
            'environmental protection',
            ['campaigns'],
            $filters,
            $sort,
            25, // Different limit
            0
        );

        expect($query1->getCacheKey())->toBe($query2->getCacheKey()) // Same queries
            ->and($query1->getCacheKey())->not->toBe($query3->getCacheKey()) // Different limit
            ->and($query1->getCacheKey())->toStartWith('search:')
            ->and(strlen($query1->getCacheKey()))->toBe(39); // 'search:' + 32 char MD5
    });

    it('generates different cache keys for different queries', function () {
        $filters = new SearchFilters;
        $sort = new SearchSort;

        $query1 = new SearchQuery('climate', ['campaigns'], $filters, $sort);
        $query2 = new SearchQuery('education', ['campaigns'], $filters, $sort);
        $query3 = new SearchQuery('climate', ['donations'], $filters, $sort);

        expect($query1->getCacheKey())->not->toBe($query2->getCacheKey()) // Different query text
            ->and($query1->getCacheKey())->not->toBe($query3->getCacheKey()); // Different indexes
    });

    it('handles complex cache key generation', function () {
        $filters = new SearchFilters(
            entityTypes: ['campaign', 'donation'],
            statuses: ['active'],
            organizationIds: [1, 2, 3],
            isActive: true
        );
        $sort = new SearchSort(SearchSort::FIELD_AMOUNT, SearchSort::DIRECTION_ASC);

        $query = new SearchQuery(
            'environmental sustainability project',
            ['campaigns', 'donations'],
            $filters,
            $sort,
            50,
            100,
            'en-US',
            true,
            true,
            false
        );

        $cacheKey = $query->getCacheKey();

        expect($cacheKey)->toStartWith('search:')
            ->and(strlen($cacheKey))->toBe(39)
            ->and($cacheKey)->toMatch('/^search:[a-f0-9]{32}$/');
    });

    it('preserves immutability of value objects', function () {
        $filters = new SearchFilters(entityTypes: ['campaign']);
        $sort = new SearchSort(SearchSort::FIELD_CREATED_AT, SearchSort::DIRECTION_DESC);

        $query = new SearchQuery('test', ['campaigns'], $filters, $sort);

        expect($query->filters)->toBe($filters)
            ->and($query->sort)->toBe($sort)
            ->and($query->filters)->toBeInstanceOf(SearchFilters::class)
            ->and($query->sort)->toBeInstanceOf(SearchSort::class);
    });

    it('handles array index immutability', function () {
        $indexes = ['campaigns', 'donations'];
        $filters = new SearchFilters;
        $sort = new SearchSort;

        $query = new SearchQuery('test', $indexes, $filters, $sort);

        // Modify original array
        $indexes[] = 'organizations';

        expect($query->indexes)->toBe(['campaigns', 'donations']); // Unchanged
    });

    it('creates queries with different configurations', function () {
        $baseFilters = new SearchFilters;
        $baseSort = new SearchSort;

        $simpleQuery = SearchQuery::forIndex('campaigns', 'environment');
        $complexQuery = new SearchQuery(
            'climate change environmental protection',
            ['campaigns', 'donations', 'organizations'],
            new SearchFilters(
                entityTypes: ['campaign', 'donation'],
                statuses: ['active', 'completed'],
                organizationIds: [1, 2, 3, 4, 5],
                isActive: true,
                isVerified: true
            ),
            new SearchSort(SearchSort::FIELD_AMOUNT, SearchSort::DIRECTION_DESC),
            100,
            200,
            'fr-FR',
            false,
            true,
            false
        );

        expect($simpleQuery->query)->toBe('environment')
            ->and($simpleQuery->indexes)->toBe(['campaigns'])
            ->and($simpleQuery->limit)->toBe(20)
            ->and($complexQuery->query)->toBe('climate change environmental protection')
            ->and($complexQuery->indexes)->toBe(['campaigns', 'donations', 'organizations'])
            ->and($complexQuery->limit)->toBe(100)
            ->and($complexQuery->offset)->toBe(200)
            ->and($complexQuery->locale)->toBe('fr-FR')
            ->and($complexQuery->enableHighlighting)->toBeFalse()
            ->and($complexQuery->enableFacets)->toBeTrue()
            ->and($complexQuery->enableTypoTolerance)->toBeFalse();
    });

    it('validates factory method behaviors', function () {
        $customFilters = new SearchFilters(categories: ['health']);
        $customSort = new SearchSort(SearchSort::FIELD_NAME, SearchSort::DIRECTION_ASC);

        $singleIndexQuery = SearchQuery::forIndex('donations', 'healthcare', $customFilters, $customSort, 30, 60);
        $multiIndexQuery = SearchQuery::forIndexes(['campaigns', 'donations'], 'healthcare', $customFilters, $customSort, 30, 60);

        expect($singleIndexQuery->indexes)->toBe(['donations'])
            ->and($multiIndexQuery->indexes)->toBe(['campaigns', 'donations'])
            ->and($singleIndexQuery->filters)->toBe($customFilters)
            ->and($multiIndexQuery->filters)->toBe($customFilters)
            ->and($singleIndexQuery->sort)->toBe($customSort)
            ->and($multiIndexQuery->sort)->toBe($customSort)
            ->and($singleIndexQuery->limit)->toBe(30)
            ->and($multiIndexQuery->limit)->toBe(30)
            ->and($singleIndexQuery->offset)->toBe(60)
            ->and($multiIndexQuery->offset)->toBe(60);
    });

    it('handles edge cases in pagination', function () {
        $filters = new SearchFilters;
        $sort = new SearchSort;

        $negativeOffsetQuery = new SearchQuery('test', ['campaigns'], $filters, $sort, 20, -10);
        $largeOffsetQuery = new SearchQuery('test', ['campaigns'], $filters, $sort, 20, 10000);

        expect($negativeOffsetQuery->getPage())->toBe(1) // floor(-10/20) + 1 = floor(-0.5) + 1 = 0 + 1 = 1
            ->and($largeOffsetQuery->getPage())->toBe(501); // floor(10000/20) + 1 = 500 + 1 = 501
    });

    it('maintains consistent serialization', function () {
        $filters = new SearchFilters(entityTypes: ['campaign']);
        $sort = new SearchSort(SearchSort::FIELD_CREATED_AT, SearchSort::DIRECTION_ASC);

        $query = new SearchQuery('test query', ['campaigns'], $filters, $sort, 25, 50);

        $array1 = $query->toArray();
        $array2 = $query->toArray();

        expect($array1)->toBe($array2) // Multiple calls return same result
            ->and($array1['q'])->toBe('test query')
            ->and($array1['indexes'])->toBe(['campaigns'])
            ->and($array1['limit'])->toBe(25)
            ->and($array1['offset'])->toBe(50);
    });

    it('handles special characters in query text', function () {
        $filters = new SearchFilters;
        $sort = new SearchSort;

        $specialQuery = new SearchQuery('test "quoted text" AND (group)', ['campaigns'], $filters, $sort);
        $unicodeQuery = new SearchQuery('café résumé naïve', ['campaigns'], $filters, $sort);
        $symbolQuery = new SearchQuery('$100 @user #hashtag', ['campaigns'], $filters, $sort);

        expect($specialQuery->query)->toBe('test "quoted text" AND (group)')
            ->and($unicodeQuery->query)->toBe('café résumé naïve')
            ->and($symbolQuery->query)->toBe('$100 @user #hashtag')
            ->and($specialQuery->isEmpty())->toBeFalse()
            ->and($unicodeQuery->isEmpty())->toBeFalse()
            ->and($symbolQuery->isEmpty())->toBeFalse();
    });

    it('validates readonly properties immutability', function () {
        $filters = new SearchFilters;
        $sort = new SearchSort;
        $query = new SearchQuery('test', ['campaigns'], $filters, $sort);

        // These properties should be readonly (cannot be modified after construction)
        expect($query->query)->toBe('test')
            ->and($query->indexes)->toBe(['campaigns'])
            ->and($query->filters)->toBe($filters)
            ->and($query->sort)->toBe($sort)
            ->and($query->limit)->toBe(20)
            ->and($query->offset)->toBe(0)
            ->and($query->locale)->toBeNull()
            ->and($query->enableHighlighting)->toBeTrue()
            ->and($query->enableFacets)->toBeFalse()
            ->and($query->enableTypoTolerance)->toBeTrue();
    });
});
