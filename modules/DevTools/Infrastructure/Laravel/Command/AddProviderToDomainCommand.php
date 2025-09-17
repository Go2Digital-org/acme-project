<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\multiselect;

use Modules\DevTools\Infrastructure\Laravel\Command\Traits\HexGeneratorTrait;

class AddProviderToDomainCommand extends Command
{
    use HexGeneratorTrait;

    /**
     * @var string
     */
    protected $signature = 'hex:add:provider {domain? : Domain name} {name? : Provider name} {--force : Force overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Add API Platform state providers (Collection, Item) to a selected domain';

    public function handle(): int
    {
        $this->initializeCodeGenerator();

        $domain = $this->askDomain();
        $prefix = $this->ask('Enter a prefix for the files (leave blank if none)', '');
        $providerTypes = $this->askProviderTypes();

        if ($domain && $providerTypes !== []) {
            $basePath = base_path("modules/{$domain}");

            if (! File::exists($basePath)) {
                $this->error("The domain {$domain} does not exist.");

                return 1;
            }

            $this->createFiles($basePath, $domain, $prefix, $providerTypes);
            $this->info("Providers for selected types have been added to the {$domain} domain.");
        } else {
            $this->error('Domain or provider types not provided.');
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
    private function askProviderTypes(): array
    {
        $options = [
            'CollectionProvider' => 'Collection Provider',
            'ItemProvider' => 'Item Provider',
        ];

        return multiselect(
            label: 'Select the types of providers you want to add',
            options: $options,
            required: 'You must select at least one provider type',
            hint: 'Use space to select multiple options and Enter to confirm.',
        );
    }

    /**
     * @param  array<int, string>  $providerTypes
     */
    private function createFiles(string $basePath, string $domain, string $prefix, array $providerTypes): void
    {
        $files = [
            'CollectionProvider' => [
                ["Infrastructure/ApiPlatform/Handler/Provider/{$prefix}{$domain}CollectionProvider.php", 'CollectionProvider.stub', []],
            ],
            'ItemProvider' => [
                ["Infrastructure/ApiPlatform/Handler/Provider/{$prefix}{$domain}ItemProvider.php", 'ItemProvider.stub', []],
            ],
        ];

        foreach ($providerTypes as $type) {
            if (isset($files[$type])) {
                $this->createFilesFromStubs($basePath, $domain, $prefix, $files[$type]);
            }
        }
    }

    /**
     * @param  array<int, array{string, string, array<string, mixed>}>  $files
     */
    private function createFilesFromStubs(string $basePath, string $domain, string $prefix, array $files): void
    {
        foreach ($files as [$filePath, $stub, $variables]) {
            $this->createFileFromStub("{$basePath}/{$filePath}", $stub, array_merge($variables, [
                'domain' => $domain,
                'prefix' => $prefix,
                'domainCamelCase' => Str::camel($domain),
                'domainLowerCase' => Str::lower($domain),
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
