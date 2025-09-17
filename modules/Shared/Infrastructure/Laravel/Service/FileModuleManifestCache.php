<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Service;

use Modules\Shared\Application\Command\ModuleManifestCacheInterface;
use Modules\Shared\Domain\ValueObject\ModuleManifest;

final readonly class FileModuleManifestCache implements ModuleManifestCacheInterface
{
    private string $cachePath;

    public function __construct(string $basePath)
    {
        $this->cachePath = $basePath . '/bootstrap/cache/modules.php';
    }

    public function store(ModuleManifest $manifest): void
    {
        $content = "<?php\n\nreturn " . var_export($manifest->toArray(), true) . ';';
        file_put_contents($this->cachePath, $content);
    }

    public function retrieve(): ?ModuleManifest
    {
        if (! $this->exists()) {
            return null;
        }

        $data = require $this->cachePath;

        return ModuleManifest::fromArray($data);
    }

    public function clear(): void
    {
        if ($this->exists()) {
            unlink($this->cachePath);
        }
    }

    public function exists(): bool
    {
        return file_exists($this->cachePath);
    }
}
