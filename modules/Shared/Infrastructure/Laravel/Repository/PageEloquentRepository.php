<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Domain\Model\Page;
use Modules\Shared\Domain\Repository\PageRepositoryInterface;

class PageEloquentRepository implements PageRepositoryInterface
{
    public function __construct(
        private readonly Page $model,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Page
    {
        /** @var Page $page */
        $page = $this->model->newModelQuery()->create($data);

        return $page;
    }

    public function findById(int $id): ?Page
    {
        return $this->model->find($id);
    }

    public function findBySlug(string $slug): ?Page
    {
        /** @var Page|null $page */
        $page = $this->model->newQuery()->where('slug', $slug)->first();

        return $page;
    }

    /**
     * @param  array<string>  $slugs
     * @return Collection<string, Page>
     */
    public function findBySlugs(array $slugs): Collection
    {
        if ($slugs === []) {
            return new Collection;
        }

        /** @var Collection<string, Page> $pages */
        $pages = $this->model->newQuery()
            ->whereIn('slug', $slugs)
            ->get()
            ->keyBy('slug'); // Key by slug for easy access

        return $pages;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateById(int $id, array $data): bool
    {
        return $this->model->newQuery()->where('id', $id)->update($data) > 0;
    }

    public function deleteById(int $id): bool
    {
        return $this->model->newQuery()->where('id', $id)->delete() > 0;
    }

    /**
     * @return Collection<int, Page>
     */
    public function getPublishedPages(): Collection
    {
        /** @var Collection<int, Page> $pages */
        $pages = $this->model->newQuery()->published()->ordered()->get();

        return $pages;
    }

    /**
     * @return Collection<int, Page>
     */
    public function getDraftPages(): Collection
    {
        /** @var Collection<int, Page> $pages */
        $pages = $this->model->newQuery()->draft()->ordered()->get();

        return $pages;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'order',
        string $sortOrder = 'asc',
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $searchTerm = $filters['search'];
            $locale = $filters['locale'] ?? app()->getLocale();

            $query->where(function (Builder $q) use ($searchTerm, $locale): void {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.{$locale}')) LIKE ?", ["%{$searchTerm}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(content, '$.{$locale}')) LIKE ?", ["%{$searchTerm}%"]);
            });
        }

        /** @var LengthAwarePaginator<int, Page> $paginator */
        $paginator = $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        return $paginator;
    }

    /**
     * @return Collection<int, Page>
     */
    public function search(string $query, string $locale = 'en', ?string $status = null): Collection
    {
        $builder = $this->model->newQuery();

        if ($status) {
            $builder->where('status', $status);
        }

        $builder->where(function (Builder $q) use ($query, $locale): void {
            $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.{$locale}')) LIKE ?", ["%{$query}%"])
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(content, '$.{$locale}')) LIKE ?", ["%{$query}%"])
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta_description, '$.{$locale}')) LIKE ?", ["%{$query}%"])
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta_keywords, '$.{$locale}')) LIKE ?", ["%{$query}%"]);
        });

        /** @var Collection<int, Page> $pages */
        $pages = $builder->ordered()->get();

        return $pages;
    }

    /**
     * Get pages by template.
     *
     * @return Collection<int, Page>
     */
    public function getByTemplate(string $template): Collection
    {
        // Note: Since template column doesn't exist in current migration,
        // returning empty collection for now. This method exists to satisfy interface.
        /** @var Collection<int, Page> $pages */
        $pages = $this->model->newQuery()->whereRaw('1 = 0')->get(); // Always empty

        return $pages;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = DB::table('pages')->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function getNextOrder(): int
    {
        $maxOrder = DB::table('pages')->max('order');

        return ($maxOrder ?? 0) + 1;
    }

    public function reorder(array $pageIds): bool
    {
        return DB::transaction(function () use ($pageIds): bool {
            foreach ($pageIds as $index => $pageId) {
                $this->model->newQuery()->where('id', $pageId)->update(['order' => $index + 1]);
            }

            return true;
        });
    }
}
