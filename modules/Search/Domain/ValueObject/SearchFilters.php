<?php

declare(strict_types=1);

namespace Modules\Search\Domain\ValueObject;

class SearchFilters
{
    /**
     * @param  array<string>|null  $entityTypes
     * @param  array<string>|null  $statuses
     * @param  array<string>|null  $categories
     * @param  array<int>|null  $organizationIds
     * @param  array<int>|null  $employeeIds
     * @param  array<float>|null  $amountRange
     * @param  array<string>|null  $tags
     * @param  array<string, mixed>|null  $customFilters
     */
    public function __construct(
        public readonly ?array $entityTypes = null,
        public readonly ?array $statuses = null,
        public readonly ?array $categories = null,
        public readonly ?array $organizationIds = null,
        public readonly ?array $employeeIds = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly ?array $amountRange = null,
        public readonly ?bool $isActive = null,
        public readonly ?bool $isVerified = null,
        public readonly ?bool $isFeatured = null,
        public readonly ?array $tags = null,
        public readonly ?array $customFilters = null,
    ) {}

    /**
     * Convert filters to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'entity_types' => $this->entityTypes,
            'statuses' => $this->statuses,
            'categories' => $this->categories,
            'organization_ids' => $this->organizationIds,
            'user_ids' => $this->employeeIds,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'amount_range' => $this->amountRange,
            'is_active' => $this->isActive,
            'is_verified' => $this->isVerified,
            'is_featured' => $this->isFeatured,
            'tags' => $this->tags,
            'custom' => $this->customFilters,
        ], fn (string|bool|array|null $value): bool => $value !== null);
    }

    /**
     * Check if filters are empty.
     */
    public function isEmpty(): bool
    {
        return $this->toArray() === [];
    }

    /**
     * Create filters from request data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            entityTypes: $data['entity_types'] ?? null,
            statuses: $data['statuses'] ?? null,
            categories: $data['categories'] ?? null,
            organizationIds: $data['organization_ids'] ?? null,
            employeeIds: $data['user_ids'] ?? null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            amountRange: $data['amount_range'] ?? null,
            isActive: $data['is_active'] ?? null,
            isVerified: $data['is_verified'] ?? null,
            isFeatured: $data['is_featured'] ?? null,
            tags: $data['tags'] ?? null,
            customFilters: $data['custom'] ?? null,
        );
    }

    /**
     * Merge with another filter set.
     */
    public function merge(self $other): self
    {
        return new self(
            entityTypes: $other->entityTypes ?? $this->entityTypes,
            statuses: $other->statuses ?? $this->statuses,
            categories: $other->categories ?? $this->categories,
            organizationIds: $other->organizationIds ?? $this->organizationIds,
            employeeIds: $other->employeeIds ?? $this->employeeIds,
            dateFrom: $other->dateFrom ?? $this->dateFrom,
            dateTo: $other->dateTo ?? $this->dateTo,
            amountRange: $other->amountRange ?? $this->amountRange,
            isActive: $other->isActive ?? $this->isActive,
            isVerified: $other->isVerified ?? $this->isVerified,
            isFeatured: $other->isFeatured ?? $this->isFeatured,
            tags: $other->tags ?? $this->tags,
            customFilters: $other->customFilters ?? $this->customFilters,
        );
    }

    /**
     * Build Meilisearch filter string.
     */
    public function toMeilisearchFilter(): string
    {
        $filters = [];

        if ($this->entityTypes !== null) {
            $types = array_map(fn (string $type): string => "\"{$type}\"", $this->entityTypes);
            $filters[] = 'entity_type IN [' . implode(',', $types) . ']';
        }

        if ($this->statuses !== null) {
            $statuses = array_map(fn (string $status): string => "\"{$status}\"", $this->statuses);
            $filters[] = 'status IN [' . implode(',', $statuses) . ']';
        }

        if ($this->categories !== null) {
            $categories = array_map(fn (string $cat): string => "\"{$cat}\"", $this->categories);
            $filters[] = 'category IN [' . implode(',', $categories) . ']';
        }

        if ($this->organizationIds !== null) {
            $filters[] = 'organization_id IN [' . implode(',', $this->organizationIds) . ']';
        }

        if ($this->employeeIds !== null) {
            $filters[] = 'user_id IN [' . implode(',', $this->employeeIds) . ']';
        }

        if ($this->dateFrom !== null) {
            $filters[] = "created_at >= \"{$this->dateFrom}\"";
        }

        if ($this->dateTo !== null) {
            $filters[] = "created_at <= \"{$this->dateTo}\"";
        }

        if ($this->amountRange !== null && count($this->amountRange) === 2) {
            [$min, $max] = $this->amountRange;
            $filters[] = "amount >= {$min} AND amount <= {$max}";
        }

        if ($this->isActive !== null) {
            $filters[] = 'is_active = ' . ($this->isActive ? 'true' : 'false');
        }

        if ($this->isVerified !== null) {
            $filters[] = 'is_verified = ' . ($this->isVerified ? 'true' : 'false');
        }

        if ($this->isFeatured !== null) {
            $filters[] = 'is_featured = ' . ($this->isFeatured ? 'true' : 'false');
        }

        return implode(' AND ', $filters);
    }
}
