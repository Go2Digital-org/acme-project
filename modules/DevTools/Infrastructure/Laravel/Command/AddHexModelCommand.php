<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\DevTools\Infrastructure\Laravel\Command\Traits\HexGeneratorTrait;

class AddHexModelCommand extends Command
{
    use HexGeneratorTrait;

    /**
     * @var string
     */
    protected $signature = 'hex:add:model {domain? : Domain name} {name? : Model name} {--force : Force overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Add a domain model to a selected domain';

    public function handle(): int
    {
        $this->initializeCodeGenerator();

        $domains = $this->codeGenerator->getAvailableDomains();

        if ($domains === []) {
            $this->error(
                'No domains found. Make sure you have created domains using the hex:add:domain command.',
            );

            return 1;
        }

        $domain = $this->choice('Select a domain to add the model to:', $domains);
        $modelName = $this->ask('Enter the model name (leave blank to use domain name)', $domain);
        $basePath = base_path("modules/{$domain}/Domain/Model");

        if (! $this->isDomainValid($domain)) {
            $this->error("The domain {$domain} does not have a valid structure.");

            return 1;
        }

        try {
            $this->createModel($basePath, $domain, $modelName !== '' ? $modelName : $domain);
            $this->info("Model {$modelName} for the domain {$domain} created successfully.");
        } catch (Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());

            return 1;
        }

        return 0;
    }

    private function isDomainValid(string $domain): bool
    {
        $expectedPaths = [
            "modules/{$domain}/Domain/Model",
            "modules/{$domain}/Infrastructure/Laravel/Factory",
        ];

        foreach ($expectedPaths as $path) {
            if (! File::exists(base_path($path))) {
                return false;
            }
        }

        return true;
    }

    private function createModel(string $basePath, string $domain, string $modelName): void
    {
        $filePath = "{$basePath}/{$modelName}.php";
        $stubPath = base_path('modules/DevTools/Infrastructure/Laravel/Stubs/hexagonal/Model.stub');

        $this->createFileFromStub($filePath, $stubPath, [
            'domain' => $domain,
            'modelName' => $modelName,
            'domainCamelCase' => Str::camel($domain),
            'domainLowerCase' => Str::lower($domain),
        ]);
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function createFileFromStub(string $filePath, string $stubPath, array $variables): void
    {
        if (! File::exists($stubPath)) {
            throw new Exception("Stub file {$stubPath} not found.");
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
