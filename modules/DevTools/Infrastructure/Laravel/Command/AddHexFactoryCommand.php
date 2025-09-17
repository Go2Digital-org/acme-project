<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\DevTools\Infrastructure\Laravel\Command\Traits\HexGeneratorTrait;

class AddHexFactoryCommand extends Command
{
    use HexGeneratorTrait;

    /**
     * @var string
     */
    protected $signature = 'hex:add:factory {domain? : Domain name} {name? : Factory name} {--force : Force overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Add a factory to a specific domain';

    public function handle(): int
    {
        $this->initializeCodeGenerator();

        $domain = $this->askDomain();
        $factoryName = $this->ask('Enter the factory name (leave blank to use domain name)', "{$domain}Factory");

        if (! $domain) {
            $this->error('Domain not provided.');

            return 0;
        }

        $basePath = base_path("modules/{$domain}");

        if (! File::exists($basePath)) {
            $this->error("The domain {$domain} does not exist.");

            return 1;
        }

        $this->createFactory($basePath, $domain, $factoryName !== '' ? $factoryName : "{$domain}Factory");
        $this->info("Factory has been added to the {$domain} domain.");

        return 0;
    }

    private function askDomain(): ?string
    {
        // Use domain argument if provided
        $domainArg = $this->argument('domain');

        if ($domainArg) {
            return $domainArg;
        }

        $domains = $this->codeGenerator->getAvailableDomains();

        if ($domains === []) {
            $this->error('No domains available.');

            return null;
        }

        return $this->choice('Select a domain', $domains);
    }

    private function createFactory(string $basePath, string $domain, string $factoryName): void
    {
        $factoryName = str_ends_with($factoryName, 'Factory') ? $factoryName : "{$factoryName}Factory";
        $filePath = "{$basePath}/Infrastructure/Laravel/Factory/{$factoryName}.php";
        $stubPath = base_path('modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/Factory.stub');

        $this->createFileFromStub($filePath, $stubPath, [
            'domain' => $domain,
            'factoryName' => $factoryName,
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
