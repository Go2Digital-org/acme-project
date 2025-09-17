<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Command;

use Modules\Shared\Domain\Service\ModuleDiscoveryInterface;

final readonly class CacheModuleManifestCommandHandler
{
    public function __construct(
        private ModuleDiscoveryInterface $moduleDiscovery,
        private ModuleManifestCacheInterface $cache,
    ) {}

    public function handle(CacheModuleManifestCommand $command): void
    {
        if ($command->force) {
            $this->moduleDiscovery->clearCache();
        }

        $manifest = $this->moduleDiscovery->discover();
        $this->cache->store($manifest);
    }
}
