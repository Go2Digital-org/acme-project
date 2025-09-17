<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\DevTools\Infrastructure\Laravel\Command\Traits\HexGeneratorTrait;

class AddHexResourceCommand extends Command
{
    use HexGeneratorTrait;

    /**
     * @var string
     */
    protected $signature = 'hex:add:resource {domain? : Domain name} {name? : Resource name} {--force : Force overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Add an API Platform resource to a specific domain';

    public function handle(): int
    {
        $this->initializeCodeGenerator();

        $domain = $this->askDomain();
        $prefix = $this->ask('Enter a prefix for the resource (leave blank if none)', '');

        if (! $domain) {
            $this->error('Domain not provided.');

            return 0;
        }

        $basePath = base_path("modules/{$domain}");

        if (! File::exists($basePath)) {
            $this->error("The domain {$domain} does not exist.");

            return 1;
        }

        $this->createResource($basePath, $domain, $prefix);
        $this->info("Resource has been added to the {$domain} domain.");

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

    private function createResource(string $basePath, string $domain, string $prefix): void
    {
        $resourceName = $prefix !== '' && $prefix !== '0' ? "{$prefix}{$domain}Resource" : "{$domain}Resource";
        $filePath = "{$basePath}/Infrastructure/ApiPlatform/Resource/{$resourceName}.php";
        $stubPath = base_path('modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/Resource.stub');

        $this->createFileFromStub($filePath, $stubPath, [
            'domain' => $domain,
            'prefix' => $prefix,
            'domainCamelCase' => Str::camel($domain),
            'domainLowerCase' => Str::lower($domain),
        ]);
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function createFileFromStub(string $filePath, string $stubPath, array $variables): void
    {
        if (! File::exists($stubPath)) {
            $this->error("Stub file {$stubPath} not found.");

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
     * @param  array<string, string>  $variables
     */
    private function replaceStubVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace("{{{$key}}}", $value, $content);
        }

        return $content;
    }
}
