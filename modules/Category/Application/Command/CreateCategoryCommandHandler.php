<?php

declare(strict_types=1);

namespace Modules\Category\Application\Command;

use Modules\Category\Domain\Model\Category;
use Modules\Category\Domain\Repository\CategoryRepositoryInterface;
use Modules\Category\Domain\ValueObject\CategorySlug;
use Modules\Category\Domain\ValueObject\CategoryStatus;

final readonly class CreateCategoryCommandHandler
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    public function handle(CreateCategoryCommand $command): Category
    {
        $slug = CategorySlug::fromText($command->name['en'] ?? array_values($command->name)[0]);

        $categoryData = [
            'name' => $command->name,
            'description' => $command->description,
            'slug' => (string) $slug,
            'icon' => $command->icon,
            'sort_order' => $command->sortOrder,
            'status' => $command->isActive ? CategoryStatus::active() : CategoryStatus::inactive(),
        ];

        return $this->categoryRepository->create($categoryData);
    }
}
