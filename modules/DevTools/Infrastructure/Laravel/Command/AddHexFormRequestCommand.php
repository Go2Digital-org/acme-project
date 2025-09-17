<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;

use function Laravel\Prompts\multiselect;

use Modules\DevTools\Infrastructure\Laravel\Command\Traits\HexGeneratorTrait;

class AddHexFormRequestCommand extends Command
{
    use HexGeneratorTrait;

    /**
     * @var string
     */
    protected $signature = 'hex:add:form-request {domain?} {name?} {--force : Force overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Add Create, Patch, or Put FormRequest to a specific domain';

    public function handle(): int
    {
        $this->initializeCodeGenerator();

        $domain = $this->getDomainName();

        if ($domain === '' || $domain === '0') {
            return 1;
        }

        if (! $this->validateDomain($domain)) {
            $this->error("The domain {$domain} does not exist.");

            return 1;
        }

        $name = $this->getFormRequestName($domain);

        if ($name === '' || $name === '0') {
            $this->error('FormRequest name not provided.');

            return 1;
        }

        if (! $this->validateClassName($name)) {
            $this->error('Invalid class name format. Use PascalCase.');

            return 1;
        }

        $types = $this->getFormRequestTypes();

        if ($types === []) {
            $this->error('No form request types selected.');

            return 1;
        }

        $successCount = 0;

        foreach ($types as $type) {
            $success = $this->generateFormRequest($domain, $name, $type);

            if ($success) {
                $successCount++;
            }
        }

        if ($successCount > 0) {
            $this->info("Generated {$successCount} FormRequest file(s) for the {$domain} domain.");
            $this->displayFormRequestSummary($domain, $name, $types);
        }

        return $successCount > 0 ? 0 : 1;
    }

    /**
     * Get or prompt for form request name.
     */
    protected function getFormRequestName(string $domain): string
    {
        $nameArgument = $this->argument('name');

        if ($nameArgument !== null && $nameArgument !== '') {
            return $nameArgument;
        }

        return $this->ask('Enter the form request name (without type prefix like "Create")', $domain) ?? '';
    }

    /**
     * Get form request types to generate.
     */
    /** @return array<array-key, mixed> */
    protected function getFormRequestTypes(): array
    {
        $options = [
            'Create' => 'CreateFormRequest - For creating new resources',
            'Update' => 'UpdateFormRequest - For updating resources (PUT)',
            'Patch' => 'PatchFormRequest - For partial updates (PATCH)',
        ];

        return multiselect(
            label: 'Select the types of form requests to generate',
            options: $options,
            required: true,
        );
    }

    /**
     * Generate a specific form request type.
     */
    protected function generateFormRequest(string $domain, string $name, string $type): bool
    {
        $stubName = match ($type) {
            'Create' => 'CreateFormRequest.stub',
            'Update' => 'UpdateFormRequest.stub',
            'Patch' => 'PatchFormRequest.stub',
            default => null,
        };

        if (! $stubName) {
            $this->error("Unknown form request type: {$type}");

            return false;
        }

        return $this->generateFile(
            $domain,
            "{$type}{$name}",
            'FormRequest',
            $stubName,
            [
                'formRequestType' => $type,
                'baseName' => $name,
            ],
            $this->shouldForceOverwrite(),
        );
    }

    /**
     * Display summary of generated form requests.
     *
     * @param  array<int, string>  $types
     */
    protected function displayFormRequestSummary(string $domain, string $name, array $types): void
    {
        $this->info('');
        $this->info("Generated FormRequest files for {$domain} domain:");
        $this->line("  Base name: {$name}");
        $this->line('  Types generated:');

        foreach ($types as $type) {
            $className = "{$type}{$name}FormRequest";
            $this->line("    - {$className}");
        }

        $this->info('');
        $this->info('Usage in controllers:');

        foreach ($types as $type) {
            $className = "{$type}{$name}FormRequest";
            $this->line("  public function {$type}({$className} \$request) { ... }");
        }
    }
}
