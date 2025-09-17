<?php

declare(strict_types=1);

namespace Modules\Category\Application\Command;

final class CreateCategoryCommand
{
    /**
     * @param  array<string, string>  $name  Translatable field
     * @param  array<string, string>  $description  Translatable field
     */
    public function __construct(
        public array $name,
        public array $description,
        public ?string $parentId = null,
        public ?string $icon = null,
        public int $sortOrder = 0,
        public bool $isActive = true
    ) {}
}
