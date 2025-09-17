<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Service;

use Modules\Shared\Application\Command\ModuleManifestCacheInterface;
use Modules\Shared\Domain\Service\ModuleDiscoveryInterface;
use Modules\Shared\Domain\ValueObject\ModuleManifest;

final class CachedModuleDiscoveryService implements ModuleDiscoveryInterface
{
    public function __construct(
        private readonly ModuleDiscoveryInterface $filesystemDiscovery,
        private readonly ModuleManifestCacheInterface $cache,
        private readonly string $environment,
    ) {}

    private ?ModuleManifest $manifestCache = null;

    public function discover(): ModuleManifest
    {
        // In-memory cache to avoid multiple file reads
        if ($this->manifestCache instanceof ModuleManifest) {
            return $this->manifestCache;
        }

        if ($this->shouldUseCache() && $this->cache->exists()) {
            $cached = $this->cache->retrieve();
            if ($cached instanceof ModuleManifest) {
                $this->manifestCache = $cached;

                return $cached;
            }
        }

        // Fall back to filesystem discovery
        $manifest = $this->filesystemDiscovery->discover();

        // Store in cache for next time (if appropriate)
        if ($this->shouldUseCache()) {
            $this->cache->store($manifest);
        }

        $this->manifestCache = $manifest;

        return $manifest;
    }

    public function shouldUseCache(): bool
    {
        // Enable caching in all environments for performance
        // Can be disabled via cache.modules.enabled config
        if (! config('cache.modules.enabled', true)) {
            return false;
        }

        // Only skip cache in testing environment
        return $this->environment !== 'testing';
    }

    public function clearCache(): void
    {
        $this->cache->clear();
    }
}
