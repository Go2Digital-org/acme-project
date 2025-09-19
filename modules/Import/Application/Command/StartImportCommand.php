<?php

declare(strict_types=1);

namespace Modules\Import\Application\Command;

final class StartImportCommand
{
    /**
     * @param  array<string, mixed>  $mapping
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public string $type, // 'campaigns', 'users', 'donations', etc.
        public string $filePath,
        /** @var array<string, mixed> */
        public array $mapping = [],
        /** @var array<string, mixed> */
        public array $options = [],
        public ?string $organizationId = null
    ) {}
}
