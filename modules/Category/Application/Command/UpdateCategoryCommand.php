<?php

declare(strict_types=1);

namespace Modules\Category\Application\Command;

final class UpdateCategoryCommand
{
    /**
     * @param  array<string, string>  $name
     * @param  array<string, string>  $description
     */
    public function __construct(
        public string $id,
        public array $name,
        public array $description,
        public ?string $parentId = null,
        public ?string $icon = null,
        public ?int $sortOrder = null,
        public ?bool $isActive = null
    ) {}
}
