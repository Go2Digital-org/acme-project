<?php

declare(strict_types=1);

namespace Modules\Import\Application\Command;

final class StartImportCommand
{
    /**
     * @param  array<string, string>  $mapping
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public string $type, // 'campaigns', 'users', 'donations', etc.
        public string $filePath,
        public array $mapping = [],
        public array $options = [],
        public ?string $organizationId = null
    ) {}
}
