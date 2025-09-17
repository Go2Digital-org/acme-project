<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Command;

use Modules\Shared\Domain\ValueObject\ModuleManifest;

interface ModuleManifestCacheInterface
{
    /**
     * Store the module manifest in cache.
     */
    public function store(ModuleManifest $manifest): void;

    /**
     * Retrieve the cached module manifest.
     *
     * @return ModuleManifest|null Returns null if cache doesn't exist
     */
    public function retrieve(): ?ModuleManifest;

    /**
     * Clear the cached manifest.
     */
    public function clear(): void;

    /**
     * Check if a cached manifest exists.
     */
    public function exists(): bool;
}
