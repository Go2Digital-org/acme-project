<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\multiselect;

use Modules\DevTools\Infrastructure\Laravel\Command\Traits\HexGeneratorTrait;

class AddProcessorToDomainCommand extends Command
{
    use HexGeneratorTrait;

    /**
     * @var string
     */
    protected $signature = 'hex:add:processor {domain? : Domain name} {name? : Processor name} {--force : Force overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Add API Platform state processors (Create, Delete, Patch, Put) to a selected domain';

    public function handle(): int
    {
        $this->initializeCodeGenerator();

        $domain = $this->askDomain();
        $prefix = $this->ask('Enter a prefix for the files (leave blank if none)', '');
        $actions = $this->askActions();

        if ($domain && $actions !== []) {
            $basePath = base_path("modules/{$domain}");

            if (! File::exists($basePath)) {
                $this->error("The domain {$domain} does not exist.");

                return 1;
            }

            $this->createFiles($basePath, $domain, $prefix, $actions);
            $this->info("Processors for selected actions have been added to the {$domain} domain.");
        } else {
            $this->error('Domain or actions not provided.');
        }

        return 0;
    }

    private function askDomain(): ?string
    {
        $domains = $this->codeGenerator->getAvailableDomains();

        if ($domains === []) {
            $this->error('No domains available.');

            return null;
        }

        return $this->choice('Select a domain', $domains);
    }

    /** @return array<array-key, mixed> */
    private function askActions(): array
    {
        $options = [
            'Create' => 'Create Processor',
            'Delete' => 'Delete Processor',
            'Patch' => 'Patch Processor',
            'Put' => 'Put Processor',
        ];

        return multiselect(
            label: 'Select the processor actions you want to add',
            options: $options,
            required: 'You must select at least one processor action',
            hint: 'Use space to select multiple options and Enter to confirm.',
        );
    }

    /**
     * @param  array<int, string>  $actions
     */
    private function createFiles(string $basePath, string $domain, string $prefix, array $actions): void
    {
        $files = [
            'Create' => [
                ["Infrastructure/ApiPlatform/Handler/Processor/{$prefix}Create{$domain}Processor.php", 'CreateProcessor.stub', []],
            ],
            'Delete' => [
                ["Infrastructure/ApiPlatform/Handler/Processor/{$prefix}Delete{$domain}Processor.php", 'DeleteProcessor.stub', []],
            ],
            'Patch' => [
                ["Infrastructure/ApiPlatform/Handler/Processor/{$prefix}Patch{$domain}Processor.php", 'PatchProcessor.stub', []],
            ],
            'Put' => [
                ["Infrastructure/ApiPlatform/Handler/Processor/{$prefix}Put{$domain}Processor.php", 'PutProcessor.stub', []],
            ],
        ];

        foreach ($actions as $action) {
            if (isset($files[$action])) {
                $this->createFilesFromStubs($basePath, $domain, $prefix, $files[$action], $action);
            }
        }
    }

    /**
     * @param  array<int, array{string, string, array<string, mixed>}>  $files
     */
    private function createFilesFromStubs(string $basePath, string $domain, string $prefix, array $files, string $action): void
    {
        foreach ($files as [$filePath, $stub, $variables]) {
            $this->createFileFromStub("{$basePath}/{$filePath}", $stub, array_merge($variables, [
                'domain' => $domain,
                'action' => $action,
                'prefix' => $prefix,
                'domainCamelCase' => Str::camel($domain),
                'domainLowerCase' => Str::lower($domain),
                'actionCamelCase' => Str::camel($action),
            ]));
        }
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function createFileFromStub(string $filePath, string $stub, array $variables): void
    {
        $stubPath = base_path("modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/{$stub}");

        if (! File::exists($stubPath)) {
            $this->error("Stub file {$stub} not found.");

            return;
        }

        // Ensure directory exists
        $directory = dirname($filePath);

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0o755, true);
        }

        $stubContent = File::get($stubPath);
        $content = $this->replaceStubVariables($stubContent, $variables);
        File::put($filePath, $content);

        $this->info("Generated: {$filePath}");
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function replaceStubVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace("{{{$key}}}", $value, $content);
        }

        return $content;
    }
}
