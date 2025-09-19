<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Modules\DevTools\Domain\Service\CodeGeneratorService;
use Modules\DevTools\Domain\Service\DomainAnalysisService;

/**
 * @phpstan-type DomainAnalysis array{
 *     exists: bool,
 *     completeness: int,
 *     components: array<string, bool>,
 *     models: array<string>,
 *     missing: array<string>,
 *     recommendations: array<string>
 * }
 */
class RetrofitDomainCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'hex:retrofit-domain 
                            {domain : Domain name to retrofit}
                            {--component= : Specific component to add (api_resource, api_processors, etc.)}
                            {--all : Add all missing components}
                            {--dry-run : Show what would be generated without creating files}
                            {--force : Force overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Add missing hexagonal architecture components to existing domain';

    private readonly DomainAnalysisService $analysisService;

    private readonly CodeGeneratorService $generatorService;

    public function __construct()
    {
        parent::__construct();
        $this->analysisService = new DomainAnalysisService(base_path('modules'));
        $this->generatorService = new CodeGeneratorService(
            base_path('modules/DevTools/Infrastructure/Laravel/Stubs'),
            base_path('modules'),
        );
    }

    public function handle(): int
    {
        $domain = $this->argument('domain');
        $specificComponent = $this->option('component');
        $addAll = $this->option('all');
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Analyze current domain state
        $analysis = $this->analysisService->analyzeDomain($domain);

        if (! $analysis['exists']) {
            $this->error(" Domain '{$domain}' does not exist");

            return self::FAILURE;
        }

        if (empty($analysis['missing'])) {
            $this->info(" Domain '{$domain}' is already complete!");

            return self::SUCCESS;
        }

        $this->displayDomainStatus($domain, $analysis);

        if ($specificComponent) {
            return $this->addSpecificComponent($domain, $specificComponent, $analysis, $isDryRun, $force);
        }

        if ($addAll) {
            return $this->addAllMissingComponents($domain, $analysis, $isDryRun, $force);
        }

        return $this->interactiveMode($domain, $analysis, $isDryRun, $force);
    }

    /**
     * @param  DomainAnalysis  $analysis
     */
    private function displayDomainStatus(string $domain, array $analysis): void
    {
        $this->line("  <info>{$domain}</info> Domain Retrofit");
        $this->line(" Current Completeness: {$analysis['completeness']}%");
        $this->line('  Models Found: ' . (count($analysis['models']) > 0 ? implode(', ', $analysis['models']) : 'None'));

        if (! empty($analysis['missing'])) {
            $this->line(' Missing Components:');

            foreach ($analysis['missing'] as $missing) {
                $this->line("   • {$missing}");
            }
        }

        $this->newLine();
    }

    /**
     * @param  DomainAnalysis  $analysis
     */
    private function addSpecificComponent(string $domain, string $component, array $analysis, bool $isDryRun, bool $force): int
    {
        $componentMap = $this->getComponentMap();

        if (! isset($componentMap[$component])) {
            $this->error(" Unknown component '{$component}'");
            $this->line('Available components: ' . implode(', ', array_keys($componentMap)));

            return self::FAILURE;
        }

        if ($analysis['components'][$component]) {
            if (! $force) {
                $this->warn("  Component '{$component}' already exists. Use --force to overwrite.");

                return self::FAILURE;
            }
            $this->warn(" Overwriting existing '{$component}' component...");
        }

        $method = $componentMap[$component];

        return $this->{$method}($domain, $analysis, $isDryRun);
    }

    /**
     * @param  DomainAnalysis  $analysis
     */
    private function addAllMissingComponents(string $domain, array $analysis, bool $isDryRun, bool $force): int
    {
        $this->info(" Adding all missing components to '{$domain}'...");

        $componentMap = $this->getComponentMap();
        $componentsAdded = 0;

        foreach ($analysis['components'] as $componentKey => $exists) {
            if ($exists && ! $force) {
                continue;
            }

            if (! isset($componentMap[$componentKey])) {
                continue;
            }

            $method = $componentMap[$componentKey];
            $result = $this->{$method}($domain, $analysis, $isDryRun);

            if ($result === self::SUCCESS) {
                $componentsAdded++;
            }
        }

        if ($componentsAdded > 0) {
            $this->info(" Added {$componentsAdded} components to '{$domain}'");
        } else {
            $this->info('ℹ️  No components were added');
        }

        return self::SUCCESS;
    }

    /**
     * @param  DomainAnalysis  $analysis
     */
    private function interactiveMode(string $domain, array $analysis, bool $isDryRun, bool $force): int
    {
        $this->info(' Interactive Mode - Select components to add:');

        $componentMap = $this->getComponentMap();
        $availableComponents = [];

        foreach ($analysis['components'] as $componentKey => $exists) {
            if (! $exists && isset($componentMap[$componentKey])) {
                $availableComponents[] = $componentKey;
            }
        }

        if ($availableComponents === []) {
            $this->info(' All components are already present!');

            return self::SUCCESS;
        }

        $choices = [];

        foreach ($availableComponents as $component) {
            $choices[] = $this->humanizeComponentName($component);
        }
        $choices[] = 'All missing components';
        $choices[] = 'Cancel';

        $selected = $this->choice(
            'Which component would you like to add?',
            $choices,
            count($choices) - 1,
        );

        if ($selected === 'Cancel') {
            return self::SUCCESS;
        }

        if ($selected === 'All missing components') {
            return $this->addAllMissingComponents($domain, $analysis, $isDryRun, $force);
        }

        // Find the component key for the selected human-readable name
        $selectedKey = null;

        foreach ($availableComponents as $key) {
            if ($this->humanizeComponentName($key) === $selected) {
                $selectedKey = $key;
                break;
            }
        }

        if ($selectedKey !== null && is_string($selectedKey)) {
            return $this->addSpecificComponent($domain, $selectedKey, $analysis, $isDryRun, $force);
        }

        return self::FAILURE;
    }

    /**
     * @return array<string, mixed>
     */
    private function getComponentMap(): array
    {
        return [
            'domain_repository' => 'addRepositoryInterface',
            'app_commands' => 'addApplicationCommands',
            'app_events' => 'addDomainEvents',
            'app_queries' => 'addQueryPatterns',
            'api_resource' => 'addApiResource',
            'api_processors' => 'addApiProcessors',
            'api_providers' => 'addApiProviders',
            'laravel_repository' => 'addRepositoryImplementation',
            'laravel_factory' => 'addModelFactory',
            'laravel_migration' => 'addMigration',
            'laravel_seeder' => 'addDatabaseSeeder',
        ];
    }

    /**
     * @param  DomainAnalysis  $analysis
     */
    private function addRepositoryInterface(string $domain, array $analysis, bool $isDryRun): int
    {
        if (empty($analysis['models'])) {
            $this->warn('  Cannot add repository interface - no models found in domain');

            return self::FAILURE;
        }

        $model = $analysis['models'][0]; // Use first model found
        $stubPath = base_path('modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/RepositoryInterface.stub');
        $targetPath = base_path("modules/{$domain}/Domain/Repository/{$domain}RepositoryInterface.php");

        $variables = [
            'domain' => $domain,
            'model' => $model,
            'prefix' => '',
        ];

        return $this->generateFromStub($stubPath, $targetPath, $variables, $isDryRun, 'Repository Interface');
    }

    private function addApplicationCommands(string $domain, bool $isDryRun): int
    {
        $this->info(" Adding CQRS Commands for '{$domain}'...");

        $commands = ['Create', 'Update', 'Delete'];
        $success = 0;

        foreach ($commands as $command) {
            $stubPath = base_path("modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/{$command}Command.stub");
            $targetPath = base_path("modules/{$domain}/Application/Command/{$command}{$domain}Command.php");

            $variables = ['domain' => $domain];

            if ($this->generateFromStub($stubPath, $targetPath, $variables, $isDryRun, "{$command} Command") === self::SUCCESS) {
                $success++;
            }

            // Also generate command handler
            $handlerStubPath = base_path("modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/{$command}CommandHandler.stub");
            $handlerTargetPath = base_path("modules/{$domain}/Application/Command/{$command}{$domain}CommandHandler.php");

            if ($this->generateFromStub($handlerStubPath, $handlerTargetPath, $variables, $isDryRun, "{$command} Command Handler") === self::SUCCESS) {
                $success++;
            }
        }

        return $success > 0 ? self::SUCCESS : self::FAILURE;
    }

    private function addDomainEvents(string $domain, bool $isDryRun): int
    {
        $this->info(" Adding Domain Events for '{$domain}'...");

        $events = [
            "{$domain}Created" => 'DomainEvent.stub',
            "{$domain}Updated" => 'DomainEvent.stub',
            "{$domain}Deleted" => 'DomainEvent.stub',
        ];

        $success = 0;

        foreach ($events as $eventName => $stub) {
            $stubPath = base_path("modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/{$stub}");
            $targetPath = base_path("modules/{$domain}/Application/Event/{$eventName}.php");

            $variables = [
                'domain' => $domain,
                'eventName' => $eventName,
            ];

            if ($this->generateFromStub($stubPath, $targetPath, $variables, $isDryRun, "Event: {$eventName}") === self::SUCCESS) {
                $success++;
            }
        }

        return $success > 0 ? self::SUCCESS : self::FAILURE;
    }

    private function addQueryPatterns(string $domain, bool $isDryRun): int
    {
        $this->info(" Adding Query Patterns for '{$domain}'...");

        $queries = ["Find{$domain}Query", "List{$domain}Query"];
        $success = 0;

        foreach ($queries as $queryName) {
            $stubPath = base_path('modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/Query.stub');
            $targetPath = base_path("modules/{$domain}/Application/Query/{$queryName}.php");

            $variables = [
                'domain' => $domain,
                'queryName' => $queryName,
            ];

            if ($this->generateFromStub($stubPath, $targetPath, $variables, $isDryRun, "Query: {$queryName}") === self::SUCCESS) {
                $success++;
            }
        }

        return $success > 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  DomainAnalysis  $analysis
     */
    private function addApiResource(string $domain, array $analysis, bool $isDryRun): int
    {
        if (empty($analysis['models'])) {
            $this->warn('  Cannot add API resource - no models found in domain');

            return self::FAILURE;
        }

        $stubPath = base_path('modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/Resource.stub');
        $targetPath = base_path("modules/{$domain}/Infrastructure/ApiPlatform/Resource/{$domain}Resource.php");

        $variables = [
            'domain' => $domain,
            'domain_lc' => strtolower($domain),
            'domainCamelCase' => lcfirst($domain),
        ];

        return $this->generateFromStub($stubPath, $targetPath, $variables, $isDryRun, 'API Platform Resource');
    }

    private function addApiProcessors(string $domain, bool $isDryRun): int
    {
        $this->info(" Adding API Processors for '{$domain}'...");

        $processors = ['Create', 'Delete', 'Patch', 'Put'];
        $success = 0;

        foreach ($processors as $processor) {
            $stubPath = base_path("modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/{$processor}Processor.stub");
            $targetPath = base_path("modules/{$domain}/Infrastructure/ApiPlatform/Handler/Processor/{$processor}{$domain}Processor.php");

            $variables = [
                'domain' => $domain,
                'domainCamelCase' => lcfirst($domain),
            ];

            if ($this->generateFromStub($stubPath, $targetPath, $variables, $isDryRun, "{$processor} Processor") === self::SUCCESS) {
                $success++;
            }
        }

        return $success > 0 ? self::SUCCESS : self::FAILURE;
    }

    private function addApiProviders(string $domain, bool $isDryRun): int
    {
        $this->info("📤 Adding API Providers for '{$domain}'...");

        $providers = [
            "{$domain}CollectionProvider" => 'CollectionProvider.stub',
            "{$domain}ItemProvider" => 'ItemProvider.stub',
        ];

        $success = 0;

        foreach ($providers as $providerName => $stub) {
            $stubPath = base_path("modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/{$stub}");
            $targetPath = base_path("modules/{$domain}/Infrastructure/ApiPlatform/Handler/Provider/{$providerName}.php");

            $variables = [
                'domain' => $domain,
                'providerName' => $providerName,
            ];

            if ($this->generateFromStub($stubPath, $targetPath, $variables, $isDryRun, "Provider: {$providerName}") === self::SUCCESS) {
                $success++;
            }
        }

        return $success > 0 ? self::SUCCESS : self::FAILURE;
    }

    private function addRepositoryImplementation(string $domain, bool $isDryRun): int
    {
        $stubPath = base_path('modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/EloquentRepository.stub');
        $targetPath = base_path("modules/{$domain}/Infrastructure/Laravel/Repository/{$domain}EloquentRepository.php");

        $variables = [
            'domain' => $domain,
            'domainCamelCase' => lcfirst($domain),
        ];

        return $this->generateFromStub($stubPath, $targetPath, $variables, $isDryRun, 'Repository Implementation');
    }

    /**
     * @param  DomainAnalysis  $analysis
     */
    private function addModelFactory(string $domain, array $analysis, bool $isDryRun): int
    {
        if (empty($analysis['models'])) {
            $this->warn('  Cannot add factory - no models found in domain');

            return self::FAILURE;
        }

        $model = $analysis['models'][0];
        $stubPath = base_path('modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/Factory.stub');
        $targetPath = base_path("modules/{$domain}/Infrastructure/Laravel/Factory/{$model}Factory.php");

        $variables = [
            'domain' => $domain,
            'model' => $model,
        ];

        return $this->generateFromStub($stubPath, $targetPath, $variables, $isDryRun, 'Model Factory');
    }

    /**
     * @param  DomainAnalysis  $analysis
     */
    private function addMigration(string $domain, array $analysis, bool $isDryRun): int
    {
        if (empty($analysis['models'])) {
            $this->warn('  Cannot add migration - no models found in domain');

            return self::FAILURE;
        }

        $model = $analysis['models'][0];
        $tableName = strtolower($domain) . 's';
        $timestamp = date('Y_m_d_His');

        $stubPath = base_path('modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/Migration.stub');
        $targetPath = base_path("modules/{$domain}/Infrastructure/Laravel/Migration/{$timestamp}_create_{$tableName}_table.php");

        $variables = [
            'domain' => $domain,
            'tableName' => $tableName,
            'model' => $model,
        ];

        return $this->generateFromStub($stubPath, $targetPath, $variables, $isDryRun, 'Database Migration');
    }

    /**
     * @param  DomainAnalysis  $analysis
     */
    private function addDatabaseSeeder(string $domain, array $analysis, bool $isDryRun): int
    {
        if (empty($analysis['models'])) {
            $this->warn('  Cannot add seeder - no models found in domain');

            return self::FAILURE;
        }

        $model = $analysis['models'][0];
        $stubPath = base_path('modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/SeederCommand.stub');
        $targetPath = base_path("modules/{$domain}/Infrastructure/Laravel/Command/{$domain}SeederCommand.php");

        $variables = [
            'domain' => $domain,
            'domain_lc' => strtolower($domain),
            'model' => $model,
            'prefix' => '',
        ];

        return $this->generateFromStub($stubPath, $targetPath, $variables, $isDryRun, 'Database Seeder');
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function generateFromStub(string $stubPath, string $targetPath, array $variables, bool $isDryRun, string $componentName): int
    {
        if (! file_exists($stubPath)) {
            $this->error(" Stub not found: {$stubPath}");

            return self::FAILURE;
        }

        $targetDir = dirname($targetPath);

        if ($isDryRun) {
            $this->line(" [DRY RUN] Would generate {$componentName}: {$targetPath}");

            return self::SUCCESS;
        }

        if (! is_dir($targetDir) && ! mkdir($targetDir, 0o755, true)) {
            $this->error(" Failed to create directory: {$targetDir}");

            return self::FAILURE;
        }

        $stubContent = file_get_contents($stubPath);

        if ($stubContent === false) {
            $this->error(" Failed to read stub file: {$stubPath}");

            return self::FAILURE;
        }

        $generatedContent = $this->generatorService->replaceVariables($stubContent, $variables);

        if (file_put_contents($targetPath, $generatedContent) === false) {
            $this->error(" Failed to create {$componentName}: {$targetPath}");

            return self::FAILURE;
        }

        $this->info(" Generated {$componentName}: {$targetPath}");

        return self::SUCCESS;
    }

    private function humanizeComponentName(string $component): string
    {
        $map = [
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
        ];

        return $map[$component] ?? ucwords(str_replace('_', ' ', $component));
    }
}
