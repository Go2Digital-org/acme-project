<?php

declare(strict_types=1);

use Modules\Search\Domain\ValueObject\SearchFilters;

describe('SearchFilters Value Object', function () {
    it('creates empty filters with all null values', function () {
        $filters = new SearchFilters;

        expect($filters->entityTypes)->toBeNull()
            ->and($filters->statuses)->toBeNull()
            ->and($filters->categories)->toBeNull()
            ->and($filters->organizationIds)->toBeNull()
            ->and($filters->employeeIds)->toBeNull()
            ->and($filters->dateFrom)->toBeNull()
            ->and($filters->dateTo)->toBeNull()
            ->and($filters->amountRange)->toBeNull()
            ->and($filters->isActive)->toBeNull()
            ->and($filters->isVerified)->toBeNull()
            ->and($filters->isFeatured)->toBeNull()
            ->and($filters->tags)->toBeNull()
            ->and($filters->customFilters)->toBeNull();
    });

    it('creates filters with entity types', function () {
        $entityTypes = ['campaign', 'donation', 'organization'];
        $filters = new SearchFilters(entityTypes: $entityTypes);

        expect($filters->entityTypes)->toBe($entityTypes)
            ->and($filters->statuses)->toBeNull();
    });

    it('creates filters with statuses', function () {
        $statuses = ['active', 'draft', 'completed'];
        $filters = new SearchFilters(statuses: $statuses);

        expect($filters->statuses)->toBe($statuses)
            ->and($filters->entityTypes)->toBeNull();
    });

    it('creates filters with categories', function () {
        $categories = ['environment', 'education', 'health'];
        $filters = new SearchFilters(categories: $categories);

        expect($filters->categories)->toBe($categories)
            ->and($filters->statuses)->toBeNull();
    });

    it('creates filters with organization IDs', function () {
        $orgIds = [1, 2, 3, 5, 8];
        $filters = new SearchFilters(organizationIds: $orgIds);

        expect($filters->organizationIds)->toBe($orgIds)
            ->and($filters->categories)->toBeNull();
    });

    it('creates filters with employee IDs', function () {
        $employeeIds = [10, 20, 30];
        $filters = new SearchFilters(employeeIds: $employeeIds);

        expect($filters->employeeIds)->toBe($employeeIds)
            ->and($filters->organizationIds)->toBeNull();
    });

    it('creates filters with date range', function () {
        $dateFrom = '2024-01-01';
        $dateTo = '2024-12-31';
        $filters = new SearchFilters(dateFrom: $dateFrom, dateTo: $dateTo);

        expect($filters->dateFrom)->toBe($dateFrom)
            ->and($filters->dateTo)->toBe($dateTo)
            ->and($filters->employeeIds)->toBeNull();
    });

    it('creates filters with amount range', function () {
        $amountRange = [100.0, 5000.0];
        $filters = new SearchFilters(amountRange: $amountRange);

        expect($filters->amountRange)->toBe($amountRange)
            ->and($filters->dateFrom)->toBeNull();
    });

    it('creates filters with boolean flags', function () {
        $filters = new SearchFilters(
            isActive: true,
            isVerified: false,
            isFeatured: true
        );

        expect($filters->isActive)->toBeTrue()
            ->and($filters->isVerified)->toBeFalse()
            ->and($filters->isFeatured)->toBeTrue()
            ->and($filters->amountRange)->toBeNull();
    });

    it('creates filters with tags', function () {
        $tags = ['urgent', 'priority', 'environmental'];
        $filters = new SearchFilters(tags: $tags);

        expect($filters->tags)->toBe($tags)
            ->and($filters->isActive)->toBeNull();
    });

    it('creates filters with custom filters', function () {
        $customFilters = ['region' => 'europe', 'type' => 'premium'];
        $filters = new SearchFilters(customFilters: $customFilters);

        expect($filters->customFilters)->toBe($customFilters)
            ->and($filters->tags)->toBeNull();
    });

    it('converts filters to array excluding null values', function () {
        $filters = new SearchFilters(
            entityTypes: ['campaign'],
            statuses: ['active', 'draft'],
            categories: null,
            organizationIds: [1, 2],
            isActive: true,
            isVerified: null,
            tags: ['urgent']
        );

        $array = $filters->toArray();

        expect($array)->toBe([
            'entity_types' => ['campaign'],
            'statuses' => ['active', 'draft'],
            'organization_ids' => [1, 2],
            'is_active' => true,
            'tags' => ['urgent'],
        ]);
    });

    it('filters out null values in toArray', function () {
        $filters = new SearchFilters(
            entityTypes: ['campaign'],
            statuses: null,
            categories: null,
            organizationIds: null,
            isActive: true
        );

        $array = $filters->toArray();

        expect($array)->toHaveKeys(['entity_types', 'is_active'])
            ->and($array)->not->toHaveKeys(['statuses', 'categories', 'organization_ids']);
    });

    it('detects empty filters correctly', function () {
        $emptyFilters = new SearchFilters;
        $nonEmptyFilters = new SearchFilters(entityTypes: ['campaign']);

        expect($emptyFilters->isEmpty())->toBeTrue()
            ->and($nonEmptyFilters->isEmpty())->toBeFalse();
    });

    it('creates filters from array data', function () {
        $data = [
            'entity_types' => ['campaign', 'donation'],
            'statuses' => ['active'],
            'organization_ids' => [1, 2, 3],
            'user_ids' => [10, 20],
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
            'amount_range' => [500.0, 10000.0],
            'is_active' => true,
            'is_verified' => false,
            'is_featured' => null,
            'tags' => ['important'],
            'custom' => ['region' => 'asia'],
        ];

        $filters = SearchFilters::fromArray($data);

        expect($filters->entityTypes)->toBe(['campaign', 'donation'])
            ->and($filters->statuses)->toBe(['active'])
            ->and($filters->organizationIds)->toBe([1, 2, 3])
            ->and($filters->employeeIds)->toBe([10, 20])
            ->and($filters->dateFrom)->toBe('2024-01-01')
            ->and($filters->dateTo)->toBe('2024-12-31')
            ->and($filters->amountRange)->toBe([500.0, 10000.0])
            ->and($filters->isActive)->toBeTrue()
            ->and($filters->isVerified)->toBeFalse()
            ->and($filters->isFeatured)->toBeNull()
            ->and($filters->tags)->toBe(['important'])
            ->and($filters->customFilters)->toBe(['region' => 'asia']);
    });

    it('creates filters from partial array data', function () {
        $data = ['entity_types' => ['campaign'], 'is_active' => true];
        $filters = SearchFilters::fromArray($data);

        expect($filters->entityTypes)->toBe(['campaign'])
            ->and($filters->isActive)->toBeTrue()
            ->and($filters->statuses)->toBeNull()
            ->and($filters->organizationIds)->toBeNull();
    });

    it('creates filters from empty array', function () {
        $filters = SearchFilters::fromArray([]);

        expect($filters->isEmpty())->toBeTrue()
            ->and($filters->entityTypes)->toBeNull()
            ->and($filters->statuses)->toBeNull();
    });

    it('merges filters correctly', function () {
        $base = new SearchFilters(
            entityTypes: ['campaign'],
            statuses: ['active'],
            isActive: true
        );

        $other = new SearchFilters(
            entityTypes: ['donation'],
            categories: ['health'],
            isVerified: true
        );

        $merged = $base->merge($other);

        expect($merged->entityTypes)->toBe(['donation']) // Overwritten
            ->and($merged->statuses)->toBe(['active']) // Preserved from base
            ->and($merged->categories)->toBe(['health']) // Added from other
            ->and($merged->isActive)->toBeTrue() // Preserved from base
            ->and($merged->isVerified)->toBeTrue(); // Added from other
    });

    it('preserves null values during merge when other has null', function () {
        $base = new SearchFilters(entityTypes: ['campaign'], isActive: true);
        $other = new SearchFilters(entityTypes: null, isVerified: false);
        $merged = $base->merge($other);

        expect($merged->entityTypes)->toBe(['campaign']) // Base preserved when other is null
            ->and($merged->isActive)->toBeTrue() // Base preserved
            ->and($merged->isVerified)->toBeFalse(); // Other value used
    });

    it('overwrites base values with other non-null values', function () {
        $base = new SearchFilters(entityTypes: ['campaign'], statuses: ['draft']);
        $other = new SearchFilters(entityTypes: ['donation'], statuses: null);
        $merged = $base->merge($other);

        expect($merged->entityTypes)->toBe(['donation']) // Overwritten by other
            ->and($merged->statuses)->toBe(['draft']); // Base preserved when other is null
    });

    it('builds meilisearch filter for entity types', function () {
        $filters = new SearchFilters(entityTypes: ['campaign', 'donation']);
        $meilisearchFilter = $filters->toMeilisearchFilter();

        expect($meilisearchFilter)->toBe('entity_type IN ["campaign","donation"]');
    });

    it('builds meilisearch filter for statuses', function () {
        $filters = new SearchFilters(statuses: ['active', 'completed']);
        $meilisearchFilter = $filters->toMeilisearchFilter();

        expect($meilisearchFilter)->toBe('status IN ["active","completed"]');
    });

    it('builds meilisearch filter for categories', function () {
        $filters = new SearchFilters(categories: ['environment', 'education']);
        $meilisearchFilter = $filters->toMeilisearchFilter();

        expect($meilisearchFilter)->toBe('category IN ["environment","education"]');
    });

    it('builds meilisearch filter for organization IDs', function () {
        $filters = new SearchFilters(organizationIds: [1, 5, 10]);
        $meilisearchFilter = $filters->toMeilisearchFilter();

        expect($meilisearchFilter)->toBe('organization_id IN [1,5,10]');
    });

    it('builds meilisearch filter for employee IDs', function () {
        $filters = new SearchFilters(employeeIds: [20, 30, 40]);
        $meilisearchFilter = $filters->toMeilisearchFilter();

        expect($meilisearchFilter)->toBe('user_id IN [20,30,40]');
    });

    it('builds meilisearch filter for date from', function () {
        $filters = new SearchFilters(dateFrom: '2024-01-01');
        $meilisearchFilter = $filters->toMeilisearchFilter();

        expect($meilisearchFilter)->toBe('created_at >= "2024-01-01"');
    });

    it('builds meilisearch filter for date to', function () {
        $filters = new SearchFilters(dateTo: '2024-12-31');
        $meilisearchFilter = $filters->toMeilisearchFilter();

        expect($meilisearchFilter)->toBe('created_at <= "2024-12-31"');
    });

    it('builds meilisearch filter for amount range', function () {
        $filters = new SearchFilters(amountRange: [100.5, 5000.0]);
        $meilisearchFilter = $filters->toMeilisearchFilter();

        expect($meilisearchFilter)->toBe('amount >= 100.5 AND amount <= 5000');
    });

    it('ignores invalid amount range', function () {
        $filters = new SearchFilters(amountRange: [100.0]); // Invalid: only one value
        $meilisearchFilter = $filters->toMeilisearchFilter();

        expect($meilisearchFilter)->toBe('');
    });

    it('builds meilisearch filter for boolean flags', function () {
        $activeFilters = new SearchFilters(isActive: true);
        $inactiveFilters = new SearchFilters(isActive: false);
        $verifiedFilters = new SearchFilters(isVerified: true);
        $unverifiedFilters = new SearchFilters(isVerified: false);
        $featuredFilters = new SearchFilters(isFeatured: true);

        expect($activeFilters->toMeilisearchFilter())->toBe('is_active = true')
            ->and($inactiveFilters->toMeilisearchFilter())->toBe('is_active = false')
            ->and($verifiedFilters->toMeilisearchFilter())->toBe('is_verified = true')
            ->and($unverifiedFilters->toMeilisearchFilter())->toBe('is_verified = false')
            ->and($featuredFilters->toMeilisearchFilter())->toBe('is_featured = true');
    });

    it('combines multiple filters with AND', function () {
        $filters = new SearchFilters(
            entityTypes: ['campaign'],
            statuses: ['active'],
            isActive: true
        );

        $meilisearchFilter = $filters->toMeilisearchFilter();

        expect($meilisearchFilter)->toBe(
            'entity_type IN ["campaign"] AND status IN ["active"] AND is_active = true'
        );
    });

    it('handles complex filter combination', function () {
        $filters = new SearchFilters(
            entityTypes: ['campaign', 'donation'],
            organizationIds: [1, 2],
            dateFrom: '2024-01-01',
            dateTo: '2024-12-31',
            amountRange: [500.0, 10000.0],
            isActive: true,
            isVerified: false
        );

        $meilisearchFilter = $filters->toMeilisearchFilter();
        $expectedParts = [
            'entity_type IN ["campaign","donation"]',
            'organization_id IN [1,2]',
            'created_at >= "2024-01-01"',
            'created_at <= "2024-12-31"',
            'amount >= 500 AND amount <= 10000',
            'is_active = true',
            'is_verified = false',
        ];

        expect($meilisearchFilter)->toBe(implode(' AND ', $expectedParts));
    });

    it('returns empty string for no filters', function () {
        $filters = new SearchFilters;
        $meilisearchFilter = $filters->toMeilisearchFilter();

        expect($meilisearchFilter)->toBe('');
    });

    it('handles quotes in string values for meilisearch', function () {
        $filters = new SearchFilters(entityTypes: ['campaign"test', 'donation']);
        $meilisearchFilter = $filters->toMeilisearchFilter();

        expect($meilisearchFilter)->toBe('entity_type IN ["campaign"test","donation"]');
    });

    it('is immutable during operations', function () {
        $original = new SearchFilters(entityTypes: ['campaign'], isActive: true);
        $other = new SearchFilters(statuses: ['active']);
        $merged = $original->merge($other);

        expect($original->statuses)->toBeNull() // Original unchanged
            ->and($merged->statuses)->toBe(['active']) // Merged has new data
            ->and($original->entityTypes)->toBe(['campaign']) // Original preserved
            ->and($merged->entityTypes)->toBe(['campaign']); // Copied to merged
    });

    it('handles empty arrays in filters', function () {
        $filters = new SearchFilters(
            entityTypes: [],
            statuses: [],
            categories: []
        );

        expect($filters->entityTypes)->toBe([])
            ->and($filters->statuses)->toBe([])
            ->and($filters->categories)->toBe([]);
    });

    it('includes empty arrays in toArray output', function () {
        $filters = new SearchFilters(entityTypes: [], isActive: true);
        $array = $filters->toArray();

        expect($array)->toHaveKey('entity_types')
            ->and($array['entity_types'])->toBe([])
            ->and($array)->toHaveKey('is_active')
            ->and($array['is_active'])->toBeTrue();
    });

    it('handles edge case with single amount in range', function () {
        $filters = new SearchFilters(amountRange: [1000.0, 1000.0]);
        $meilisearchFilter = $filters->toMeilisearchFilter();

        expect($meilisearchFilter)->toBe('amount >= 1000 AND amount <= 1000');
    });

    it('preserves order in meilisearch filter construction', function () {
        $filters = new SearchFilters(
            organizationIds: [3, 1, 2],
            employeeIds: [30, 10, 20]
        );

        $meilisearchFilter = $filters->toMeilisearchFilter();

        expect($meilisearchFilter)->toBe(
            'organization_id IN [3,1,2] AND user_id IN [30,10,20]'
        );
    });
});
