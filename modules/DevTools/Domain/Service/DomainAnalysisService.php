<?php

declare(strict_types=1);

namespace Modules\DevTools\Domain\Service;

final readonly class DomainAnalysisService
{
    private string $modulesPath;

    public function __construct(string $modulesPath)
    {
        $this->modulesPath = rtrim($modulesPath, '/');
    }

    /**
     * Analyze all domains and return completeness status.
     *
     * @return array<string, mixed>
     */
    public function analyzeAllDomains(): array
    {
        $domains = $this->getAvailableDomains();
        $analysis = [];

        foreach ($domains as $domain) {
            $analysis[$domain] = $this->analyzeDomain($domain);
        }

        return $analysis;
    }

    /**
     * Analyze a specific domain for missing components.
     *
     * @return array{
     *     exists: bool,
     *     completeness: int,
     *     components: array<string, bool>,
     *     models: array<int, string>,
     *     missing: array<int, string>,
     *     recommendations: array<int, string>
     * }
     */
    public function analyzeDomain(string $domain): array
    {
        $domainPath = "{$this->modulesPath}/{$domain}";

        if (! is_dir($domainPath)) {
            return [
                'exists' => false,
                'completeness' => 0,
                'components' => [],
                'missing' => [],
                'models' => [],
                'recommendations' => [],
            ];
        }

        $components = $this->checkDomainComponents($domain);
        $models = $this->getDomainModels($domain);
        $completeness = $this->calculateCompleteness($components);
        $missing = $this->getMissingComponents($components);
        $recommendations = $this->generateRecommendations($domain, $components, $models);

        return [
            'exists' => true,
            'completeness' => $completeness,
            'components' => $components,
            'models' => $models,
            'missing' => $missing,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Get domains that are partially complete (have some but not all components).
     *
     * @return array<string, mixed>
     */
    public function getPartialDomains(): array
    {
        $allDomains = $this->analyzeAllDomains();
        $partialDomains = [];

        foreach ($allDomains as $domain => $analysis) {
            if (! $analysis['exists']) {
                continue;
            }

            $completeness = $analysis['completeness'];

            // Consider partial if between 10% and 90% complete
            if ($completeness > 10 && $completeness < 90) {
                $partialDomains[$domain] = $analysis;
            }
        }

        return $partialDomains;
    }

    /**
     * Get domains missing specific component type.
     *
     * @return array<string, mixed>
     */
    public function getDomainsWithoutComponent(string $componentType): array
    {
        $allDomains = $this->analyzeAllDomains();
        $missingDomains = [];

        foreach ($allDomains as $domain => $analysis) {
            if (! $analysis['exists']) {
                continue;
            }

            if (in_array($domain, ['DevTools', 'Admin', 'Shared'], true)) {
                continue;
            }

            if (! $analysis['components'][$componentType]) {
                $missingDomains[$domain] = $analysis;
            }
        }

        return $missingDomains;
    }

    /**
     * Check which hexagonal architecture components exist for a domain.
     *
     * @return array<string, bool>
     */
    private function checkDomainComponents(string $domain): array
    {
        $domainPath = "{$this->modulesPath}/{$domain}";

        return [
            'domain_model' => is_dir("{$domainPath}/Domain/Model"),
            'domain_repository' => is_dir("{$domainPath}/Domain/Repository"),
            'app_commands' => is_dir("{$domainPath}/Application/Command"),
            'app_events' => is_dir("{$domainPath}/Application/Event"),
            'app_queries' => is_dir("{$domainPath}/Application/Query"),
            'api_resource' => is_dir("{$domainPath}/Infrastructure/ApiPlatform/Resource"),
            'api_processors' => is_dir("{$domainPath}/Infrastructure/ApiPlatform/Handler/Processor"),
            'api_providers' => is_dir("{$domainPath}/Infrastructure/ApiPlatform/Handler/Provider"),
            'laravel_repository' => is_dir("{$domainPath}/Infrastructure/Laravel/Repository"),
            'laravel_factory' => is_dir("{$domainPath}/Infrastructure/Laravel/Factory"),
            'laravel_migration' => is_dir("{$domainPath}/Infrastructure/Laravel/Migration"),
            'laravel_seeder' => is_dir("{$domainPath}/Infrastructure/Laravel/Command"),
            'laravel_form_request' => is_dir("{$domainPath}/Infrastructure/Laravel/FormRequest"),
        ];
    }

    /**
     * Get all models in a domain.
     *
     * @return array<int, string>
     */
    private function getDomainModels(string $domain): array
    {
        $modelsPath = "{$this->modulesPath}/{$domain}/Domain/Model";

        if (! is_dir($modelsPath)) {
            return [];
        }

        $models = [];
        $files = glob("{$modelsPath}/*.php");

        if ($files === false) {
            return [];
        }

        foreach ($files as $file) {
            $models[] = basename($file, '.php');
        }

        return $models;
    }

    /**
     * Calculate completeness percentage based on components.
     *
     * @param  array<string, bool>  $components
     */
    private function calculateCompleteness(array $components): int
    {
        $totalComponents = count($components);
        $existingComponents = count(array_filter($components, fn (bool $exists): bool => $exists));

        return $totalComponents > 0 ? (int) round(($existingComponents / $totalComponents) * 100) : 0;
    }

    /**
     * Get list of missing components.
     *
     * @param  array<string, bool>  $components
     * @return array<int, string>
     */
    private function getMissingComponents(array $components): array
    {
        $missing = [];

        foreach ($components as $component => $exists) {
            if (! $exists) {
                $missing[] = $this->humanizeComponentName($component);
            }
        }

        return $missing;
    }

    /**
     * Generate smart recommendations for a domain.
     *
     * @param  array<string, bool>  $components
     * @param  array<int, string>  $models
     * @return array<int, string>
     */
    private function generateRecommendations(string $domain, array $components, array $models): array
    {
        $recommendations = [];

        // Skip utility modules
        if (in_array($domain, ['DevTools', 'Admin', 'Shared'], true)) {
            return ['This is a utility module - recommendations not applicable'];
        }

        // If has models but missing API Platform components
        if ($models !== [] && ! $components['api_resource']) {
            $recommendations[] = ' Add API Platform Resource for ' . implode(', ', $models);
            $recommendations[] = ' Add API Processors (CRUD operations)';
            $recommendations[] = '📤 Add API Providers (data retrieval)';
        }

        // If has models but missing CQRS components
        if ($models !== [] && ! $components['app_commands']) {
            $recommendations[] = ' Add CQRS Commands & Handlers';
        }

        if ($models !== [] && ! $components['app_events']) {
            $recommendations[] = ' Add Domain Events & Handlers';
        }

        if ($models !== [] && ! $components['app_queries']) {
            $recommendations[] = ' Add Query Pattern Classes';
        }

        // If has models but missing infrastructure
        if ($models !== [] && ! $components['laravel_repository']) {
            $recommendations[] = '🗄️ Add Repository Implementation';
        }

        if ($models !== [] && ! $components['laravel_factory']) {
            $recommendations[] = '🏭 Add Model Factory for Testing';
        }

        if ($models !== [] && ! $components['laravel_migration']) {
            $recommendations[] = ' Add Database Migration';
        }

        // Domain without models needs basic structure
        if ($models === [] && ! $components['domain_model']) {
            $recommendations[] = ' Create Domain Model(s)';
            $recommendations[] = ' Add Repository Interface';
            $recommendations[] = ' Generate Complete Domain Structure';
        }

        // High-level recommendations
        if ($this->calculateCompleteness($components) < 50) {
            $recommendations[] = " Consider using 'hex:create-domain' to generate complete structure";
        }

        return $recommendations;
    }

    /**
     * Get available domains (excluding special directories).
     *
     * @return array<int, string>
     */
    private function getAvailableDomains(): array
    {
        if (! is_dir($this->modulesPath)) {
            return [];
        }

        $directories = glob("{$this->modulesPath}/*", GLOB_ONLYDIR);

        if ($directories === false) {
            return [];
        }
        $domains = [];

        foreach ($directories as $dir) {
            $domainName = basename($dir);
            // Include all domains for analysis
            $domains[] = $domainName;
        }

        sort($domains);

        return $domains;
    }

    /**
     * Convert component internal name to human readable.
     */
    private function humanizeComponentName(string $component): string
    {
        $map = [
            'domain_model' => 'Domain Model',
            'domain_repository' => 'Repository Interface',
            'app_commands' => 'Application Commands',
            'app_events' => 'Domain Events',
            'app_queries' => 'Query Patterns',
            'api_resource' => 'API Platform Resource',
            'api_processors' => 'API Processors',
            'api_providers' => 'API Providers',
            'laravel_repository' => 'Repository Implementation',
            'laravel_factory' => 'Model Factory',
            'laravel_migration' => 'Database Migration',
            'laravel_seeder' => 'Database Seeder',
            'laravel_form_request' => 'Form Request Validation',
        ];

        return $map[$component] ?? ucwords(str_replace('_', ' ', $component));
    }

    /**
     * Analyze a specific module and return detailed information.
     *
     * @return array<string, mixed>
     */
    public function analyzeModule(string $module): array
    {
        $analysis = $this->analyzeDomain($module);
        $models = $this->getDomainModels($module);

        return [
            'module' => $module,
            'exists' => $analysis['exists'],
            'completeness' => $analysis['completeness'],
            'models' => $models,
            'components' => $analysis['components'],
            'recommendations' => $analysis['recommendations'],
            'missing' => $analysis['missing'],
            'entities' => $models,
            'valueObjects' => $this->getDomainValueObjects($module),
            'aggregates' => [],
            'services' => $this->getDomainServices($module),
            'repositories' => $this->getDomainRepositories($module),
            'events' => $this->getDomainEvents($module),
            'metrics' => [
                'total_files' => $this->countModuleFiles($module),
                'completeness' => $analysis['completeness'],
            ],
            'violations' => $this->detectViolations(),
        ];
    }

    /**
     * Get domain value objects.
     *
     * @return array<int, string>
     */
    private function getDomainValueObjects(string $domain): array
    {
        $path = "{$this->modulesPath}/{$domain}/Domain/ValueObject";

        return $this->getPhpFilesInDirectory($path);
    }

    /**
     * Get domain services.
     *
     * @return array<int, string>
     */
    private function getDomainServices(string $domain): array
    {
        $path = "{$this->modulesPath}/{$domain}/Domain/Service";

        return $this->getPhpFilesInDirectory($path);
    }

    /**
     * Get domain repositories.
     *
     * @return array<int, string>
     */
    private function getDomainRepositories(string $domain): array
    {
        $path = "{$this->modulesPath}/{$domain}/Domain/Repository";

        return $this->getPhpFilesInDirectory($path);
    }

    /**
     * Get domain events.
     *
     * @return array<int, string>
     */
    private function getDomainEvents(string $domain): array
    {
        $path = "{$this->modulesPath}/{$domain}/Application/Event";

        return $this->getPhpFilesInDirectory($path);
    }

    /**
     * Get PHP files in a directory.
     *
     * @return array<int, string>
     */
    private function getPhpFilesInDirectory(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $files = glob("{$path}/*.php");
        if ($files === false) {
            return [];
        }

        return array_map(fn ($file) => basename($file, '.php'), $files);
    }

    /**
     * Count total PHP files in a module.
     */
    private function countModuleFiles(string $domain): int
    {
        $modulePath = "{$this->modulesPath}/{$domain}";
        if (! is_dir($modulePath)) {
            return 0;
        }

        $files = glob("{$modulePath}/**/*.php", GLOB_BRACE);

        return $files !== false ? count($files) : 0;
    }

    /**
     * Detect architectural violations.
     *
     * @return array<int, string>
     */
    private function detectViolations(): array
    {
        // Simple violation detection - could be expanded
        return [];
    }
}
