<?php

declare(strict_types=1);

namespace Modules\Category\Application\Command;

use Modules\Category\Domain\Exception\CategoryException;
use Modules\Category\Domain\Model\Category;
use Modules\Category\Domain\Repository\CategoryRepositoryInterface;
use Modules\Category\Domain\ValueObject\CategoryStatus;

final readonly class UpdateCategoryCommandHandler
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository
    ) {}

    public function handle(UpdateCategoryCommand $command): Category
    {
        $category = $this->categoryRepository->findById((int) $command->id);

        if (! $category instanceof Category) {
            throw CategoryException::notFound($command->id);
        }

        $category->updateName($command->name);
        $category->updateDescription($command->description);

        if ($command->parentId !== null) {
            $category->setParentId((int) $command->parentId);
        }

        if ($command->icon !== null) {
            $category->setIcon($command->icon);
        }

        if ($command->sortOrder !== null) {
            $category->setSortOrder($command->sortOrder);
        }

        if ($command->isActive !== null) {
            $category->setStatus(
                $command->isActive ? CategoryStatus::active() : CategoryStatus::inactive()
            );
        }

        $this->categoryRepository->save($category);

        return $category;
    }
}
