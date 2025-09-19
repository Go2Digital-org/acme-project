<?php

declare(strict_types=1);

namespace Modules\Category\Application\Command;

final class UpdateCategoryCommand
{
    public function __construct(
        public string $id,
        /** @var array<string, mixed> */
        public array $name,
        /** @var array<string, mixed> */
        public array $description,
        public ?string $parentId = null,
        public ?string $icon = null,
        public ?int $sortOrder = null,
        public ?bool $isActive = null
    ) {}
}
