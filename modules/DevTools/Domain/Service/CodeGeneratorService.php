<?php

declare(strict_types=1);

namespace Modules\DevTools\Domain\Service;

use Exception;
use InvalidArgumentException;
use RuntimeException;

final readonly class CodeGeneratorService
{
    private string $stubsPath;

    private string $modulesPath;

    public function __construct(string $stubsPath, string $modulesPath)
    {
        $this->stubsPath = rtrim($stubsPath, '/');
        $this->modulesPath = rtrim($modulesPath, '/');
    }

    /**
     * Process a stub template by replacing variables with values.
     *
     * @param  array<string, mixed>  $variables
     */
    public function processStub(string $stubName, array $variables): string
    {
        $stubPath = $this->getStubPath($stubName);

        if (! file_exists($stubPath)) {
            throw new InvalidArgumentException("Stub file not found: {$stubPath}");
        }

        $content = file_get_contents($stubPath);

        if ($content === false) {
            throw new RuntimeException("Failed to read stub file: {$stubPath}");
        }

        return $this->replaceVariables($content, $variables);
    }

    /**
     * Replace variables in content using double curly braces syntax.
     *
     * @param  array<string, mixed>  $variables
     */
    public function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            // Support both {{variable}} and {{ variable }} formats
            $placeholderWithSpaces = '{{ ' . $key . ' }}';
            $placeholderWithoutSpaces = '{{' . $key . '}}';

            $content = str_replace($placeholderWithSpaces, (string) $value, $content);
            $content = str_replace($placeholderWithoutSpaces, (string) $value, $content);
        }

        return $content;
    }

    /**
     * Create a file from a stub template.
     *
     * @param  array<string, mixed>  $variables
     */
    public function createFileFromStub(
        string $stubName,
        string $destinationPath,
        array $variables,
        bool $overwrite = false,
    ): void {
        if (! $overwrite && file_exists($destinationPath)) {
            throw new RuntimeException("File already exists: {$destinationPath}");
        }

        $content = $this->processStub($stubName, $variables);
        $directory = dirname($destinationPath);

        if (! is_dir($directory) && ! mkdir($directory, 0o755, true)) {
            throw new RuntimeException("Failed to create directory: {$directory}");
        }

        if (file_put_contents($destinationPath, $content) === false) {
            throw new RuntimeException("Failed to write file: {$destinationPath}");
        }
    }

    /**
     * Get available domains in the modules directory.
     */
    /** @return array<array-key, mixed> */
    public function getAvailableDomains(): array
    {
        if (! is_dir($this->modulesPath)) {
            return [];
        }

        $domains = [];
        $directories = scandir($this->modulesPath);

        if ($directories === false) {
            return [];
        }

        foreach ($directories as $directory) {
            if ($directory === '.') {
                continue;
            }

            if ($directory === '..') {
                continue;
            }
            $fullPath = $this->modulesPath . '/' . $directory;

            if (is_dir($fullPath) && $this->isValidDomainModule($fullPath)) {
                $domains[] = $directory;
            }
        }

        sort($domains);

        return $domains;
    }

    /**
     * Get the full path to a stub file.
     */
    public function getStubPath(string $stubName): string
    {
        $stubFile = str_ends_with($stubName, '.stub') ? $stubName : $stubName . '.stub';

        return $this->stubsPath . '/' . $stubFile;
    }

    /**
     * Get available stub files.
     */
    /** @return array<array-key, mixed> */
    public function getAvailableStubs(): array
    {
        if (! is_dir($this->stubsPath)) {
            return [];
        }

        $stubs = [];
        $files = glob($this->stubsPath . '/*.stub');

        if ($files === false) {
            return [];
        }

        foreach ($files as $file) {
            $stubs[] = basename($file, '.stub');
        }

        sort($stubs);

        return $stubs;
    }

    /**
     * Build the destination path for a generated file.
     */
    public function buildDestinationPath(
        string $domain,
        string $layer,
        string $type,
        string $name,
    ): string {
        $basePath = $this->modulesPath . '/' . $domain . '/' . $layer;

        switch ($type) {
            case 'Command':
            case 'Query':
                return $basePath . '/Application/' . $type . '/' . $name . '.php';

            case 'CommandHandler':
                return $basePath . '/Application/Command/' . str_replace('CommandHandler', '', $name) . 'Handler.php';

            case 'QueryHandler':
                return $basePath . '/Application/Query/' . str_replace('QueryHandler', '', $name) . 'Handler.php';

            case 'Model':
                return $basePath . '/Domain/Model/' . $name . '.php';

            case 'ValueObject':
                return $basePath . '/Domain/ValueObject/' . $name . '.php';

            case 'Repository':
                return $basePath . '/Domain/Repository/' . $name . 'RepositoryInterface.php';

            case 'RepositoryEloquent':
                return $basePath . '/Infrastructure/Laravel/Repository/' . str_replace('RepositoryEloquent', '', $name) . 'EloquentRepository.php';

            case 'Controller':
                return $basePath . '/Infrastructure/Laravel/Controllers/' . $name . '.php';

            case 'FormRequest':
                return $basePath . '/Infrastructure/Laravel/FormRequest/' . $name . '.php';

            case 'Migration':
                return $basePath . '/Infrastructure/Laravel/Migration/' . date('Y_m_d_His') . '_' . strtolower($name) . '.php';

            case 'Factory':
                return $basePath . '/Infrastructure/Laravel/Factory/' . $name . '.php';

            case 'Seeder':
                return $basePath . '/Infrastructure/Laravel/Seeder/' . $name . '.php';

            case 'Event':
                return $basePath . '/Application/Event/' . $name . '.php';

            case 'Processor':
                return $basePath . '/Infrastructure/ApiPlatform/Handler/Processor/' . $name . '.php';

            default:
                throw new InvalidArgumentException("Unknown type: {$type}");
        }
    }

    /**
     * Generate common variables for stub processing.
     *
     * @param  array<string, mixed>  $additionalVariables
     * @return array<string, mixed>
     */
    public function generateCommonVariables(
        string $domain,
        string $name,
        string $type,
        array $additionalVariables = [],
    ): array {
        $variables = [
            'DOMAIN' => $domain,
            'NAME' => $name,
            'TYPE' => $type,
            'NAMESPACE' => $this->buildNamespace($domain, $type),
            'CLASS_NAME' => $this->buildClassName($name, $type),
            'LOWER_DOMAIN' => strtolower($domain),
            'SNAKE_CASE_NAME' => $this->toSnakeCase($name),
            'KEBAB_CASE_NAME' => $this->toKebabCase($name),
            'CAMEL_CASE_NAME' => lcfirst($name),
            'TIMESTAMP' => date('Y_m_d_His'),
            'DATE' => date('Y-m-d'),
            'DATETIME' => date('Y-m-d H:i:s'),
            'YEAR' => date('Y'),
        ];

        return array_merge($variables, $additionalVariables);
    }

    /**
     * Validate if a directory represents a valid domain module.
     */
    private function isValidDomainModule(string $path): bool
    {
        // Check if it has the basic hexagonal architecture structure
        $requiredDirs = ['Domain', 'Application', 'Infrastructure'];

        foreach ($requiredDirs as $dir) {
            if (! is_dir($path . '/' . $dir)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Build namespace for a given domain and type.
     */
    private function buildNamespace(string $domain, string $type): string
    {
        $baseNamespace = 'Modules\\' . $domain;

        switch ($type) {
            case 'Command':
            case 'Query':
                return $baseNamespace . '\\Application\\' . $type;

            case 'CommandHandler':
                return $baseNamespace . '\\Application\\Command';

            case 'QueryHandler':
                return $baseNamespace . '\\Application\\Query';

            case 'Model':
            case 'ValueObject':
                return $baseNamespace . '\\Domain\\' . $type;

            case 'Repository':
                return $baseNamespace . '\\Domain\\Repository';

            case 'RepositoryEloquent':
                return $baseNamespace . '\\Infrastructure\\Laravel\\Repository';

            case 'Controller':
                return $baseNamespace . '\\Infrastructure\\Laravel\\Controllers';

            case 'FormRequest':
                return $baseNamespace . '\\Infrastructure\\Laravel\\FormRequest';

            case 'Migration':
                return $baseNamespace . '\\Infrastructure\\Laravel\\Migration';

            case 'Factory':
                return $baseNamespace . '\\Infrastructure\\Laravel\\Factory';

            case 'Seeder':
                return $baseNamespace . '\\Infrastructure\\Laravel\\Seeder';

            case 'Event':
                return $baseNamespace . '\\Application\\Event';

            case 'Processor':
                return $baseNamespace . '\\Infrastructure\\ApiPlatform\\Handler\\Processor';

            default:
                return $baseNamespace . '\\Domain';
        }
    }

    /**
     * Build class name for a given name and type.
     */
    private function buildClassName(string $name, string $type): string
    {
        return match ($type) {
            'CommandHandler' => $name . 'CommandHandler',
            'QueryHandler' => $name . 'QueryHandler',
            'Repository' => $name . 'RepositoryInterface',
            'RepositoryEloquent' => $name . 'EloquentRepository',
            default => $name,
        };
    }

    /**
     * Convert string to snake_case.
     */
    private function toSnakeCase(string $string): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    /**
     * Convert string to kebab-case.
     */
    private function toKebabCase(string $string): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $string));
    }

    /**
     * Generate code files based on type, module, and name.
     *
     * @param  array<string, mixed>  $properties
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function generate(
        string $type,
        string $module,
        string $name,
        array $properties = [],
        array $options = []
    ): array {
        $stubName = strtolower($type);
        $variables = $this->generateCommonVariables($module, $name, $type, [
            'PROPERTIES' => $properties,
            'OPTIONS' => $options,
        ]);

        $destinationPath = $this->buildDestinationPath($module, 'Domain', $type, $name);

        try {
            $this->createFileFromStub(
                $stubName,
                $destinationPath,
                $variables,
                $options['overwrite'] ?? false
            );

            return [
                'success' => true,
                'files' => [$destinationPath],
                'message' => "Generated {$type} successfully",
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'files' => [],
                'message' => "Failed to generate {$type}: " . $e->getMessage(),
            ];
        }
    }
}
