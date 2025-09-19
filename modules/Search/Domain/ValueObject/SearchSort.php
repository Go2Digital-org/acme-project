<?php

declare(strict_types=1);

namespace Modules\Search\Domain\ValueObject;

use InvalidArgumentException;

class SearchSort
{
    public const DIRECTION_ASC = 'asc';

    public const DIRECTION_DESC = 'desc';

    public const FIELD_RELEVANCE = '_relevance';

    public const FIELD_CREATED_AT = 'created_at';

    public const FIELD_UPDATED_AT = 'updated_at';

    public const FIELD_NAME = 'name';

    public const FIELD_AMOUNT = 'amount';

    public const FIELD_DATE = 'date';

    public function __construct(
        public readonly string $field = self::FIELD_RELEVANCE,
        public readonly string $direction = self::DIRECTION_DESC,
    ) {
        $this->validate();
    }

    /**
     * Convert to array.
     *
     * @return array{field: string, direction: string}
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'direction' => $this->direction,
        ];
    }

    /**
     * Convert to Meilisearch sort format.
     *
     * @return array<int, string>
     */
    public function toMeilisearchSort(): array
    {
        if ($this->field === self::FIELD_RELEVANCE) {
            return [];
        }

        return [sprintf('%s:%s', $this->field, $this->direction)];
    }

    /**
     * Create from string format (e.g., "created_at:desc").
     */
    public static function fromString(string $sort): self
    {
        if (str_contains($sort, ':')) {
            $parts = explode(':', $sort);
            $direction = array_pop($parts);
            $field = implode(':', $parts);

            return new self($field, $direction);
        }

        return new self($sort);
    }

    /**
     * Convert to string format.
     */
    public function toString(): string
    {
        if ($this->field === self::FIELD_RELEVANCE) {
            return '';
        }

        return sprintf('%s:%s', $this->field, $this->direction);
    }

    /**
     * Check if sorting by relevance.
     */
    public function isRelevanceSort(): bool
    {
        return $this->field === self::FIELD_RELEVANCE;
    }

    /**
     * Check if sorting in ascending order.
     */
    public function isAscending(): bool
    {
        return $this->direction === self::DIRECTION_ASC;
    }

    /**
     * Check if sorting in descending order.
     */
    public function isDescending(): bool
    {
        return $this->direction === self::DIRECTION_DESC;
    }

    /**
     * Create a new instance with reversed direction.
     */
    public function reverse(): self
    {
        $newDirection = $this->direction === self::DIRECTION_ASC
            ? self::DIRECTION_DESC
            : self::DIRECTION_ASC;

        return new self($this->field, $newDirection);
    }

    /**
     * Validate sort parameters.
     */
    private function validate(): void
    {
        if (! in_array($this->direction, [self::DIRECTION_ASC, self::DIRECTION_DESC], true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid sort direction: %s', $this->direction),
            );
        }
    }
}
