<?php

declare(strict_types=1);

namespace Modules\Category\Domain\Exception;

use RuntimeException;

class CategoryException extends RuntimeException
{
    public static function slugAlreadyExists(string $slug): self
    {
        return new self("Category with slug '{$slug}' already exists");
    }

    public static function notFound(int|string|null $id = null): self
    {
        if ($id === null) {
            return new self('Category not found');
        }

        return new self("Category with ID {$id} not found");
    }

    public static function categoryNotFound(int $id): self
    {
        return new self("Category with ID {$id} not found");
    }

    public static function cannotDeleteCategoryWithCampaigns(int $id): self
    {
        return new self("Cannot delete category with ID {$id} because it has associated campaigns");
    }
}
