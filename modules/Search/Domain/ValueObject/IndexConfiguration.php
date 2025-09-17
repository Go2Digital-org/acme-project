<?php

declare(strict_types=1);

namespace Modules\Search\Domain\ValueObject;

class IndexConfiguration
{
    /**
     * @param  array<string>  $searchableFields
     * @param  array<string>  $filterableFields
     * @param  array<string>  $sortableFields
     * @param  array<string>  $rankingRules
     * @param  array<string>  $stopWords
     * @param  array<string, array<string>>  $synonyms
     * @param  array<string, mixed>  $faceting
     * @param  array<string>  $displayedAttributes
     */
    public function __construct(
        public readonly string $primaryKey = 'id',
        public readonly array $searchableFields = [],
        public readonly array $filterableFields = [],
        public readonly array $sortableFields = [],
        public readonly array $rankingRules = [
            'words',
            'typo',
            'proximity',
            'attribute',
            'sort',
            'exactness',
        ],
        public readonly array $stopWords = [],
        public readonly array $synonyms = [],
        public readonly ?string $distinctAttribute = null,
        public readonly array $faceting = [
            'maxValuesPerFacet' => 100,
        ],
        public readonly array $displayedAttributes = ['*'],
        public readonly bool $typoToleranceEnabled = true,
        public readonly int $maxTotalHits = 1000,
    ) {}

    /**
     * Convert to Meilisearch settings format.
     *
     * @return array<string, mixed>
     */
    public function toMeilisearchSettings(): array
    {
        $settings = [
            'searchableAttributes' => $this->searchableFields,
            'filterableAttributes' => $this->filterableFields,
            'sortableAttributes' => $this->sortableFields,
            'rankingRules' => $this->rankingRules,
            'displayedAttributes' => $this->displayedAttributes,
        ];

        if ($this->stopWords !== []) {
            $settings['stopWords'] = $this->stopWords;
        }

        if ($this->synonyms !== []) {
            $settings['synonyms'] = $this->synonyms;
        }

        if ($this->distinctAttribute !== null) {
            $settings['distinctAttribute'] = $this->distinctAttribute;
        }

        if ($this->faceting !== []) {
            $settings['faceting'] = $this->faceting;
        }

        $settings['typoTolerance'] = [
            'enabled' => $this->typoToleranceEnabled,
            'minWordSizeForTypos' => [
                'oneTypo' => 5,
                'twoTypos' => 9,
            ],
        ];

        $settings['pagination'] = [
            'maxTotalHits' => $this->maxTotalHits,
        ];

        return $settings;
    }

    /**
     * Create configuration from array.
     *
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            primaryKey: $config['primaryKey'] ?? 'id',
            searchableFields: $config['searchableFields'] ?? [],
            filterableFields: $config['filterableFields'] ?? [],
            sortableFields: $config['sortableFields'] ?? [],
            rankingRules: $config['rankingRules'] ?? [
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
            ],
            stopWords: $config['stopWords'] ?? [],
            synonyms: $config['synonyms'] ?? [],
            distinctAttribute: $config['distinctAttribute'] ?? null,
            faceting: $config['faceting'] ?? ['maxValuesPerFacet' => 100],
            displayedAttributes: $config['displayedAttributes'] ?? ['*'],
            typoToleranceEnabled: $config['typoToleranceEnabled'] ?? true,
            maxTotalHits: $config['maxTotalHits'] ?? 1000,
        );
    }

    /**
     * Merge with another configuration.
     */
    public function merge(self $other): self
    {
        return new self(
            primaryKey: $other->primaryKey,
            searchableFields: array_values(array_unique(array_merge($this->searchableFields, $other->searchableFields))),
            filterableFields: array_values(array_unique(array_merge($this->filterableFields, $other->filterableFields))),
            sortableFields: array_values(array_unique(array_merge($this->sortableFields, $other->sortableFields))),
            rankingRules: $other->rankingRules,
            stopWords: array_values(array_unique(array_merge($this->stopWords, $other->stopWords))),
            synonyms: array_merge($this->synonyms, $other->synonyms),
            distinctAttribute: $other->distinctAttribute ?? $this->distinctAttribute,
            faceting: array_merge($this->faceting, $other->faceting),
            displayedAttributes: $other->displayedAttributes,
            typoToleranceEnabled: $other->typoToleranceEnabled,
            maxTotalHits: $other->maxTotalHits,
        );
    }

    /**
     * Validate configuration.
     */
    public function validate(): bool
    {
        if ($this->primaryKey === '' || $this->primaryKey === '0') {
            return false;
        }

        if ($this->searchableFields === []) {
            return false;
        }

        foreach ($this->rankingRules as $rule) {
            if (! in_array($rule, ['words', 'typo', 'proximity', 'attribute', 'sort', 'exactness'], true)
                && ! str_contains($rule, ':')) {
                return false;
            }
        }

        return true;
    }
}
