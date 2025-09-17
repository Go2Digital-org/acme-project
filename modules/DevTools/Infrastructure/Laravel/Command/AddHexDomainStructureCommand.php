<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\DevTools\Infrastructure\Laravel\Command\Traits\HexGeneratorTrait;

class AddHexDomainStructureCommand extends Command
{
    use HexGeneratorTrait;

    /**
     * @var string
     */
    protected $signature = 'hex:add:structure {domain} {name?} {--force : Force overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Add the entire empty structure for a specified domain';

    public function handle(): int
    {
        $domain = ucfirst($this->argument('domain'));

        if (! $this->validateDomainName($domain)) {
            $this->error(
                'Invalid domain name. ' .
                'The domain name must start with an uppercase letter and contain only alphanumeric characters.',
            );

            return 1;
        }

        $basePath = base_path("modules/{$domain}");

        if (File::exists($basePath)) {
            $this->error("The domain {$domain} already exists.");

            return 1;
        }

        try {
            $this->createDirectories($basePath);
            $this->info("Empty structure for domain '{$domain}' created successfully.");
        } catch (Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());

            return 1;
        }

        return 0;
    }

    private function validateDomainName(string $domain): bool
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
            $fullPath = "{$basePath}/{$dir}";

            if (! File::exists($fullPath)) {
                File::makeDirectory($fullPath, 0o755, true);
                $this->info("Directory created: {$fullPath}");
            } else {
                $this->warn("Directory already exists: {$fullPath}");
            }
        }
    }
}
