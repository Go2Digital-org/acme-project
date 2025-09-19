<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command\Traits;

use Exception;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\DevTools\Domain\Service\CodeGeneratorService;
use RuntimeException;
use Symfony\Component\Console\Input\InputDefinition;

/**
 * Trait HexGeneratorTrait.
 *
 * This trait provides hexagonal architecture code generation utilities.
 * It must be used only in classes extending Illuminate\Console\Command.
 *
 * @method InputDefinition getDefinition()
 * @method bool hasArgument(string $name) [Not available - use getDefinition()->hasArgument()]
 * @method bool hasOption(string $name) [Not available - use getDefinition()->hasOption()]
 * @method mixed argument(string $key = null)
 * @method string choice(string $question, array<string> $choices, mixed $default = null, mixed $attempts = null, bool $multiple = false)
 * @method string ask(string $question, string|null $default = null)
 * @method bool confirm(string $question, bool $default = false)
 * @method mixed option(string $key = null)
 * @method void info(string|array<string> $string, int|string|null $verbosity = null)
 * @method void error(string|array<string> $string, int|string|null $verbosity = null)
 * @method void warn(string|array<string> $string, int|string|null $verbosity = null)
 * @method void line(string|array<string> $string, string|null $style = null, int|string|null $verbosity = null)
 * @method int call(string $command, array<string, mixed> $arguments = [])
 * @method Application getApplication()
 */
trait HexGeneratorTrait
{
    protected CodeGeneratorService $codeGenerator;

    /**
     * Initialize the code generator service.
     */
    protected function initializeCodeGenerator(): void
    {
        $stubsPath = base_path('modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal');
        $modulesPath = base_path('modules');

        $this->codeGenerator = new CodeGeneratorService($stubsPath, $modulesPath);
    }

    /**
     * Get or prompt for domain name.
     */
    protected function getDomainName(): string
    {
        // Only try to get domain argument if the command actually has it defined
        if ($this->getDefinition()->hasArgument('domain')) {
            $domainValue = $this->argument('domain');

            if ($domainValue) {
                return $domainValue;
            }
        }

        $availableDomains = $this->codeGenerator->getAvailableDomains();

        if (empty($availableDomains)) {
            $this->error('No domains found. Please create a domain first using: php artisan add:hex:domain');

            return '';
        }

        return $this->choice(
            'Select a domain',
            $availableDomains,
            0,
        );
    }

    /**
     * Get or prompt for class name.
     */
    protected function getClassName(): string
    {
        // Only try to get name argument if the command actually has it defined
        if ($this->getDefinition()->hasArgument('name')) {
            $nameValue = $this->argument('name');

            if ($nameValue) {
                return $nameValue;
            }
        }

        return $this->ask('Enter the class name');
    }

    /**
     * Validate domain exists.
     */
    protected function validateDomain(string $domain): bool
    {
        $availableDomains = $this->codeGenerator->getAvailableDomains();

        return in_array($domain, $availableDomains, true);
    }

    /**
     * Validate class name format.
     */
    protected function validateClassName(string $name): bool
    {
        return preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name) === 1;
    }

    /**
     * Generate file with confirmation.
     */
    /**
     * @param  array<string, mixed>  $additionalVariables
     */
    protected function generateFile(
        string $domain,
        string $name,
        string $type,
        string $stubName,
        array $additionalVariables = [],
        bool $forceOverwrite = false,
    ): bool {
        try {
            $variables = $this->codeGenerator->generateCommonVariables(
                $domain,
                $name,
                $type,
                $additionalVariables,
            );

            $destinationPath = $this->codeGenerator->buildDestinationPath(
                $domain,
                $this->getLayer($type),
                $type,
                $name,
            );

            // Check if file exists and get confirmation
            if (! $forceOverwrite && file_exists($destinationPath) && ! $this->confirm("File already exists at {$destinationPath}. Overwrite?")) {
                $this->info('Generation cancelled.');

                return false;
            }

            $this->codeGenerator->createFileFromStub(
                $stubName,
                $destinationPath,
                $variables,
                true,
            );

            $this->info("Generated: {$destinationPath}");

            return true;
        } catch (RuntimeException|InvalidArgumentException $e) {
            $this->error("Generation failed: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Create domain directory structure.
     */
    protected function createDomainStructure(string $domain): void
    {
        $basePath = base_path("modules/{$domain}");

        $directories = [
            'Domain/Model',
            'Domain/Repository',
            'Domain/ValueObject',
            'Domain/Exception',
            'Domain/Service',
            'Application/Command',
            'Application/Query',
            'Application/Event',
            'Application/Service',
            'Infrastructure/Laravel/Controllers',
            'Infrastructure/Laravel/FormRequest',
            'Infrastructure/Laravel/Repository',
            'Infrastructure/Laravel/Factory',
            'Infrastructure/Laravel/Migration',
            'Infrastructure/Laravel/Seeder',
            'Infrastructure/Laravel/Provider',
            'Infrastructure/ApiPlatform/Resource',
            'Infrastructure/ApiPlatform/Handler/Processor',
            'Infrastructure/ApiPlatform/Handler/Provider',
            'Infrastructure/Filament/Resources',
        ];

        foreach ($directories as $directory) {
            $fullPath = $basePath . '/' . $directory;

            if (! is_dir($fullPath)) {
                mkdir($fullPath, 0o755, true);
                $this->line("Created directory: {$fullPath}");
            }
        }
    }

    /**
     * Display available domains.
     */
    protected function displayAvailableDomains(): void
    {
        $domains = $this->codeGenerator->getAvailableDomains();

        if (empty($domains)) {
            $this->warn('No domains found.');

            return;
        }

        $this->info('Available domains:');

        foreach ($domains as $domain) {
            $this->line("  - {$domain}");
        }
    }

    /**
     * Display available stubs.
     */
    protected function displayAvailableStubs(): void
    {
        $stubs = $this->codeGenerator->getAvailableStubs();

        if (empty($stubs)) {
            $this->warn('No stubs found.');

            return;
        }

        $this->info('Available stubs:');

        foreach ($stubs as $stub) {
            $this->line("  - {$stub}");
        }
    }

    /**
     * Get layer for a given type.
     */
    protected function getLayer(string $type): string
    {
        $domainTypes = ['Model', 'ValueObject', 'Repository', 'Exception', 'Service'];
        $applicationTypes = ['Command', 'CommandHandler', 'Query', 'QueryHandler', 'Event'];

        if (in_array($type, $domainTypes, true)) {
            return 'Domain';
        }

        if (in_array($type, $applicationTypes, true)) {
            return 'Application';
        }

        return 'Infrastructure';
    }

    /**
     * Format class name to proper case.
     */
    protected function formatClassName(string $name): string
    {
        return Str::studly($name);
    }

    /**
     * Format domain name to proper case.
     */
    protected function formatDomainName(string $domain): string
    {
        return Str::studly($domain);
    }

    /**
     * Get command signature with common options.
     */
    protected function getSignatureWithOptions(string $baseSignature): string
    {
        return $baseSignature . ' {--force : Force overwrite existing files}';
    }

    /**
     * Should force overwrite files.
     */
    protected function shouldForceOverwrite(): bool
    {
        // Only try to get force option if the command actually has it defined
        if ($this->getDefinition()->hasOption('force')) {
            return $this->option('force') === true;
        }

        return false;
    }

    /**
     * Display generation summary.
     */
    protected function displaySummary(string $domain, string $name, string $type): void
    {
        $this->info("Generated {$type} for {$domain} domain:");
        $this->line("  Domain: {$domain}");
        $this->line("  Name: {$name}");
        $this->line("  Type: {$type}");
        $this->line("  Layer: {$this->getLayer($type)}");
    }

    /**
     * Check if stub exists.
     */
    protected function stubExists(string $stubName): bool
    {
        $stubPath = $this->codeGenerator->getStubPath($stubName);

        return file_exists($stubPath);
    }

    /**
     * Create related files prompt.
     */
    protected function promptForRelatedFiles(string $domain, string $name, string $type): void
    {
        $relatedFiles = $this->getRelatedFiles($type);

        if (empty($relatedFiles)) {
            return;
        }

        $this->info('');
        $this->info('You might also want to create:');

        foreach ($relatedFiles as $relatedType => $description) {
            $this->line("  - {$relatedType}: {$description}");
        }

        if ($this->confirm('Would you like to generate related files?')) {
            foreach ($relatedFiles as $relatedType => $description) {
                if ($this->confirm("Create {$relatedType}?")) {
                    $arguments = [];
                    $commandName = "add:hex:{$this->getCommandForType($relatedType)}";

                    // Only add arguments if the command supports them
                    if ($this->commandHasArgument($commandName, 'domain')) {
                        $arguments['domain'] = $domain;
                    }

                    if ($this->commandHasArgument($commandName, 'name')) {
                        $arguments['name'] = $name;
                    }

                    if ($this->commandHasOption($commandName, 'force') && $this->shouldForceOverwrite()) {
                        $arguments['--force'] = true;
                    }

                    $this->call($commandName, $arguments);
                }
            }
        }
    }

    /**
     * Get related files for a type.
     */
    /**
     * @return array<string, string>
     */
    protected function getRelatedFiles(string $type): array
    {
        $relations = [
            'Command' => [
                'CommandHandler' => 'Handler for processing the command',
            ],
            'Query' => [
                'QueryHandler' => 'Handler for processing the query',
            ],
            'Model' => [
                'Repository' => 'Repository interface for the model',
                'RepositoryEloquent' => 'Eloquent implementation of the repository',
                'Factory' => 'Factory for creating model instances',
            ],
            'Repository' => [
                'RepositoryEloquent' => 'Eloquent implementation of the repository',
            ],
        ];

        return $relations[$type] ?? [];
    }

    /**
     * Get artisan command for a type.
     */
    protected function getCommandForType(string $type): string
    {
        $commands = [
            'Command' => 'command',
            'CommandHandler' => 'command-handler',
            'Query' => 'query',
            'QueryHandler' => 'query-handler',
            'Model' => 'model',
            'Repository' => 'repository',
            'RepositoryEloquent' => 'repository-eloquent',
            'Factory' => 'factory',
            'ValueObject' => 'value-object',
            'Event' => 'event',
            'Processor' => 'processor',
            'FormRequest' => 'form-request',
            'Migration' => 'migration',
            'Seeder' => 'seeder',
        ];

        return $commands[$type] ?? strtolower($type);
    }

    /**
     * Check if a command has a specific argument.
     */
    protected function commandHasArgument(string $commandName, string $argumentName): bool
    {
        try {
            $kernel = $this->getApplication()->make(Kernel::class);
            $command = $kernel->all()[$commandName] ?? null;

            if ($command === null) {
                return false;
            }

            $definition = $command->getDefinition();

            return $definition->hasArgument($argumentName);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Check if a command has a specific option.
     */
    protected function commandHasOption(string $commandName, string $optionName): bool
    {
        try {
            $kernel = $this->getApplication()->make(Kernel::class);
            $command = $kernel->all()[$commandName] ?? null;

            if ($command === null) {
                return false;
            }

            $definition = $command->getDefinition();

            return $definition->hasOption($optionName);
        } catch (Exception) {
            return false;
        }
    }
}
