<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\multiselect;

use Modules\DevTools\Infrastructure\Laravel\Command\Traits\HexGeneratorTrait;

class AddHexFindQueryCommand extends Command
{
    use HexGeneratorTrait;

    /**
     * @var string
     */
    protected $signature = 'add:hex:find-query {domain? : Domain name} {name? : Query name} {--force : Force overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Add Find Query, Find Query Handler, or Plural versions to a specific domain';

    public function handle(): int
    {
        $this->initializeCodeGenerator();

        $domain = $this->askDomain();
        $prefix = $this->ask('Enter a prefix for the files (leave blank if none)', '');
        $types = $this->askTypes();

        if ($domain && $types !== []) {
            $basePath = base_path("modules/{$domain}");

            if (! File::exists($basePath)) {
                $this->error("The domain {$domain} does not exist.");

                return 1;
            }

            $this->createFiles($basePath, $domain, $prefix, $types);
            $this->info("Query files for selected types have been added to the {$domain} domain.");
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

    /**
     * @return array<string, mixed>
     */
    private function askTypes(): array
    {
        $options = [
            'FindQuery' => 'Find Query (single item)',
            'FindQueryHandler' => 'Find Query Handler (single item)',
            'FindPluralQuery' => 'Find Plural Query (collection)',
            'FindPluralQueryHandler' => 'Find Plural Query Handler (collection)',
        ];

        return multiselect(
            label: 'Select the types of queries and handlers to add',
            options: $options,
            required: 'You must select at least one option',
            hint: 'Use space to select multiple options and Enter to confirm.',
        );
    }

    /**
     * @param  array<string, mixed>  $types
     */
    private function createFiles(string $basePath, string $domain, string $prefix, array $types): void
    {
        $files = [
            'FindQuery' => [
                ["Application/Query/{$prefix}Find{$domain}Query.php", 'FindQuery.stub', []],
            ],
            'FindQueryHandler' => [
                ["Application/Query/{$prefix}Find{$domain}QueryHandler.php", 'FindQueryHandler.stub', []],
            ],
            'FindPluralQuery' => [
                ["Application/Query/{$prefix}Find{$domain}sQuery.php", 'FindPluralQuery.stub', []],
            ],
            'FindPluralQueryHandler' => [
                ["Application/Query/{$prefix}Find{$domain}sQueryHandler.php", 'FindPluralQueryHandler.stub', []],
            ],
        ];

        foreach ($types as $type) {
            if (isset($files[$type])) {
                $this->createFilesFromStubs($basePath, $domain, $prefix, $files[$type]);
            }
        }
    }

    /**
     * @param  array<int, array<int, mixed>>  $files
     */
    private function createFilesFromStubs(string $basePath, string $domain, string $prefix, array $files): void
    {
        foreach ($files as [$filePath, $stub, $variables]) {
            $this->createFileFromStub("{$basePath}/{$filePath}", $stub, array_merge($variables, [
                'domain' => $domain,
                'prefix' => $prefix,
                'domainCamelCase' => Str::camel($domain),
                'domain_lc' => strtolower($domain),
                'domainPlural' => Str::plural($domain),
                'domainPluralCamelCase' => Str::camel(Str::plural($domain)),
                'domainPlural_lc' => strtolower(Str::plural($domain)),
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
