<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Service;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Modules\Shared\Domain\Service\ModuleDiscoveryInterface;
use Modules\Shared\Domain\ValueObject\ModuleManifest;

final readonly class FilesystemModuleDiscoveryService implements ModuleDiscoveryInterface
{
    private const EXCLUDED_PROVIDERS = ['AdminPanelProvider.php', 'TenantPanelProvider.php'];

    public function __construct(
        private string $basePath,
        private string $environment,
    ) {}

    public function discover(): ModuleManifest
    {
        $serviceProviders = $this->discoverServiceProviders();
        $apiHandlers = $this->discoverApiHandlers();

        return new ModuleManifest(
            array_values($serviceProviders),
            array_values($apiHandlers['processors']),
            array_values($apiHandlers['providers']),
        );
    }

    public function shouldUseCache(): bool
    {
        return ! in_array($this->environment, ['local', 'development', 'testing']);
    }

    public function clearCache(): void
    {
        // No-op for filesystem discovery
    }

    /**
     * @return array<int, class-string<ServiceProvider>>
     */
    private function discoverServiceProviders(): array
    {
        $providers = [];

        foreach (File::directories($this->basePath . '/modules') as $modulePath) {
            if (! is_string($modulePath)) {
                continue;
            }

            $providerPath = "{$modulePath}/Infrastructure/Laravel/Provider";

            if (! File::exists($providerPath)) {
                continue;
            }

            foreach (File::files($providerPath) as $file) {
                $filename = $file->getFilename();

                if (! str_ends_with($filename, 'Provider.php')) {
                    continue;
                }

                if (in_array($filename, self::EXCLUDED_PROVIDERS)) {
                    continue;
                }

                $moduleName = basename($modulePath);
                $className = "Modules\\{$moduleName}\\Infrastructure\\Laravel\\Provider\\"
                    . $file->getFilenameWithoutExtension();

                if (class_exists($className) && is_subclass_of($className, ServiceProvider::class)) {
                    $providers[] = $className;
                }
            }
        }

        return $providers;
    }

    /**
     * @return array{processors: array<int, class-string>, providers: array<int, class-string>}
     */
    private function discoverApiHandlers(): array
    {
        $handlers = [
            'processors' => [],
            'providers' => [],
        ];

        foreach (File::directories($this->basePath . '/modules') as $modulePath) {
            if (! is_string($modulePath)) {
                continue;
            }

            $handlersPath = "{$modulePath}/Infrastructure/ApiPlatform/Handler";

            if (! File::exists($handlersPath)) {
                continue;
            }

            $moduleName = basename($modulePath);

            // Discover processors
            $processorsPath = "{$handlersPath}/Processor";
            if (File::exists($processorsPath)) {
                foreach (File::files($processorsPath) as $file) {
                    if (! str_ends_with($file->getFilename(), '.php')) {
                        continue;
                    }

                    $className = "Modules\\{$moduleName}\\Infrastructure\\ApiPlatform\\Handler\\Processor\\"
                        . $file->getFilenameWithoutExtension();

                    if (class_exists($className)) {
                        $handlers['processors'][] = $className;
                    }
                }
            }

            // Discover providers
            $providersPath = "{$handlersPath}/Provider";
            if (File::exists($providersPath)) {
                foreach (File::files($providersPath) as $file) {
                    if (! str_ends_with($file->getFilename(), '.php')) {
                        continue;
                    }

                    $className = "Modules\\{$moduleName}\\Infrastructure\\ApiPlatform\\Handler\\Provider\\"
                        . $file->getFilenameWithoutExtension();

                    if (class_exists($className)) {
                        $handlers['providers'][] = $className;
                    }
                }
            }
        }

        return $handlers;
    }
}
