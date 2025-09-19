<?php

declare(strict_types=1);

namespace Modules\Category\Application\Query;

use Modules\Category\Application\ReadModel\CategoryReadModel;
use Modules\Category\Domain\Repository\CategoryRepositoryInterface;

final readonly class ListCategoriesQueryHandler
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    /**
     * @return array<int, CategoryReadModel>
     */
    public function handle(ListCategoriesQuery $query): array
    {
        $filters = [];

        if ($query->activeOnly) {
            $filters['status'] = 'active';
        }

        $categories = $this->categoryRepository->findByFilters($filters);

        return array_map(
            fn ($category): CategoryReadModel => CategoryReadModel::fromDomainModel($category),
            $categories->toArray()
        );
    }
}
