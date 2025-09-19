<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\DevTools\Domain\Service\CodeGeneratorService;
use Modules\DevTools\Infrastructure\Laravel\Command\Traits\HexGeneratorTrait;

class CreateDomainStructureCommand extends Command
{
    use HexGeneratorTrait;

    /**
     * @var string
     */
    protected $signature = 'hex:create-domain {domain} {name?} {--force : Force overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Create a new domain structure with necessary files in the modules directory';

    public function __construct(CodeGeneratorService $codeGenerator)
    {
        parent::__construct();
        $this->codeGenerator = $codeGenerator;
    }

    public function handle(): int
    {
        $domain = ucfirst($this->argument('domain'));

        if (! $this->validateDomain($domain)) {
            $this->error('Invalid domain name. The domain name must start with an uppercase letter.');

            return 1;
        }

        $basePath = base_path("modules/{$domain}");

        if (File::exists($basePath)) {
            $this->error("The domain {$domain} already exists.");

            return 1;
        }

        try {
            $this->createDirectories($basePath);
            $this->createFiles($basePath, $domain);
            $this->info("Domain structure for {$domain} created successfully.");
        } catch (Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());

            // Clean up any partially created directory structure
            // Check if directory was created during the process (it might exist partially)
            if (File::isDirectory($basePath)) {
                File::deleteDirectory($basePath);
                $this->info("The domain {$domain} was removed due to an error.");
            }

            return 1;
        }

        return 0;
    }

    private function validateDomain(string $domain): bool
    {
        return Str::ucfirst($domain) === $domain && preg_match('/^[A-Z][a-zA-Z0-9]*$/', $domain);
    }

    private function createDirectories(string $basePath): void
    {
        $directories = [
            'Application/Command',
            'Application/Event',
            'Application/Query',
            'Domain/Model',
            'Domain/Repository',
            'Infrastructure/ApiPlatform/Handler/Processor',
            'Infrastructure/ApiPlatform/Handler/Provider',
            'Infrastructure/ApiPlatform/Payload',
            'Infrastructure/ApiPlatform/Resource',
            'Infrastructure/Laravel/Command',
            'Infrastructure/Laravel/Factory',
            'Infrastructure/Laravel/FormRequest',
            'Infrastructure/Laravel/Migration',
            'Infrastructure/Laravel/Repository',
            'Infrastructure/Laravel/Provider',
            'Infrastructure/Laravel/Seeder',
        ];

        foreach ($directories as $dir) {
            File::makeDirectory("{$basePath}/{$dir}", 0o755, true);
        }
    }

    private function createFiles(string $basePath, string $domain): void
    {
        $domainLowerCase = strtolower($domain);

        $files = [
            ["Application/Command/Create{$domain}Command.php", 'CreateCommand', []],
            ["Application/Command/Delete{$domain}Command.php", 'DeleteCommand', []],
            ["Application/Command/Patch{$domain}Command.php", 'PatchCommand', []],
            ["Application/Command/Put{$domain}Command.php", 'PutCommand', []],
            ["Application/Command/Create{$domain}CommandHandler.php", 'CreateCommandHandler', []],
            ["Application/Command/Delete{$domain}CommandHandler.php", 'DeleteCommandHandler', []],
            ["Application/Command/Patch{$domain}CommandHandler.php", 'PatchCommandHandler', []],
            ["Application/Command/Put{$domain}CommandHandler.php", 'PutCommandHandler', []],
            [
                "Application/Event/{$domain}CreatedEvent.php",
                'Event',
                ['properties' => $this->getEventProperties()],
            ],
            ["Application/Event/{$domain}CreatedEventHandler.php", 'EventHandler', []],
            ["Application/Query/Find{$domain}Query.php", 'FindQuery', []],
            ["Application/Query/Find{$domain}sQuery.php", 'FindPluralQuery', []],
            ["Application/Query/Find{$domain}QueryHandler.php", 'FindQueryHandler', []],
            ["Application/Query/Find{$domain}sQueryHandler.php", 'FindPluralQueryHandler', []],
            ["Infrastructure/Laravel/FormRequest/Create{$domain}FormRequest.php", 'CreateFormRequest', []],
            ["Infrastructure/Laravel/FormRequest/Patch{$domain}FormRequest.php", 'PatchFormRequest', []],
            ["Infrastructure/Laravel/FormRequest/Put{$domain}FormRequest.php", 'PutFormRequest', []],
            ["Domain/Model/{$domain}.php", 'Model', []],
            ["Domain/Repository/{$domain}RepositoryInterface.php", 'RepositoryInterface', []],
            ["Infrastructure/ApiPlatform/Handler/Processor/Create{$domain}Processor.php", 'CreateProcessor', []],
            ["Infrastructure/ApiPlatform/Handler/Processor/Delete{$domain}Processor.php", 'DeleteProcessor', []],
            ["Infrastructure/ApiPlatform/Handler/Processor/Patch{$domain}Processor.php", 'PatchProcessor', []],
            ["Infrastructure/ApiPlatform/Handler/Processor/Put{$domain}Processor.php", 'PutProcessor', []],
            [
                "Infrastructure/ApiPlatform/Handler/Provider/{$domain}CollectionProvider.php",
                'CollectionProvider',
                [],
            ],
            ["Infrastructure/ApiPlatform/Handler/Provider/{$domain}ItemProvider.php", 'ItemProvider', []],
            ["Infrastructure/ApiPlatform/Resource/{$domain}Resource.php", 'Resource', []],
            ["Infrastructure/Laravel/Factory/{$domain}Factory.php", 'Factory', []],
            ["Infrastructure/Laravel/Command/{$domain}SeederCommand.php", 'SeederCommand', []],
            ["Infrastructure/Laravel/Migration/create_{$domainLowerCase}_table.php", 'Migration', []],
            ["Infrastructure/Laravel/Repository/{$domain}EloquentRepository.php", 'EloquentRepository', []],
            ["Infrastructure/Laravel/Provider/{$domain}ServiceProvider.php", 'ServiceProvider', []],
        ];

        foreach ($files as [$filePath, $stub, $variables]) {
            $directoryPath = dirname("{$basePath}/{$filePath}");

            if (! File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0o755, true);
            }

            $this->createFileFromStub(
                "{$basePath}/{$filePath}",
                $stub,
                array_merge($variables, [
                    'domain' => $domain,
                    'domainCamelCase' => Str::camel($domain),
                    'domain_lc' => strtolower($domain),
                    'domainLowerCase' => strtolower($domain),
                    'prefix' => '',
                    'TIMESTAMP' => date('Y_m_d_His'),
                    'DATE' => date('Y-m-d'),
                    'DATETIME' => date('Y-m-d H:i:s'),
                    'YEAR' => date('Y'),
                ]),
            );
        }
    }

    private function getEventProperties(): string
    {
        $properties = [
            'int $id',
            'string $title',
            'string $author',
        ];

        return implode(",\n        ", $properties);
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function createFileFromStub(string $filePath, string $stub, array $variables): void
    {
        $stubPath = base_path("modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/{$stub}.stub");

        if (! File::exists($stubPath)) {
            throw new Exception("Stub file {$stub}.stub not found.");
        }

        $stubContent = File::get($stubPath);
        $content = $this->replaceStubVariables($stubContent, $variables);
        File::put($filePath, $content);
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function replaceStubVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            // Support both {{variable}} and {{ variable }} formats
            $content = str_replace("{{ {$key} }}", $value, $content);
            $content = str_replace("{{{$key}}}", $value, $content);
        }

        return $content;
    }
}
