<?php

declare(strict_types=1);

namespace Modules\Category\Application\Command;

final class CreateCategoryCommand
{
    /**
     * @param  array<string, mixed>  $name  Translatable field
     * @param  array<string, mixed>  $description  Translatable field
     */
    public function __construct(
        /** @var array<string, mixed> */
        public array $name,
        /** @var array<string, mixed> */
        public array $description,
        public ?string $parentId = null,
        public ?string $icon = null,
        public int $sortOrder = 0,
        public bool $isActive = true
    ) {}
}
