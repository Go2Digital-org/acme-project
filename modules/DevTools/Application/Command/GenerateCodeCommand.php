<?php

declare(strict_types=1);

namespace Modules\DevTools\Application\Command;

final class GenerateCodeCommand
{
    public function __construct(
        public string $type, // 'entity', 'command', 'query', 'controller', etc.
        public string $module,
        public string $name,
        /** @var array<string, mixed> */
        public array $properties = [],
        /** @var array<string, mixed> */
        public array $options = []
    ) {}
}
