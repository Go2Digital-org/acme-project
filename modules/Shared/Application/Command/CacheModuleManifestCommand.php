<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Command;

final readonly class CacheModuleManifestCommand
{
    public function __construct(
        public bool $force = false,
    ) {}
}
