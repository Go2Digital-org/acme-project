<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Modules\DevTools\Domain\Service\DomainAnalysisService;

class CompletePartialDomainsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'hex:complete-partial-domains
                            {--component= : Complete only specific component across all domains}
                            {--min-completeness=10 : Minimum completeness percentage to process}
                            {--max-completeness=90 : Maximum completeness percentage to process}
                            {--exclude= : Comma-separated list of domains to exclude}
                            {--include= : Comma-separated list of domains to include (overrides exclude)}
                            {--dry-run : Show what would be completed without making changes}
                            {--batch : Run in batch mode (no interactive prompts)}';

    /**
     * @var string
     */
    protected $description = 'Complete partially implemented domains by adding missing components in bulk';

    private readonly DomainAnalysisService $analysisService;

    public function __construct()
    {
        parent::__construct();
        $this->analysisService = new DomainAnalysisService(base_path('modules'));
    }

    public function handle(): int
    {
        $specificComponent = $this->option('component');
        $minCompleteness = (int) $this->option('min-completeness');
        $maxCompleteness = (int) $this->option('max-completeness');
        $excludeDomains = $this->parseList($this->option('exclude'));
        $includeDomains = $this->parseList($this->option('include'));
        $isDryRun = $this->option('dry-run');
        $isBatch = $this->option('batch');

        $this->displayHeader();

        if ($specificComponent) {
            return $this->completeSpecificComponent($specificComponent, $excludeDomains, $includeDomains, $isDryRun, $isBatch);
        }

        return $this->completePartialDomains($minCompleteness, $maxCompleteness, $excludeDomains, $includeDomains, $isDryRun, $isBatch);
    }

    /**
     * @param string $component
     * @param array<string> $excludeDomains
     * @param array<string> $includeDomains
     * @param bool $isDryRun
     * @param bool $isBatch
     */
    private function completeSpecificComponent(string $component, array $excludeDomains, array $includeDomains, bool $isDryRun, bool $isBatch): int
    {
        $domainsWithoutComponent = $this->analysisService->getDomainsWithoutComponent($component);

        if ($domainsWithoutComponent === []) {
            $this->info(" All domains already have the '{$component}' component");

            return self::SUCCESS;
        }

        $filteredDomains = $this->filterDomains($domainsWithoutComponent, $excludeDomains, $includeDomains);

        if ($filteredDomains === []) {
            $this->info('ℹ️  No domains match the filtering criteria');

            return self::SUCCESS;
        }

        $this->displayComponentCompletionPlan($component, $filteredDomains);

        if (! $isBatch && ! $isDryRun && ! $this->confirm('Proceed with completing this component across all selected domains?')) {
            $this->info('Operation cancelled');

            return self::SUCCESS;
        }

        return $this->executeComponentCompletion($component, $filteredDomains, $isDryRun);
    }

    /**
     * @param int $minCompleteness
     * @param int $maxCompleteness
     * @param array<string> $excludeDomains
     * @param array<string> $includeDomains
     * @param bool $isDryRun
     * @param bool $isBatch
     */
    private function completePartialDomains(int $minCompleteness, int $maxCompleteness, array $excludeDomains, array $includeDomains, bool $isDryRun, bool $isBatch): int
    {
        $allDomains = $this->analysisService->analyzeAllDomains();
        $partialDomains = [];

        foreach ($allDomains as $domain => $analysis) {
            if (! $analysis['exists']) {
                continue;
            }

            $completeness = $analysis['completeness'];

            if ($completeness >= $minCompleteness && $completeness <= $maxCompleteness) {
                $partialDomains[$domain] = $analysis;
            }
        }

        if ($partialDomains === []) {
            $this->info(" No domains found in the {$minCompleteness}%-{$maxCompleteness}% completeness range");

            return self::SUCCESS;
        }

        $filteredDomains = $this->filterDomains($partialDomains, $excludeDomains, $includeDomains);

        if ($filteredDomains === []) {
            $this->info('ℹ️  No domains match the filtering criteria');

            return self::SUCCESS;
        }

        $this->displayDomainCompletionPlan($filteredDomains, $minCompleteness, $maxCompleteness);

        if (! $isBatch && ! $isDryRun && ! $this->confirm('Proceed with completing all selected domains?')) {
            $this->info('Operation cancelled');

            return self::SUCCESS;
        }

        return $this->executeDomainCompletion($filteredDomains, $isDryRun);
    }

    /**
     * @param array<string, mixed> $domains
     * @param array<string> $excludeDomains
     * @param array<string> $includeDomains
     * @return array<string, mixed>
     */
    private function filterDomains(array $domains, array $excludeDomains, array $includeDomains): array
    {
        if ($includeDomains !== []) {
            return array_intersect_key($domains, array_flip($includeDomains));
        }

        if ($excludeDomains !== []) {
            return array_diff_key($domains, array_flip($excludeDomains));
        }

        return $domains;
    }

    private function displayHeader(): void
    {
        $this->line('╔══════════════════════════════════════════════════════════╗');
        $this->line('║               Bulk Domain Completion Tool              ║');
        $this->line('║                   Go²Digital DevTools                    ║');
        $this->line('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    /**
     * @param  array<string, mixed>  $domains
     */
    private function displayComponentCompletionPlan(string $component, array $domains): void
    {
        $humanName = $this->humanizeComponentName($component);
        $this->info(" Plan: Add '{$humanName}' to " . count($domains) . ' domains');
        $this->newLine();

        $headers = ['Domain', 'Current Completeness', 'Models', 'Expected Impact'];
        $rows = [];

        foreach ($domains as $domain => $analysis) {
            $rows[] = [
                $domain,
                $analysis['completeness'] . '%',
                count($analysis['models']) > 0 ? implode(', ', $analysis['models']) : 'None',
                $this->calculateComponentImpact($analysis),
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
    }

    /**
     * @param  array<string, mixed>  $domains
     */
    private function displayDomainCompletionPlan(array $domains, int $minCompleteness, int $maxCompleteness): void
    {
        $this->info(' Plan: Complete ' . count($domains) . " domains ({$minCompleteness}%-{$maxCompleteness}% range)");
        $this->newLine();

        $headers = ['Domain', 'Current %', 'Missing Components', 'Priority Actions'];
        $rows = [];

        foreach ($domains as $domain => $analysis) {
            $priorityActions = $this->getPriorityActions($analysis);
            $rows[] = [
                $domain,
                $analysis['completeness'] . '%',
                count($analysis['missing']),
                implode(', ', array_slice($priorityActions, 0, 2)),
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
    }

    /**
     * @param  array<string, mixed>  $domains
     */
    private function executeComponentCompletion(string $component, array $domains, bool $isDryRun): int
    {
        $this->info(" Executing bulk completion for component: {$component}");
        $this->newLine();

        $success = 0;
        $total = count($domains);

        foreach (array_keys($domains) as $domain) {
            $this->line("Processing {$domain}...");

            $result = $this->callRetrofitCommand($domain, $component, $isDryRun);

            if ($result === 0) {
                $success++;
                $this->info("   Completed {$domain}");
            } else {
                $this->error("   Failed {$domain}");
            }
        }

        $this->newLine();
        $this->info(" Results: {$success}/{$total} domains completed successfully");

        return $success > 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $domains
     */
    private function executeDomainCompletion(array $domains, bool $isDryRun): int
    {
        $this->info(' Executing bulk domain completion');
        $this->newLine();

        $success = 0;
        $total = count($domains);

        foreach (array_keys($domains) as $domain) {
            $this->line("Processing {$domain}...");

            $result = $this->callRetrofitCommand($domain, null, $isDryRun, true);

            if ($result === 0) {
                $success++;
                $this->info("   Completed {$domain}");
            } else {
                $this->error("   Failed {$domain}");
            }
        }

        $this->newLine();
        $this->info(" Results: {$success}/{$total} domains completed successfully");

        return $success > 0 ? self::SUCCESS : self::FAILURE;
    }

    private function callRetrofitCommand(string $domain, ?string $component, bool $isDryRun, bool $addAll = false): int
    {
        $arguments = ['domain' => $domain];
        $options = [];

        if ($component) {
            $options['--component'] = $component;
        }

        if ($addAll) {
            $options['--all'] = true;
        }

        if ($isDryRun) {
            $options['--dry-run'] = true;
        }

        $options['--force'] = true; // Force overwrite in batch mode

        return $this->call('hex:retrofit-domain', $arguments);
    }

    /**
     * @param  array<string, mixed>  $analysis
     */
    private function calculateComponentImpact(array $analysis): string
    {
        $currentCompleteness = $analysis['completeness'];
        $totalComponents = count($analysis['components']);

        // Each component is worth approximately 100/totalComponents percent
        $componentValue = round(100 / $totalComponents);
        $expectedCompleteness = min(100, $currentCompleteness + $componentValue);

        return "+{$componentValue}% → {$expectedCompleteness}%";
    }

    /**
     * @param array<string, mixed> $analysis
     * @return array<string>
     */
    private function getPriorityActions(array $analysis): array
    {
        $actions = [];
        $components = $analysis['components'];
        $hasModels = ! empty($analysis['models']);

        // Priority order based on importance
        if ($hasModels && ! $components['api_resource']) {
            $actions[] = 'API Resource';
        }

        if ($hasModels && ! $components['laravel_repository']) {
            $actions[] = 'Repository';
        }

        if ($hasModels && ! $components['api_processors']) {
            $actions[] = 'API Processors';
        }

        if ($hasModels && ! $components['app_commands']) {
            $actions[] = 'CQRS Commands';
        }

        if ($hasModels && ! $components['laravel_migration']) {
            $actions[] = 'Migration';
        }

        if ($actions === []) {
            $actions[] = 'Complete Structure';
        }

        return $actions;
    }

    /**
     * @param string|null $list
     * @return array<string>
     */
    private function parseList(?string $list): array
    {
        if ($list === null || $list === '' || $list === '0') {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $list)), fn (string $item): bool => $item !== '');
    }

    private function humanizeComponentName(string $component): string
    {
        $map = [
            'domain_model' => 'Domain Model',
            'domain_repository' => 'Repository Interface',
            'app_commands' => 'Application Commands',
            'app_events' => 'Domain Events',
            'app_queries' => 'Query Patterns',
            'api_resource' => 'API Platform Resource',
            'api_processors' => 'API Processors',
            'api_providers' => 'API Providers',
            'laravel_repository' => 'Repository Implementation',
            'laravel_factory' => 'Model Factory',
            'laravel_migration' => 'Database Migration',
            'laravel_seeder' => 'Database Seeder',
            'laravel_form_request' => 'Form Request Validation',
        ];

        return $map[$component] ?? ucwords(str_replace('_', ' ', $component));
    }
}
