<?php

declare(strict_types=1);

use Modules\Search\Domain\ValueObject\SearchSort;

describe('SearchSort Value Object', function () {
    it('creates default sort with relevance and descending order', function () {
        $sort = new SearchSort;

        expect($sort->field)->toBe(SearchSort::FIELD_RELEVANCE)
            ->and($sort->direction)->toBe(SearchSort::DIRECTION_DESC);
    });

    it('creates sort with custom field and direction', function () {
        $sort = new SearchSort(SearchSort::FIELD_CREATED_AT, SearchSort::DIRECTION_ASC);

        expect($sort->field)->toBe(SearchSort::FIELD_CREATED_AT)
            ->and($sort->direction)->toBe(SearchSort::DIRECTION_ASC);
    });

    it('validates sort direction on construction', function () {
        expect(fn () => new SearchSort(SearchSort::FIELD_NAME, 'invalid'))
            ->toThrow(InvalidArgumentException::class, 'Invalid sort direction: invalid');
    });

    it('converts to array correctly', function () {
        $sort = new SearchSort(SearchSort::FIELD_AMOUNT, SearchSort::DIRECTION_ASC);
        $array = $sort->toArray();

        expect($array)->toBe([
            'field' => SearchSort::FIELD_AMOUNT,
            'direction' => SearchSort::DIRECTION_ASC,
        ]);
    });

    it('converts to meilisearch sort format for non-relevance fields', function () {
        $sort = new SearchSort(SearchSort::FIELD_CREATED_AT, SearchSort::DIRECTION_DESC);
        $meilisearchSort = $sort->toMeilisearchSort();

        expect($meilisearchSort)->toBe(['created_at:desc']);
    });

    it('returns empty array for relevance sort in meilisearch format', function () {
        $sort = new SearchSort(SearchSort::FIELD_RELEVANCE, SearchSort::DIRECTION_DESC);
        $meilisearchSort = $sort->toMeilisearchSort();

        expect($meilisearchSort)->toBe([]);
    });

    it('creates from string format with field and direction', function () {
        $sort = SearchSort::fromString('created_at:asc');

        expect($sort->field)->toBe('created_at')
            ->and($sort->direction)->toBe(SearchSort::DIRECTION_ASC);
    });

    it('creates from string format with field only', function () {
        $sort = SearchSort::fromString('amount');

        expect($sort->field)->toBe('amount')
            ->and($sort->direction)->toBe(SearchSort::DIRECTION_DESC);
    });

    it('handles complex string format with multiple colons', function () {
        $sort = SearchSort::fromString('nested:field:asc');

        expect($sort->field)->toBe('nested:field')
            ->and($sort->direction)->toBe(SearchSort::DIRECTION_ASC);
    });

    it('converts to string format correctly', function () {
        $sort = new SearchSort(SearchSort::FIELD_UPDATED_AT, SearchSort::DIRECTION_ASC);
        $string = $sort->toString();

        expect($string)->toBe('updated_at:asc');
    });

    it('returns empty string for relevance sort', function () {
        $sort = new SearchSort(SearchSort::FIELD_RELEVANCE, SearchSort::DIRECTION_DESC);
        $string = $sort->toString();

        expect($string)->toBe('');
    });

    it('identifies relevance sort correctly', function () {
        $relevanceSort = new SearchSort(SearchSort::FIELD_RELEVANCE);
        $fieldSort = new SearchSort(SearchSort::FIELD_NAME);

        expect($relevanceSort->isRelevanceSort())->toBeTrue()
            ->and($fieldSort->isRelevanceSort())->toBeFalse();
    });

    it('identifies ascending sort correctly', function () {
        $ascSort = new SearchSort(SearchSort::FIELD_AMOUNT, SearchSort::DIRECTION_ASC);
        $descSort = new SearchSort(SearchSort::FIELD_AMOUNT, SearchSort::DIRECTION_DESC);

        expect($ascSort->isAscending())->toBeTrue()
            ->and($descSort->isAscending())->toBeFalse();
    });

    it('identifies descending sort correctly', function () {
        $descSort = new SearchSort(SearchSort::FIELD_AMOUNT, SearchSort::DIRECTION_DESC);
        $ascSort = new SearchSort(SearchSort::FIELD_AMOUNT, SearchSort::DIRECTION_ASC);

        expect($descSort->isDescending())->toBeTrue()
            ->and($ascSort->isDescending())->toBeFalse();
    });

    it('reverses sort direction correctly', function () {
        $ascSort = new SearchSort(SearchSort::FIELD_DATE, SearchSort::DIRECTION_ASC);
        $reversed = $ascSort->reverse();

        expect($reversed->field)->toBe(SearchSort::FIELD_DATE)
            ->and($reversed->direction)->toBe(SearchSort::DIRECTION_DESC)
            ->and($ascSort->direction)->toBe(SearchSort::DIRECTION_ASC); // Original unchanged
    });

    it('reverses descending to ascending', function () {
        $descSort = new SearchSort(SearchSort::FIELD_CREATED_AT, SearchSort::DIRECTION_DESC);
        $reversed = $descSort->reverse();

        expect($reversed->direction)->toBe(SearchSort::DIRECTION_ASC);
    });

    it('preserves field when reversing', function () {
        $sort = new SearchSort(SearchSort::FIELD_UPDATED_AT, SearchSort::DIRECTION_ASC);
        $reversed = $sort->reverse();

        expect($reversed->field)->toBe(SearchSort::FIELD_UPDATED_AT);
    });

    it('has correct field constants', function () {
        expect(SearchSort::FIELD_RELEVANCE)->toBe('_relevance')
            ->and(SearchSort::FIELD_CREATED_AT)->toBe('created_at')
            ->and(SearchSort::FIELD_UPDATED_AT)->toBe('updated_at')
            ->and(SearchSort::FIELD_NAME)->toBe('name')
            ->and(SearchSort::FIELD_AMOUNT)->toBe('amount')
            ->and(SearchSort::FIELD_DATE)->toBe('date');
    });

    it('has correct direction constants', function () {
        expect(SearchSort::DIRECTION_ASC)->toBe('asc')
            ->and(SearchSort::DIRECTION_DESC)->toBe('desc');
    });

    it('validates valid directions pass through', function () {
        $ascSort = new SearchSort(SearchSort::FIELD_NAME, SearchSort::DIRECTION_ASC);
        $descSort = new SearchSort(SearchSort::FIELD_NAME, SearchSort::DIRECTION_DESC);

        expect($ascSort->direction)->toBe(SearchSort::DIRECTION_ASC)
            ->and($descSort->direction)->toBe(SearchSort::DIRECTION_DESC);
    });

    it('supports custom field names', function () {
        $sort = new SearchSort('custom_field', SearchSort::DIRECTION_ASC);

        expect($sort->field)->toBe('custom_field')
            ->and($sort->direction)->toBe(SearchSort::DIRECTION_ASC);
    });

    it('converts custom field to meilisearch format', function () {
        $sort = new SearchSort('priority', SearchSort::DIRECTION_DESC);
        $meilisearchSort = $sort->toMeilisearchSort();

        expect($meilisearchSort)->toBe(['priority:desc']);
    });

    it('creates immutable instances', function () {
        $original = new SearchSort(SearchSort::FIELD_AMOUNT, SearchSort::DIRECTION_ASC);
        $reversed = $original->reverse();

        expect($original->direction)->toBe(SearchSort::DIRECTION_ASC)
            ->and($reversed->direction)->toBe(SearchSort::DIRECTION_DESC)
            ->and($original)->not->toBe($reversed);
    });

    it('handles empty field name gracefully', function () {
        $sort = new SearchSort('', SearchSort::DIRECTION_ASC);

        expect($sort->field)->toBe('')
            ->and($sort->direction)->toBe(SearchSort::DIRECTION_ASC);
    });

    it('handles case sensitivity in validation', function () {
        expect(fn () => new SearchSort(SearchSort::FIELD_NAME, 'ASC'))
            ->toThrow(InvalidArgumentException::class, 'Invalid sort direction: ASC');

        expect(fn () => new SearchSort(SearchSort::FIELD_NAME, 'DESC'))
            ->toThrow(InvalidArgumentException::class, 'Invalid sort direction: DESC');
    });

    it('creates consistent string representation', function () {
        $sort1 = new SearchSort(SearchSort::FIELD_AMOUNT, SearchSort::DIRECTION_ASC);
        $sort2 = SearchSort::fromString($sort1->toString());

        expect($sort2->field)->toBe($sort1->field)
            ->and($sort2->direction)->toBe($sort1->direction);
    });

    it('handles special characters in field names', function () {
        $sort = new SearchSort('field_with_underscore', SearchSort::DIRECTION_ASC);

        expect($sort->field)->toBe('field_with_underscore')
            ->and($sort->toString())->toBe('field_with_underscore:asc');
    });

    it('supports dotted field names for nested fields', function () {
        $sort = new SearchSort('user.name', SearchSort::DIRECTION_DESC);

        expect($sort->field)->toBe('user.name')
            ->and($sort->toMeilisearchSort())->toBe(['user.name:desc']);
    });

    it('round trip conversion preserves data', function () {
        $original = new SearchSort(SearchSort::FIELD_DATE, SearchSort::DIRECTION_ASC);
        $fromString = SearchSort::fromString($original->toString());

        expect($fromString->field)->toBe($original->field)
            ->and($fromString->direction)->toBe($original->direction);
    });

    it('supports all predefined field constants in meilisearch format', function () {
        $fields = [
            SearchSort::FIELD_CREATED_AT,
            SearchSort::FIELD_UPDATED_AT,
            SearchSort::FIELD_NAME,
            SearchSort::FIELD_AMOUNT,
            SearchSort::FIELD_DATE,
        ];

        foreach ($fields as $field) {
            $sort = new SearchSort($field, SearchSort::DIRECTION_ASC);
            $meilisearchSort = $sort->toMeilisearchSort();

            expect($meilisearchSort)->toBe(["{$field}:asc"]);
        }
    });

    it('creates new instance for each operation', function () {
        $sort1 = new SearchSort(SearchSort::FIELD_AMOUNT, SearchSort::DIRECTION_ASC);
        $sort2 = $sort1->reverse();
        $sort3 = $sort1->reverse();

        expect($sort2)->not->toBe($sort3)
            ->and($sort1)->not->toBe($sort2)
            ->and($sort1)->not->toBe($sort3);
    });
});
