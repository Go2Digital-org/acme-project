<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\text;

use Modules\DevTools\Infrastructure\Laravel\Command\Traits\HexGeneratorTrait;

class AddHexEventCommand extends Command
{
    use HexGeneratorTrait;

    /**
     * @var string
     */
    protected $signature = 'add:hex:event {domain? : Domain name} {name? : Event name} {--force : Force overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Add Created Event and Created Event Handler to a specific domain';

    public function handle(): int
    {
        $this->initializeCodeGenerator();

        $domain = $this->askDomain();
        $types = $this->askTypes();

        if ($domain && $types !== []) {
            $basePath = base_path("modules/{$domain}");

            if (! File::exists($basePath)) {
                $this->error("The domain {$domain} does not exist.");

                return 1;
            }

            $this->createFiles($basePath, $domain, $types);
            $this->info("Event files for selected types have been added to the {$domain} domain.");
        } else {
            $this->error('Domain or types not provided.');
        }

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

    /** @return array<array-key, mixed> */
    private function askTypes(): array
    {
        $options = [
            'CreatedEvent' => 'Created Event',
            'CreatedEventHandler' => 'Created Event Handler',
        ];

        return multiselect(
            label: 'Select the types of event components to add',
            options: $options,
            required: 'You must select at least one option',
            hint: 'Use space to select multiple options and Enter to confirm.',
        );
    }

    /**
     * @param  array<int, string>  $types
     */
    private function createFiles(string $basePath, string $domain, array $types): void
    {
        $eventProperties = null;

        // Ask for event properties if CreatedEvent is selected
        if (in_array('CreatedEvent', $types, true)) {
            $eventProperties = $this->getEventProperties();
        }

        $files = [
            'CreatedEvent' => [
                [
                    "Application/Event/{$domain}CreatedEvent.php",
                    'Event.stub',
                    ['properties' => $eventProperties ?? $this->getDefaultEventProperties()],
                ],
            ],
            'CreatedEventHandler' => [
                ["Application/Event/{$domain}CreatedEventHandler.php", 'EventHandler.stub', []],
            ],
        ];

        foreach ($types as $type) {
            if (isset($files[$type])) {
                $this->createFilesFromStubs($basePath, $domain, $files[$type]);
            }
        }
    }

    /**
     * @param  array<int, array{string, string, array<string, mixed>}>  $files
     */
    private function createFilesFromStubs(string $basePath, string $domain, array $files): void
    {
        foreach ($files as [$filePath, $stub, $variables]) {
            $this->createFileFromStub("{$basePath}/{$filePath}", $stub, array_merge($variables, [
                'domain' => $domain,
                'domainCamelCase' => Str::camel($domain),
                'domain_lc' => strtolower($domain),
                'prefix' => '',
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

    private function getEventProperties(): string
    {
        $this->info('Define event properties (press Enter with empty value to finish):');
        $properties = [];

        while (true) {
            $property = text(
                label: 'Enter property (type $name) or press Enter to finish',
                placeholder: 'e.g., int $id, string $title',
                required: false,
            );

            if ($property === '' || $property === '0') {
                break;
            }

            $properties[] = $property;
        }

        if ($properties === []) {
            return $this->getDefaultEventProperties();
        }

        return 'public readonly ' . implode(",\n        public readonly ", $properties);
    }

    private function getDefaultEventProperties(): string
    {
        return 'public readonly int $id';
    }
}
