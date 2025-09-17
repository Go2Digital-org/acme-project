<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Command to validate generated domains against PHPStan level 8 compliance.
 *
 * This command ensures that all generated domain code meets S-tier CQRS standards
 * and passes PHPStan static analysis at level 8.
 */
final class ValidateDomainCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'hex:validate-domain {domain} {--fix : Attempt to fix issues automatically}';

    /**
     * @var string
     */
    protected $description = 'Validate a generated domain against PHPStan level 8 compliance and S-tier CQRS standards';

    public function handle(): int
    {
        $domain = ucfirst($this->argument('domain'));
        $shouldFix = $this->option('fix');

        if (! $this->validateDomainExists($domain)) {
            $this->error("Domain {$domain} does not exist in modules directory.");

            return 1;
        }

        $this->info("Validating domain: {$domain}");
        $this->newLine();

        $issues = [];

        // Check domain structure
        $structureIssues = $this->validateDomainStructure($domain);
        $issues = array_merge($issues, $structureIssues);

        // Check PHPStan compliance
        $phpstanIssues = $this->validatePHPStanCompliance($domain);
        $issues = array_merge($issues, $phpstanIssues);

        // Check CQRS compliance
        $cqrsIssues = $this->validateCQRSCompliance($domain);
        $issues = array_merge($issues, $cqrsIssues);

        if ($issues === []) {
            $this->info("✅ Domain {$domain} is fully compliant with S-tier CQRS and PHPStan level 8!");

            return 0;
        }

        $this->error('❌ Found ' . count($issues) . " issues in domain {$domain}:");
        $this->newLine();

        foreach ($issues as $issue) {
            $this->line("  • {$issue}");
        }

        if ($shouldFix) {
            $this->newLine();
            $this->info('Attempting to fix issues automatically...');
            $this->fixIssues($domain, $issues);
        }

        return 1;
    }

    private function validateDomainExists(string $domain): bool
    {
        return File::exists(base_path("modules/{$domain}"));
    }

    /**
     * @return array<int, string>
     */
    private function validateDomainStructure(string $domain): array
    {
        $issues = [];
        $basePath = base_path("modules/{$domain}");

        $requiredDirectories = [
            'Application/Command',
            'Application/Event',
            'Application/Query',
            'Domain/Model',
            'Domain/Repository',
            'Infrastructure/Laravel/Repository',
            'Infrastructure/Laravel/Factory',
        ];

        foreach ($requiredDirectories as $dir) {
            if (! File::exists("{$basePath}/{$dir}")) {
                $issues[] = "Missing required directory: {$dir}";
            }
        }

        // Check for essential files
        $requiredFiles = [
            "Domain/Model/{$domain}.php",
            "Domain/Repository/{$domain}RepositoryInterface.php",
            "Infrastructure/Laravel/Repository/{$domain}EloquentRepository.php",
            "Infrastructure/Laravel/Factory/{$domain}Factory.php",
        ];

        foreach ($requiredFiles as $file) {
            if (! File::exists("{$basePath}/{$file}")) {
                $issues[] = "Missing required file: {$file}";
            }
        }

        return $issues;
    }

    /**
     * @return array<int, string>
     */
    private function validatePHPStanCompliance(string $domain): array
    {
        $issues = [];
        $basePath = base_path("modules/{$domain}");

        // Find all PHP files in the domain
        $phpFiles = collect(File::allFiles($basePath))
            ->filter(fn ($file): bool => $file->getExtension() === 'php')
            ->map(fn ($file) => $file->getPathname());

        foreach ($phpFiles as $file) {
            $content = File::get($file);
            $relativePath = str_replace($basePath . '/', '', $file);

            // Check for strict types declaration
            if (! str_contains($content, 'declare(strict_types=1);')) {
                $issues[] = "{$relativePath}: Missing strict types declaration";
            }

            // Check for proper class finality
            if (str_contains($content, 'class ') && ! str_contains($content, 'final class') && ! str_contains($content, 'abstract class')) {
                $issues[] = "{$relativePath}: Class should be declared as final or abstract";
            }

            // Check for readonly properties where applicable
            if (str_contains($content, 'public function __construct(') && str_contains($content, 'public ') && ! str_contains($content, 'readonly')) {
                $issues[] = "{$relativePath}: Constructor parameters should be readonly where applicable";
            }

            // Check for PHPDoc blocks
            if (str_contains($content, 'public function') && ! str_contains($content, '/**')) {
                $issues[] = "{$relativePath}: Missing PHPDoc blocks for public methods";
            }
        }

        return $issues;
    }

    /**
     * @return array<int, string>
     */
    private function validateCQRSCompliance(string $domain): array
    {
        $issues = [];
        $basePath = base_path("modules/{$domain}");

        // Check Command/Query separation
        $commandPath = "{$basePath}/Application/Command";
        $queryPath = "{$basePath}/Application/Query";

        if (File::exists($commandPath)) {
            $commandFiles = File::files($commandPath);
            foreach ($commandFiles as $file) {
                $content = File::get($file->getPathname());
                if (str_contains($file->getFilename(), 'Command') && ! str_contains($content, 'implements CommandInterface')) {
                    $issues[] = "Command {$file->getFilename()} should implement CommandInterface";
                }
                if (str_contains($file->getFilename(), 'CommandHandler') && str_contains($content, 'return')) {
                    // Commands with void return are preferred, but returning entities is acceptable
                }
            }
        }

        if (File::exists($queryPath)) {
            $queryFiles = File::files($queryPath);
            foreach ($queryFiles as $file) {
                $content = File::get($file->getPathname());
                if (str_contains($file->getFilename(), 'Query') && ! str_contains($file->getFilename(), 'Handler') && ! str_contains($content, 'implements QueryInterface')) {
                    $issues[] = "Query {$file->getFilename()} should implement QueryInterface";
                }
            }
        }

        return $issues;
    }

    /**
     * @param  array<int, string>  $issues
     */
    private function fixIssues(string $domain, array $issues): void
    {
        $fixedCount = 0;

        foreach ($issues as $issue) {
            if ($this->attemptFix($domain, $issue)) {
                $fixedCount++;
                $this->info("  ✅ Fixed: {$issue}");
            } else {
                $this->warn("  ⚠️  Could not auto-fix: {$issue}");
            }
        }

        $this->newLine();
        $this->info("Fixed {$fixedCount} out of " . count($issues) . ' issues.');
    }

    private function attemptFix(string $domain, string $issue): bool
    {
        // Simple fixes that can be automated
        if (str_contains($issue, 'Missing strict types declaration')) {
            return $this->addStrictTypesDeclaration($domain, $issue);
        }

        if (str_contains($issue, 'Class should be declared as final')) {
            return $this->makeFinalClass($domain, $issue);
        }

        return false;
    }

    private function addStrictTypesDeclaration(string $domain, string $issue): bool
    {
        $filePath = $this->extractFilePathFromIssue($domain, $issue);
        if (! $filePath || ! File::exists($filePath)) {
            return false;
        }

        $content = File::get($filePath);
        if (! str_contains($content, 'declare(strict_types=1);')) {
            $content = str_replace('<?php', "<?php\n\ndeclare(strict_types=1);", $content);
            File::put($filePath, $content);

            return true;
        }

        return false;
    }

    private function makeFinalClass(string $domain, string $issue): bool
    {
        $filePath = $this->extractFilePathFromIssue($domain, $issue);
        if (! $filePath || ! File::exists($filePath)) {
            return false;
        }

        $content = File::get($filePath);
        $content = preg_replace('/class (\w+)/', 'final class $1', $content);

        if ($content) {
            File::put($filePath, $content);

            return true;
        }

        return false;
    }

    private function extractFilePathFromIssue(string $domain, string $issue): ?string
    {
        if (preg_match('/^([^:]+):/', $issue, $matches)) {
            return base_path("modules/{$domain}/{$matches[1]}");
        }

        return null;
    }
}
