<?php

declare(strict_types=1);

namespace Modules\DevTools\Application\Command;

final class GenerateCodeCommand
{
    /**
     * @param  array<string, mixed>  $properties
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public string $type, // 'entity', 'command', 'query', 'controller', etc.
        public string $module,
        public string $name,
        public array $properties = [],
        public array $options = []
    ) {}
}
