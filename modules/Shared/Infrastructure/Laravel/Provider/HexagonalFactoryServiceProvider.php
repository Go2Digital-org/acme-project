<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Provider;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use Throwable;

class HexagonalFactoryServiceProvider extends ServiceProvider
{
    /**
     * Map of model classes to their factory classes.
     *
     * @var array<string, string>
     */
    private static array $factoryMap = [];

    public function register(): void
    {
        $this->discoverAndRegisterFactories();
    }

    public function boot(): void
    {
        $this->registerFactoryResolver();
    }

    /**
     * Get all discovered factory classes.
     *
     * @return array<string>
     */
    public function getDiscoveredFactories(): array
    {
        return array_keys($this->discoverFactories());
    }

    /**
     * Check if a factory exists for a given model.
     */
    public function hasFactoryFor(string $modelClass): bool
    {
        return isset(self::$factoryMap[$modelClass]);
    }

    /**
     * Get the factory class for a given model.
     */
    public function getFactoryFor(string $modelClass): ?string
    {
        return self::$factoryMap[$modelClass] ?? null;
    }

    /**
     * Get all registered model-factory mappings.
     *
     * @return array<string, string>
     */
    public function getFactoryMap(): array
    {
        return self::$factoryMap;
    }

    /**
     * Discover and register all factories from hexagonal modules.
     */
    private function discoverAndRegisterFactories(): void
    {
        $factories = $this->discoverFactories();

        foreach ($factories as $factoryClass => $modelClass) {
            self::$factoryMap[$modelClass] = $factoryClass;
            $this->app->bind($factoryClass);
        }
    }

    /**
     * Register a factory resolver for all hexagonal module models.
     */
    private function registerFactoryResolver(): void
    {
        Factory::guessFactoryNamesUsing(
            /** @param class-string $modelName @phpstan-ignore argument.type */
            function (string $modelName): string {
                // Check if we have a registered factory for this model
                if (isset(self::$factoryMap[$modelName])) {
                    return self::$factoryMap[$modelName];
                }

                // Try to resolve factory based on hexagonal module structure
                if (preg_match('/^Modules\\\\([^\\\\]+)\\\\/', $modelName, $matches)) {
                    $moduleName = $matches[1];
                    $modelBaseName = class_basename($modelName);

                    // Check standard factory location
                    $factoryClass = "Modules\\{$moduleName}\\Infrastructure\\Laravel\\Factory\\{$modelBaseName}Factory";
                    if (class_exists($factoryClass)) {
                        return $factoryClass;
                    }

                    // Check alternative factory location (Database/Factories)
                    $altFactoryClass = "Modules\\{$moduleName}\\Infrastructure\\Laravel\\Database\\Factories\\{$modelBaseName}Factory";
                    if (class_exists($altFactoryClass)) {
                        return $altFactoryClass;
                    }
                }

                // Fall back to Laravel's default factory resolution
                $modelBaseName = class_basename($modelName);

                return "Database\\Factories\\{$modelBaseName}Factory";
            }
        );
    }

    /**
     * Discover all factory files from hexagonal modules.
     *
     * @return array<string, string> Array of [factoryClass => modelClass]
     */
    private function discoverFactories(): array
    {
        $factories = [];

        if (! File::exists(base_path('modules'))) {
            return $factories;
        }

        foreach (File::directories(base_path('modules')) as $module) {
            $factoryPath = "{$module}/Infrastructure/Laravel/Factory";

            if (! File::exists($factoryPath)) {
                continue;
            }

            foreach (File::files($factoryPath) as $file) {
                $filename = $file->getFilename();

                if (! str_ends_with($filename, 'Factory.php')) {
                    continue;
                }

                $moduleName = basename((string) $module);
                $factoryClassName = "Modules\\{$moduleName}\\Infrastructure\\Laravel\\Factory\\"
                    . $file->getFilenameWithoutExtension();

                if (class_exists($factoryClassName) && is_subclass_of($factoryClassName, Factory::class)) {
                    $modelClass = $this->getFactoryModelClass($factoryClassName);
                    if ($modelClass) {
                        $factories[$factoryClassName] = $modelClass;
                    }
                }
            }
        }

        return $factories;
    }

    /**
     * Get the model class that a factory creates.
     */
    private function getFactoryModelClass(string $factoryClassName): ?string
    {
        try {
            if (! class_exists($factoryClassName)) {
                return null;
            }
            $reflection = new ReflectionClass($factoryClassName);
            $modelProperty = $reflection->getProperty('model');

            // Get default value from property or create instance to check
            $defaultValue = $modelProperty->getDefaultValue();
            if ($defaultValue && is_string($defaultValue)) {
                return $defaultValue;
            }

            // If no default value, instantiate factory and check model property
            $factory = new $factoryClassName;
            if (property_exists($factory, 'model') && is_string($factory->model)) {
                return $factory->model;
            }
        } catch (Throwable) {
            // Silently continue if we can't determine the model class
        }

        return null;
    }
}
