<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Shared\Domain\Model\Page;

interface PageRepositoryInterface
{
    /**
     * Create a new page.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Page;

    /**
     * Find a page by ID.
     */
    public function findById(int $id): ?Page;

    /**
     * Find a page by slug.
     */
    public function findBySlug(string $slug): ?Page;

    /**
     * Find multiple pages by their slugs.
     *
     * @param  list<string>  $slugs
     * @return Collection<string, Page>
     */
    public function findBySlugs(array $slugs): Collection;

    /**
     * Update a page by ID.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function updateById(int $id, array $data): bool;

    /**
     * Delete a page by ID.
     */
    public function deleteById(int $id): bool;

    /**
     * Get all published pages ordered by their display order.
     *
     * @return Collection<int, Page>
     */
    public function getPublishedPages(): Collection;

    /**
     * Get all draft pages ordered by their display order.
     *
     * @return Collection<int, Page>
     */
    public function getDraftPages(): Collection;

    /**
     * Get all pages with pagination.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Page>
     */
    public function paginate(
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'order',
        string $sortOrder = 'asc',
    ): LengthAwarePaginator;

    /**
     * Search pages by title and content in current locale.
     *
     * @return Collection<int, Page>
     */
    public function search(string $query, string $locale = 'en', ?string $status = null): Collection;

    /**
     * Get pages by template.
     *
     * @return Collection<int, Page>
     */
    public function getByTemplate(string $template): Collection;

    /**
     * Check if slug exists for a different page.
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool;

    /**
     * Get next order value for new page.
     */
    public function getNextOrder(): int;

    /**
     * Reorder pages by updating their order values.
     *
     * @param  list<int>  $pageIds
     */
    public function reorder(array $pageIds): bool;
}
