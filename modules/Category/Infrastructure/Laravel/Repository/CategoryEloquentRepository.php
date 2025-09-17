<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Laravel\Repository;

use Illuminate\Database\Eloquent\Collection;
use Modules\Category\Domain\Model\Category;
use Modules\Category\Domain\Repository\CategoryRepositoryInterface;
use RuntimeException;

class CategoryEloquentRepository implements CategoryRepositoryInterface
{
    public function findById(int $id): ?Category
    {
        return Category::find($id);
    }

    public function findBySlug(string $slug): ?Category
    {
        return Category::where('slug', $slug)->first();
    }

    /**
     * @return Collection<int, Category>
     */
    public function findAll(): Collection
    {
        return Category::ordered()->get();
    }

    /**
     * @return Collection<int, Category>
     */
    public function findActive(): Collection
    {
        return Category::active()->ordered()->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        return Category::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        $fresh = $category->fresh();
        if ($fresh === null) {
            throw new RuntimeException('Category not found after update');
        }

        return $fresh;
    }

    public function delete(Category $category): bool
    {
        $result = $category->delete();

        return $result !== false && $result !== null;
    }

    public function existsBySlug(string $slug, ?int $excludeId = null): bool
    {
        $query = Category::where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * @param  array<int>  $ids
     * @return Collection<int, Category>
     */
    public function getByIds(array $ids): Collection
    {
        return Category::whereIn('id', $ids)->ordered()->get();
    }

    public function save(Category $category): Category
    {
        $category->save();

        return $category;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Category>
     */
    public function findByFilters(array $filters = []): Collection
    {
        $query = Category::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name->en', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $query->ordered()->get();
    }
}
