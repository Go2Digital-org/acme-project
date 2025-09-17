<?php

declare(strict_types=1);

namespace Modules\Category\Application\ReadModel;

use Modules\Category\Domain\Model\Category;

final class CategoryReadModel
{
    /**
     * @param  array<string, string>  $name
     * @param  array<string, string>  $description
     */
    public function __construct(
        public string $id,
        public array $name,
        public array $description,
        public string $slug,
        public ?string $parentId,
        public ?string $icon,
        public int $sortOrder,
        public string $status,
        public int $campaignCount = 0,
        public ?string $createdAt = null,
        public ?string $updatedAt = null
    ) {}

    public static function fromDomainModel(Category $category): self
    {
        return new self(
            id: (string) $category->getId(),
            name: $category->name ?? ['en' => $category->getName()],
            description: $category->description ?? ($category->getDescription() ? ['en' => $category->getDescription()] : ['en' => '']),
            slug: $category->getSlug(),
            parentId: $category->getParentId() ? (string) $category->getParentId() : null,
            icon: $category->getIcon(),
            sortOrder: $category->getSortOrder(),
            status: (string) $category->getStatus()->value,
            campaignCount: $category->getCampaignCount(),
            createdAt: $category->getCreatedAt()?->format('Y-m-d H:i:s'),
            updatedAt: $category->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}
