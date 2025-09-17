<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Service;

use Modules\Shared\Domain\ValueObject\ModuleManifest;

interface ModuleDiscoveryInterface
{
    /**
     * Discover all modules and their components.
     *
     * @return ModuleManifest The discovered module manifest
     */
    public function discover(): ModuleManifest;

    /**
     * Check if discovery cache should be used.
     *
     * @return bool True if cache should be used, false for fresh discovery
     */
    public function shouldUseCache(): bool;

    /**
     * Clear the discovery cache.
     */
    public function clearCache(): void;
}
