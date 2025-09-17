<?php

declare(strict_types=1);

use Modules\Search\Domain\ValueObject\IndexConfiguration;

describe('IndexConfiguration Value Object', function () {
    it('creates default configuration with sensible defaults', function () {
        $config = new IndexConfiguration;

        expect($config->primaryKey)->toBe('id')
            ->and($config->searchableFields)->toBe([])
            ->and($config->filterableFields)->toBe([])
            ->and($config->sortableFields)->toBe([])
            ->and($config->rankingRules)->toBe([
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
            ])
            ->and($config->stopWords)->toBe([])
            ->and($config->synonyms)->toBe([])
            ->and($config->distinctAttribute)->toBeNull()
            ->and($config->faceting)->toBe(['maxValuesPerFacet' => 100])
            ->and($config->displayedAttributes)->toBe(['*'])
            ->and($config->typoToleranceEnabled)->toBeTrue()
            ->and($config->maxTotalHits)->toBe(1000);
    });

    it('creates configuration with custom primary key', function () {
        $config = new IndexConfiguration(primaryKey: 'uuid');

        expect($config->primaryKey)->toBe('uuid')
            ->and($config->searchableFields)->toBe([]);
    });

    it('creates configuration with searchable fields', function () {
        $searchableFields = ['title', 'description', 'content'];
        $config = new IndexConfiguration(searchableFields: $searchableFields);

        expect($config->searchableFields)->toBe($searchableFields)
            ->and($config->primaryKey)->toBe('id');
    });

    it('creates configuration with filterable fields', function () {
        $filterableFields = ['status', 'category', 'organization_id'];
        $config = new IndexConfiguration(filterableFields: $filterableFields);

        expect($config->filterableFields)->toBe($filterableFields)
            ->and($config->searchableFields)->toBe([]);
    });

    it('creates configuration with sortable fields', function () {
        $sortableFields = ['created_at', 'amount', 'priority'];
        $config = new IndexConfiguration(sortableFields: $sortableFields);

        expect($config->sortableFields)->toBe($sortableFields)
            ->and($config->filterableFields)->toBe([]);
    });

    it('creates configuration with custom ranking rules', function () {
        $rankingRules = ['words', 'sort', 'exactness'];
        $config = new IndexConfiguration(rankingRules: $rankingRules);

        expect($config->rankingRules)->toBe($rankingRules)
            ->and($config->sortableFields)->toBe([]);
    });

    it('creates configuration with stop words', function () {
        $stopWords = ['the', 'a', 'an', 'and', 'or'];
        $config = new IndexConfiguration(stopWords: $stopWords);

        expect($config->stopWords)->toBe($stopWords)
            ->and($config->rankingRules)->toBe([
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
            ]);
    });

    it('creates configuration with synonyms', function () {
        $synonyms = [
            'car' => ['automobile', 'vehicle'],
            'phone' => ['mobile', 'smartphone'],
        ];
        $config = new IndexConfiguration(synonyms: $synonyms);

        expect($config->synonyms)->toBe($synonyms)
            ->and($config->stopWords)->toBe([]);
    });

    it('creates configuration with distinct attribute', function () {
        $config = new IndexConfiguration(distinctAttribute: 'organization_id');

        expect($config->distinctAttribute)->toBe('organization_id')
            ->and($config->synonyms)->toBe([]);
    });

    it('creates configuration with custom faceting', function () {
        $faceting = ['maxValuesPerFacet' => 50, 'sortFacetValuesBy' => ['*' => 'count']];
        $config = new IndexConfiguration(faceting: $faceting);

        expect($config->faceting)->toBe($faceting)
            ->and($config->distinctAttribute)->toBeNull();
    });

    it('creates configuration with custom displayed attributes', function () {
        $displayedAttributes = ['id', 'title', 'description', 'status'];
        $config = new IndexConfiguration(displayedAttributes: $displayedAttributes);

        expect($config->displayedAttributes)->toBe($displayedAttributes)
            ->and($config->faceting)->toBe(['maxValuesPerFacet' => 100]);
    });

    it('creates configuration with typo tolerance disabled', function () {
        $config = new IndexConfiguration(typoToleranceEnabled: false);

        expect($config->typoToleranceEnabled)->toBeFalse()
            ->and($config->displayedAttributes)->toBe(['*']);
    });

    it('creates configuration with custom max total hits', function () {
        $config = new IndexConfiguration(maxTotalHits: 5000);

        expect($config->maxTotalHits)->toBe(5000)
            ->and($config->typoToleranceEnabled)->toBeTrue();
    });

    it('converts to meilisearch settings format', function () {
        $config = new IndexConfiguration(
            searchableFields: ['title', 'description'],
            filterableFields: ['status', 'category'],
            sortableFields: ['created_at', 'amount'],
            rankingRules: ['words', 'sort', 'exactness'],
            displayedAttributes: ['id', 'title', 'status']
        );

        $settings = $config->toMeilisearchSettings();

        expect($settings)->toBe([
            'searchableAttributes' => ['title', 'description'],
            'filterableAttributes' => ['status', 'category'],
            'sortableAttributes' => ['created_at', 'amount'],
            'rankingRules' => ['words', 'sort', 'exactness'],
            'displayedAttributes' => ['id', 'title', 'status'],
            'faceting' => [
                'maxValuesPerFacet' => 100,
            ],
            'typoTolerance' => [
                'enabled' => true,
                'minWordSizeForTypos' => [
                    'oneTypo' => 5,
                    'twoTypos' => 9,
                ],
            ],
            'pagination' => [
                'maxTotalHits' => 1000,
            ],
        ]);
    });

    it('includes stop words in meilisearch settings when not empty', function () {
        $config = new IndexConfiguration(
            searchableFields: ['title'],
            stopWords: ['the', 'a', 'an']
        );

        $settings = $config->toMeilisearchSettings();

        expect($settings)->toHaveKey('stopWords')
            ->and($settings['stopWords'])->toBe(['the', 'a', 'an']);
    });

    it('excludes stop words from meilisearch settings when empty', function () {
        $config = new IndexConfiguration(searchableFields: ['title']);

        $settings = $config->toMeilisearchSettings();

        expect($settings)->not->toHaveKey('stopWords');
    });

    it('includes synonyms in meilisearch settings when not empty', function () {
        $synonyms = ['car' => ['automobile', 'vehicle']];
        $config = new IndexConfiguration(
            searchableFields: ['title'],
            synonyms: $synonyms
        );

        $settings = $config->toMeilisearchSettings();

        expect($settings)->toHaveKey('synonyms')
            ->and($settings['synonyms'])->toBe($synonyms);
    });

    it('excludes synonyms from meilisearch settings when empty', function () {
        $config = new IndexConfiguration(searchableFields: ['title']);

        $settings = $config->toMeilisearchSettings();

        expect($settings)->not->toHaveKey('synonyms');
    });

    it('includes distinct attribute when set', function () {
        $config = new IndexConfiguration(
            searchableFields: ['title'],
            distinctAttribute: 'organization_id'
        );

        $settings = $config->toMeilisearchSettings();

        expect($settings)->toHaveKey('distinctAttribute')
            ->and($settings['distinctAttribute'])->toBe('organization_id');
    });

    it('excludes distinct attribute when null', function () {
        $config = new IndexConfiguration(searchableFields: ['title']);

        $settings = $config->toMeilisearchSettings();

        expect($settings)->not->toHaveKey('distinctAttribute');
    });

    it('includes faceting settings when not empty', function () {
        $faceting = ['maxValuesPerFacet' => 50];
        $config = new IndexConfiguration(
            searchableFields: ['title'],
            faceting: $faceting
        );

        $settings = $config->toMeilisearchSettings();

        expect($settings)->toHaveKey('faceting')
            ->and($settings['faceting'])->toBe($faceting);
    });

    it('includes typo tolerance settings with custom configuration', function () {
        $config = new IndexConfiguration(
            searchableFields: ['title'],
            typoToleranceEnabled: false,
            maxTotalHits: 2000
        );

        $settings = $config->toMeilisearchSettings();

        expect($settings['typoTolerance'])->toBe([
            'enabled' => false,
            'minWordSizeForTypos' => [
                'oneTypo' => 5,
                'twoTypos' => 9,
            ],
        ])
            ->and($settings['pagination'])->toBe([
                'maxTotalHits' => 2000,
            ]);
    });

    it('creates configuration from array data', function () {
        $data = [
            'primaryKey' => 'uuid',
            'searchableFields' => ['title', 'content'],
            'filterableFields' => ['status'],
            'sortableFields' => ['created_at'],
            'rankingRules' => ['words', 'sort'],
            'stopWords' => ['the', 'a'],
            'synonyms' => ['car' => ['vehicle']],
            'distinctAttribute' => 'org_id',
            'faceting' => ['maxValuesPerFacet' => 25],
            'displayedAttributes' => ['id', 'title'],
            'typoToleranceEnabled' => false,
            'maxTotalHits' => 500,
        ];

        $config = IndexConfiguration::fromArray($data);

        expect($config->primaryKey)->toBe('uuid')
            ->and($config->searchableFields)->toBe(['title', 'content'])
            ->and($config->filterableFields)->toBe(['status'])
            ->and($config->sortableFields)->toBe(['created_at'])
            ->and($config->rankingRules)->toBe(['words', 'sort'])
            ->and($config->stopWords)->toBe(['the', 'a'])
            ->and($config->synonyms)->toBe(['car' => ['vehicle']])
            ->and($config->distinctAttribute)->toBe('org_id')
            ->and($config->faceting)->toBe(['maxValuesPerFacet' => 25])
            ->and($config->displayedAttributes)->toBe(['id', 'title'])
            ->and($config->typoToleranceEnabled)->toBeFalse()
            ->and($config->maxTotalHits)->toBe(500);
    });

    it('creates configuration from partial array with defaults', function () {
        $data = [
            'searchableFields' => ['title'],
            'filterableFields' => ['status'],
        ];

        $config = IndexConfiguration::fromArray($data);

        expect($config->primaryKey)->toBe('id') // Default
            ->and($config->searchableFields)->toBe(['title'])
            ->and($config->filterableFields)->toBe(['status'])
            ->and($config->sortableFields)->toBe([]) // Default
            ->and($config->rankingRules)->toBe([
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
            ]) // Default
            ->and($config->typoToleranceEnabled)->toBeTrue() // Default
            ->and($config->maxTotalHits)->toBe(1000); // Default
    });

    it('creates configuration from empty array with all defaults', function () {
        $config = IndexConfiguration::fromArray([]);

        expect($config->primaryKey)->toBe('id')
            ->and($config->searchableFields)->toBe([])
            ->and($config->filterableFields)->toBe([])
            ->and($config->typoToleranceEnabled)->toBeTrue()
            ->and($config->maxTotalHits)->toBe(1000);
    });

    it('merges configurations correctly', function () {
        $base = new IndexConfiguration(
            primaryKey: 'id',
            searchableFields: ['title'],
            filterableFields: ['status'],
            rankingRules: ['words', 'sort'],
            stopWords: ['the'],
            synonyms: ['car' => ['vehicle']],
            typoToleranceEnabled: true,
            maxTotalHits: 1000
        );

        $other = new IndexConfiguration(
            primaryKey: 'uuid',
            searchableFields: ['description'],
            sortableFields: ['created_at'],
            rankingRules: ['exactness'],
            stopWords: ['a'],
            synonyms: ['phone' => ['mobile']],
            distinctAttribute: 'org_id',
            typoToleranceEnabled: false,
            maxTotalHits: 2000
        );

        $merged = $base->merge($other);

        expect($merged->primaryKey)->toBe('uuid') // Overwritten
            ->and($merged->searchableFields)->toBe(['title', 'description']) // Merged
            ->and($merged->filterableFields)->toBe(['status']) // Preserved from base
            ->and($merged->sortableFields)->toBe(['created_at']) // Added from other
            ->and($merged->rankingRules)->toBe(['exactness']) // Overwritten
            ->and($merged->stopWords)->toBe(['the', 'a']) // Merged unique
            ->and($merged->synonyms)->toBe([
                'car' => ['vehicle'],
                'phone' => ['mobile'],
            ]) // Merged
            ->and($merged->distinctAttribute)->toBe('org_id') // Added from other
            ->and($merged->typoToleranceEnabled)->toBeFalse() // Overwritten
            ->and($merged->maxTotalHits)->toBe(2000); // Overwritten
    });

    it('preserves base values when other has null distinct attribute', function () {
        $base = new IndexConfiguration(distinctAttribute: 'base_attr');
        $other = new IndexConfiguration(distinctAttribute: null);
        $merged = $base->merge($other);

        expect($merged->distinctAttribute)->toBe('base_attr');
    });

    it('overwrites with other when other has non-null distinct attribute', function () {
        $base = new IndexConfiguration(distinctAttribute: 'base_attr');
        $other = new IndexConfiguration(distinctAttribute: 'other_attr');
        $merged = $base->merge($other);

        expect($merged->distinctAttribute)->toBe('other_attr');
    });

    it('merges faceting configurations', function () {
        $base = new IndexConfiguration(faceting: ['maxValuesPerFacet' => 100]);
        $other = new IndexConfiguration(faceting: ['sortFacetValuesBy' => ['*' => 'count']]);
        $merged = $base->merge($other);

        expect($merged->faceting)->toBe([
            'maxValuesPerFacet' => 100,
            'sortFacetValuesBy' => ['*' => 'count'],
        ]);
    });

    it('validates configuration correctly', function () {
        $validConfig = new IndexConfiguration(
            primaryKey: 'id',
            searchableFields: ['title', 'description'],
            rankingRules: ['words', 'typo', 'sort']
        );

        expect($validConfig->validate())->toBeTrue();
    });

    it('fails validation with empty primary key', function () {
        $invalidConfig = new IndexConfiguration(
            primaryKey: '',
            searchableFields: ['title']
        );

        expect($invalidConfig->validate())->toBeFalse();
    });

    it('fails validation with zero primary key', function () {
        $invalidConfig = new IndexConfiguration(
            primaryKey: '0',
            searchableFields: ['title']
        );

        expect($invalidConfig->validate())->toBeFalse();
    });

    it('fails validation with empty searchable fields', function () {
        $invalidConfig = new IndexConfiguration(
            primaryKey: 'id',
            searchableFields: []
        );

        expect($invalidConfig->validate())->toBeFalse();
    });

    it('validates custom ranking rules with colon', function () {
        $customConfig = new IndexConfiguration(
            primaryKey: 'id',
            searchableFields: ['title'],
            rankingRules: ['words', 'created_at:desc', 'sort']
        );

        expect($customConfig->validate())->toBeTrue();
    });

    it('fails validation with invalid ranking rule', function () {
        $invalidConfig = new IndexConfiguration(
            primaryKey: 'id',
            searchableFields: ['title'],
            rankingRules: ['words', 'invalid_rule', 'sort']
        );

        expect($invalidConfig->validate())->toBeFalse();
    });

    it('validates all standard ranking rules', function () {
        $standardRules = ['words', 'typo', 'proximity', 'attribute', 'sort', 'exactness'];
        $config = new IndexConfiguration(
            primaryKey: 'id',
            searchableFields: ['title'],
            rankingRules: $standardRules
        );

        expect($config->validate())->toBeTrue();
    });

    it('is immutable during operations', function () {
        $original = new IndexConfiguration(
            searchableFields: ['title'],
            filterableFields: ['status']
        );

        $other = new IndexConfiguration(
            searchableFields: ['description'],
            sortableFields: ['created_at']
        );

        $merged = $original->merge($other);

        expect($original->searchableFields)->toBe(['title']) // Original unchanged
            ->and($original->sortableFields)->toBe([]) // Original unchanged
            ->and($merged->searchableFields)->toBe(['title', 'description']) // Merged
            ->and($merged->sortableFields)->toBe(['created_at']) // Added from other
            ->and($original)->not->toBe($merged); // Different instances
    });

    it('handles edge cases in array creation', function () {
        $data = [
            'primaryKey' => null, // Should use default
            'searchableFields' => null, // Should use default
            'unknownField' => 'ignored', // Should be ignored
        ];

        $config = IndexConfiguration::fromArray($data);

        expect($config->primaryKey)->toBe('id') // Default used
            ->and($config->searchableFields)->toBe([]); // Default used
    });

    it('preserves configuration immutability', function () {
        $searchableFields = ['title', 'content'];
        $config = new IndexConfiguration(searchableFields: $searchableFields);

        // Modify the original array
        $searchableFields[] = 'description';

        expect($config->searchableFields)->toBe(['title', 'content']); // Unchanged
    });

    it('handles complex merge scenarios', function () {
        $base = new IndexConfiguration(
            searchableFields: ['title', 'content'],
            filterableFields: ['status', 'type'],
            stopWords: ['the', 'and']
        );

        $other = new IndexConfiguration(
            searchableFields: ['content', 'description'], // Has overlap
            filterableFields: ['category'], // Different
            stopWords: ['and', 'or'] // Has overlap
        );

        $merged = $base->merge($other);

        expect($merged->searchableFields)->toBe(['title', 'content', 'description']) // Unique merge
            ->and($merged->filterableFields)->toBe(['status', 'type', 'category']) // Unique merge
            ->and($merged->stopWords)->toBe(['the', 'and', 'or']); // Unique merge
    });
});
