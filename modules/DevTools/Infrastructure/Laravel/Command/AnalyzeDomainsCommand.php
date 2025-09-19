<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Modules\DevTools\Domain\Service\DomainAnalysisService;

class AnalyzeDomainsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'hex:analyze-domains 
                            {domain? : Specific domain to analyze}
                            {--missing= : Show domains missing specific component (api_resource, api_processors, etc.)}
                            {--partial : Show only partially complete domains}
                            {--complete : Show only complete domains}
                            {--incomplete : Show only incomplete domains}
                            {--json : Output as JSON}';

    /**
     * @var string
     */
    protected $description = 'Analyze hexagonal architecture completeness across all domains';

    private readonly DomainAnalysisService $analysisService;

    public function __construct()
    {
        parent::__construct();
        $this->analysisService = new DomainAnalysisService(base_path('modules'));
    }

    public function handle(): int
    {
        $specificDomain = $this->argument('domain');
        $missingComponent = $this->option('missing');
        $showPartial = $this->option('partial');
        $showComplete = $this->option('complete');
        $showIncomplete = $this->option('incomplete');
        $outputJson = $this->option('json');

        if ($specificDomain) {
            return $this->analyzeSingleDomain((string) $specificDomain, (bool) $outputJson);
        }

        if ($missingComponent) {
            return $this->showDomainsWithoutComponent((string) $missingComponent, (bool) $outputJson);
        }

        if ($showPartial) {
            return $this->showPartialDomains((bool) $outputJson);
        }

        if ($showComplete) {
            return $this->showDomainsByCompleteness(90, 100, (bool) $outputJson);
        }

        if ($showIncomplete) {
            return $this->showDomainsByCompleteness(0, 50, (bool) $outputJson);
        }

        return $this->analyzeAllDomains((bool) $outputJson);
    }

    private function analyzeSingleDomain(string $domain, bool $outputJson): int
    {
        $analysis = $this->analysisService->analyzeDomain($domain);

        if ($outputJson) {
            $jsonOutput = json_encode([$domain => $analysis], JSON_PRETTY_PRINT);

            if ($jsonOutput === false) {
                $this->error('Failed to encode JSON output');

                return self::FAILURE;
            }
            $this->line($jsonOutput);

            return self::SUCCESS;
        }

        if (! $analysis['exists']) {
            $this->error(" Domain '{$domain}' does not exist");

            return self::FAILURE;
        }

        $this->displayDomainHeader($domain, $analysis['completeness']);
        $this->displayComponentsTable([$domain => $analysis]);
        $this->displayRecommendations($domain, $analysis);

        return self::SUCCESS;
    }

    private function showDomainsWithoutComponent(string $component, bool $outputJson): int
    {
        $domainsWithoutComponent = $this->analysisService->getDomainsWithoutComponent($component);

        if ($outputJson) {
            $jsonOutput = json_encode($domainsWithoutComponent, JSON_PRETTY_PRINT);

            if ($jsonOutput === false) {
                $this->error('Failed to encode JSON output');

                return self::FAILURE;
            }
            $this->line($jsonOutput);

            return self::SUCCESS;
        }

        if ($domainsWithoutComponent === []) {
            $this->info(" All domains have the '{$component}' component");

            return self::SUCCESS;
        }

        $this->displayHeader('Domains Missing: ' . $this->humanizeComponentName($component));
        $this->displayComponentsTable($domainsWithoutComponent);

        return self::SUCCESS;
    }

    private function showPartialDomains(bool $outputJson): int
    {
        $partialDomains = $this->analysisService->getPartialDomains();

        if ($outputJson) {
            $jsonOutput = json_encode($partialDomains, JSON_PRETTY_PRINT);

            if ($jsonOutput === false) {
                $this->error('Failed to encode JSON output');

                return self::FAILURE;
            }
            $this->line($jsonOutput);

            return self::SUCCESS;
        }

        if ($partialDomains === []) {
            $this->info(' No partially complete domains found');

            return self::SUCCESS;
        }

        $this->displayHeader('Partially Complete Domains (10-90% complete)');
        $this->displayComponentsTable($partialDomains);

        return self::SUCCESS;
    }

    private function showDomainsByCompleteness(int $minPercent, int $maxPercent, bool $outputJson): int
    {
        $allDomains = $this->analysisService->analyzeAllDomains();
        $filteredDomains = [];

        foreach ($allDomains as $domain => $analysis) {
            if (! $analysis['exists']) {
                continue;
            }

            $completeness = $analysis['completeness'];

            if ($completeness >= $minPercent && $completeness <= $maxPercent) {
                $filteredDomains[$domain] = $analysis;
            }
        }

        if ($outputJson) {
            $jsonOutput = json_encode($filteredDomains, JSON_PRETTY_PRINT);

            if ($jsonOutput === false) {
                $this->error('Failed to encode JSON output');

                return self::FAILURE;
            }
            $this->line($jsonOutput);

            return self::SUCCESS;
        }

        if ($filteredDomains === []) {
            $this->info(" No domains found in the {$minPercent}%-{$maxPercent}% completeness range");

            return self::SUCCESS;
        }

        $this->displayHeader("Domains with {$minPercent}%-{$maxPercent}% Completeness");
        $this->displayComponentsTable($filteredDomains);

        return self::SUCCESS;
    }

    private function analyzeAllDomains(bool $outputJson): int
    {
        $allDomains = $this->analysisService->analyzeAllDomains();

        if ($outputJson) {
            $jsonOutput = json_encode($allDomains, JSON_PRETTY_PRINT);

            if ($jsonOutput === false) {
                $this->error('Failed to encode JSON output');

                return self::FAILURE;
            }
            $this->line($jsonOutput);

            return self::SUCCESS;
        }

        $this->displayHeader('Domain Architecture Analysis');
        $this->displayOverview($allDomains);
        $this->newLine();
        $this->displayComponentsTable($allDomains);

        return self::SUCCESS;
    }

    private function displayHeader(string $title): void
    {
        $this->line('╔══════════════════════════════════════════════════════════╗');
        $this->line('║ ' . str_pad($title, 57) . ' ║');
        $this->line('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    private function displayDomainHeader(string $domain, int $completeness): void
    {
        $status = $this->getCompletenessStatus($completeness);
        $this->line("  <info>{$domain}</info> Domain Analysis");
        $this->line(" Completeness: {$status} ({$completeness}%)");
        $this->newLine();
    }

    /**
     * @param  array<string, mixed>  $allDomains
     */
    private function displayOverview(array $allDomains): void
    {
        $total = count($allDomains);
        $existing = 0;
        $complete = 0;
        $partial = 0;
        $incomplete = 0;

        foreach ($allDomains as $analysis) {
            if (! $analysis['exists']) {
                continue;
            }

            $existing++;
            $completeness = $analysis['completeness'];

            if ($completeness >= 90) {
                $complete++;
            } elseif ($completeness >= 10) {
                $partial++;
            } else {
                $incomplete++;
            }
        }

        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total Modules', $total, '100%'],
                ['Existing Domains', $existing, $this->percentage($existing, $total)],
                ['Complete (90%+)', $complete, $this->percentage($complete, $existing)],
                ['Partial (10-89%)', $partial, $this->percentage($partial, $existing)],
                ['Incomplete (<10%)', $incomplete, $this->percentage($incomplete, $existing)],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $domainsAnalysis
     */
    private function displayComponentsTable(array $domainsAnalysis): void
    {
        $headers = [
            'Domain',
            'Complete%',
            'Models',
            'API Res',
            'API Proc',
            'Commands',
            'Events',
            'Queries',
            'Repository',
        ];

        $rows = [];

        foreach ($domainsAnalysis as $domain => $analysis) {
            if (! $analysis['exists']) {
                continue;
            }

            $components = $analysis['components'];
            $rows[] = [
                $domain,
                $this->getCompletenessStatus($analysis['completeness']) . ' ' . $analysis['completeness'] . '%',
                count($analysis['models']) > 0 ? ' ' . count($analysis['models']) : '',
                $components['api_resource'] ? '' : '',
                $components['api_processors'] ? '' : '',
                $components['app_commands'] ? '' : '',
                $components['app_events'] ? '' : '',
                $components['app_queries'] ? '' : '',
                $components['laravel_repository'] ? '' : '',
            ];
        }

        if ($rows !== []) {
            $this->table($headers, $rows);
            $this->displayDetailedRecommendations($domainsAnalysis);
        }
    }

    /**
     * @param  array<string, mixed>  $domainsAnalysis
     */
    private function displayDetailedRecommendations(array $domainsAnalysis): void
    {
        foreach ($domainsAnalysis as $domain => $analysis) {
            if (! $analysis['exists']) {
                continue;
            }

            if (empty($analysis['recommendations'])) {
                continue;
            }
            $this->displayRecommendations($domain, $analysis);
        }
    }

    /**
     * @param array<string, mixed> $analysis
     */
    private function displayRecommendations(string $domain, array $analysis): void
    {
        if (empty($analysis['recommendations'])) {
            return;
        }

        $this->newLine();
        $this->line("<info> Recommendations for {$domain}:</info>");

        foreach ($analysis['recommendations'] as $recommendation) {
            $this->line("   • {$recommendation}");
        }
    }

    private function getCompletenessStatus(int $completeness): string
    {
        if ($completeness >= 90) {
            return '<fg=green></fg=green>';
        }

        if ($completeness >= 70) {
            return '<fg=yellow></fg=yellow>';
        }

        if ($completeness >= 30) {
            return '<fg=yellow>🔶</fg=yellow>';
        }

        return '<fg=red></fg=red>';
    }

    private function percentage(int $value, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }

        return round(($value / $total) * 100) . '%';
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
