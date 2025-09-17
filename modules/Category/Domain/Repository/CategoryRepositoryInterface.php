<?php

declare(strict_types=1);

namespace Modules\Category\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Modules\Category\Domain\Model\Category;

interface CategoryRepositoryInterface
{
    public function findById(int $id): ?Category;

    public function findBySlug(string $slug): ?Category;

    /**
     * @return Collection<int, Category>
     */
    public function findAll(): Collection;

    /**
     * @return Collection<int, Category>
     */
    public function findActive(): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category;

    public function save(Category $category): Category;

    public function delete(Category $category): bool;

    public function existsBySlug(string $slug, ?int $excludeId = null): bool;

    /**
     * @param  array<int>  $ids
     * @return Collection<int, Category>
     */
    public function getByIds(array $ids): Collection;

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Category>
     */
    public function findByFilters(array $filters = []): Collection;
}
