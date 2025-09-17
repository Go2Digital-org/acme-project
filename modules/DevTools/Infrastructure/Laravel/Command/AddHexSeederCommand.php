<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\DevTools\Infrastructure\Laravel\Command\Traits\HexGeneratorTrait;

class AddHexSeederCommand extends Command
{
    use HexGeneratorTrait;

    /**
     * @var string
     */
    protected $signature = 'hex:add:seeder {domain? : Domain name} {name? : Seeder name} {--force : Force overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Add a seeder command to a specific domain';

    public function handle(): int
    {
        $this->initializeCodeGenerator();

        $domain = $this->askDomain();
        $seederName = $this->ask('Enter the seeder name (leave blank to use domain name)', "{$domain}Seeder");

        if (! $domain) {
            $this->error('Domain not provided.');

            return 0;
        }

        $basePath = base_path("modules/{$domain}");

        if (! File::exists($basePath)) {
            $this->error("The domain {$domain} does not exist.");

            return 1;
        }

        $this->createSeeder($basePath, $domain, $seederName !== '' ? $seederName : "{$domain}Seeder");
        $this->info("Seeder command has been added to the {$domain} domain.");

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

    private function createSeeder(string $basePath, string $domain, string $seederName): void
    {
        $commandName = str_ends_with($seederName, 'Command') ? $seederName : "{$seederName}Command";
        $filePath = "{$basePath}/Infrastructure/Laravel/Command/{$commandName}.php";
        $stubPath = base_path('modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/SeederCommand.stub');

        $this->createFileFromStub($filePath, $stubPath, [
            'domain' => $domain,
            'seederName' => $seederName,
            'commandName' => $commandName,
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
