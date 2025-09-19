<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Console\Commands;

use Exception;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

use Modules\Organization\Domain\Model\Organization;
use Modules\Shared\Application\Command\SuperSeedCommand;
use Modules\Shared\Application\Command\SuperSeedCommandHandler;

final class SuperSeederCommand extends Command
{
    protected $signature = 'go2digital:super-seed 
                            {--interactive : Run in interactive mode}
                            {--model= : Model to seed (User|Campaign|Donation|All)}
                            {--count= : Number of records to create}
                            {--batch-size= : Batch size for processing}
                            {--organization= : Organization ID for tenant-specific seeding}
                            {--auto-index : Auto-index seeded records with Scout/Meilisearch}
                            {--dry-run : Show configuration without executing}';

    protected $description = 'Go2Digital BV Super Seeder - Enterprise-scale data seeding with performance optimization for 20K+ employees';

    /** @var list<string> */
    private array $availableModels = ['User', 'Campaign', 'Donation', 'All'];

    /** @var array<string, mixed> */
    private array $countPresets = [
        '1K' => 1000,
        '10K' => 10000,
        '100K' => 100000,
        '1M' => 1000000,
        'Custom' => 0,
    ];

    /** @var array<string, mixed> */
    private array $batchSizeOptions = [
        '1K' => 1000,
        '5K' => 5000,
        '10K' => 10000,
        'Custom' => 0,
    ];

    public function __construct(
        private readonly SuperSeedCommandHandler $handler
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->displayHeader();

        $parameters = $this->option('interactive') || ! $this->option('model')
            ? $this->runInteractiveMode()
            : $this->parseCommandLineOptions();

        if (! $parameters) {
            return Command::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->displayExecutionPlan($parameters);
            $this->info('Dry run complete. No data was generated.');

            return Command::SUCCESS;
        }

        if ($parameters['count'] > 1000000 && ! $this->confirmLargeScale($parameters['count'])) {
            $this->info('Operation cancelled by user.');

            return Command::SUCCESS;
        }

        return $this->executeSeedCommand($parameters);
    }

    private function displayHeader(): void
    {
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════════════════╗');
        $this->info('║                  Go2Digital BV Super Seeder                          ║');
        $this->info('╚══════════════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    /**
     * @return array<string, mixed>
     */
    private function runInteractiveMode(): ?array
    {
        $availableModels = array_diff($this->availableModels, ['All']);
        $selectedModels = multiselect(
            label: 'Select models to seed:',
            options: array_combine($availableModels, $availableModels),
            default: ['User'],
            required: 'You must select at least one model.',
            hint: 'Use space to select multiple options and Enter to confirm.'
        );

        if ($selectedModels === []) {
            $this->error('At least one model must be selected.');

            return null;
        }

        $model = count($selectedModels) === 1 ? $selectedModels[0] : 'All';

        $countOption = select(
            label: 'Select number of records:',
            options: array_map(fn ($key, $value): string => $value === 0 ? 'Custom' : "{$key} ({$value} records)", array_keys($this->countPresets), $this->countPresets),
            default: '1K (1000 records)'
        );

        if (str_contains((string) $countOption, 'Custom')) {
            $count = 0;
        } else {
            preg_match('/\((\d+) records\)/', (string) $countOption, $matches);
            $count = (int) ($matches[1] ?? 1000);
        }
        if ($count === 0) {
            $count = (int) $this->ask('Enter custom count:', '10000');
            if ($count <= 0) {
                $this->error('Count must be greater than 0');

                return null;
            }
        }

        $batchSizeOption = select(
            label: 'Select batch size:',
            options: array_map(fn ($key, $value): string => $value === 0 ? 'Custom' : "{$key} ({$value} per batch)", array_keys($this->batchSizeOptions), $this->batchSizeOptions),
            default: '1K (1000 per batch)'
        );

        if (str_contains((string) $batchSizeOption, 'Custom')) {
            $batchSize = 0;
        } else {
            preg_match('/\((\d+) per batch\)/', (string) $batchSizeOption, $matches);
            $batchSize = (int) ($matches[1] ?? 1000);
        }
        if ($batchSize === 0) {
            $batchSize = (int) $this->ask('Enter custom batch size:', '1000');
            if ($batchSize <= 0 || $batchSize > $count) {
                $this->error('Batch size must be between 1 and ' . $count);

                return null;
            }
        }

        $selectedOrganizations = $this->selectOrganization();

        $autoIndexOption = select(
            label: 'Auto-index seeded records with Scout/Meilisearch?',
            options: ['no' => 'No', 'yes' => 'Yes'],
            default: 'no'
        );
        $autoIndex = $autoIndexOption === 'yes';

        return [
            'model' => $model,
            'selectedModels' => $selectedModels,
            'count' => $count,
            'batchSize' => $batchSize,
            'selectedOrganizations' => $selectedOrganizations,
            'autoIndex' => $autoIndex,
        ];
    }

    /**
     * @return list<string>
     */
    private function selectOrganization(): array
    {
        $organizations = Organization::select('id', 'name')->get();

        $options = ['central' => 'Central DB (Hybrid Setup)'];

        foreach ($organizations as $organization) {
            $name = is_array($organization->name)
                ? ($organization->name['en'] ?? $organization->name['nl'] ?? 'Unnamed')
                : (string) $organization->name;

            $options[$organization->id] = "#{$organization->id} - {$name}";
        }

        if (count($options) === 1) {
            $this->warn('No organizations found. Using Central DB.');

            return ['central'];
        }

        $selectedOptions = multiselect(
            label: 'Select target databases:',
            options: $options,
            default: ['central'],
            required: 'You must select at least one target.',
            hint: 'Use space to select multiple targets and Enter to confirm.'
        );

        return array_values(array_map('strval', $selectedOptions));
    }

    /**
     * @return array<string, mixed>
     */
    private function parseCommandLineOptions(): ?array
    {
        $model = $this->option('model');
        $count = $this->option('count');
        $batchSize = $this->option('batch-size');
        $organizationId = $this->option('organization');
        $autoIndex = $this->option('auto-index');

        if (! $model || ! in_array($model, $this->availableModels, true)) {
            $this->error('Valid model is required. Available: ' . implode(', ', $this->availableModels));

            return null;
        }

        if (! $count || ! is_numeric($count) || (int) $count <= 0) {
            $this->error('Valid count is required (positive integer)');

            return null;
        }

        $count = (int) $count;
        $batchSize = $batchSize ? (int) $batchSize : 1000;
        $organizationId = $organizationId ? (int) $organizationId : null;

        if ($batchSize <= 0 || $batchSize > $count) {
            $this->error('Batch size must be between 1 and ' . $count);

            return null;
        }

        if ($organizationId && ! Organization::find($organizationId)) {
            $this->error("Organization with ID {$organizationId} not found");

            return null;
        }

        return [
            'model' => $model,
            'count' => $count,
            'batchSize' => $batchSize,
            'selectedOrganizations' => $organizationId ? [$organizationId] : ['central'],
            'autoIndex' => $autoIndex,
        ];
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function executeSeedCommand(array $parameters): int
    {
        $this->displayExecutionPlan($parameters);

        $proceed = confirm(
            label: 'Proceed with seeding?',
            default: true,
            yes: 'Yes, start seeding',
            no: 'No, cancel',
            hint: 'Press space to toggle, Enter to confirm'
        );

        if (! $proceed) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        $this->info('Starting data seeding with enterprise performance optimizations...');
        $this->newLine();

        $selectedOrganizations = $parameters['selectedOrganizations'] ?? ['central'];
        $allResults = [];

        foreach ($selectedOrganizations as $orgId) {
            $targetOrg = $orgId === 'central' ? null : (int) $orgId;
            $orgName = $orgId === 'central' ? 'Central DB (Hybrid)' : "Organization #{$orgId}";

            $this->info("Seeding {$orgName}...");

            $command = new SuperSeedCommand(
                model: $parameters['model'],
                count: $parameters['count'],
                batchSize: $parameters['batchSize'],
                organizationId: $targetOrg,
                autoIndex: $parameters['autoIndex'] ?? false
            );

            try {
                $result = $this->handler->handle($command);
                $result['target'] = $orgName;
                $allResults[] = $result;
                $this->info("Completed {$orgName}");
            } catch (Exception $e) {
                $this->error("Failed {$orgName}: " . $e->getMessage());

                return Command::FAILURE;
            }

            $this->newLine();
        }

        $this->displayMultiResults($allResults);

        return Command::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function displayExecutionPlan(array $parameters): void
    {
        $selectedOrganizations = $parameters['selectedOrganizations'] ?? ['central'];
        $selectedModels = $parameters['selectedModels'] ?? [$parameters['model']];

        $this->info('Execution Plan:');
        $this->info('┌─────────────────────────────────────────────────────────────────────┐');
        $this->info('│ Model(s): ' . str_pad(implode(', ', $selectedModels), 57) . '│');
        $this->info('│ Count: ' . str_pad(number_format($parameters['count']), 59) . '│');
        $this->info('│ Batch Size: ' . str_pad(number_format($parameters['batchSize']), 55) . '│');

        $targetNames = [];
        foreach ($selectedOrganizations as $orgId) {
            $targetNames[] = $orgId === 'central' ? 'Central DB' : "Org #{$orgId}";
        }
        $targetsText = implode(', ', $targetNames);
        $this->info('│ Targets: ' . str_pad($targetsText, 58) . '│');

        $estimatedBatches = ceil($parameters['count'] / $parameters['batchSize']);
        $totalOperations = $estimatedBatches * count($selectedOrganizations) * count($selectedModels);
        $this->info('│ Est. Batches: ' . str_pad(number_format($estimatedBatches), 52) . '│');
        $this->info('│ Total Operations: ' . str_pad(number_format($totalOperations), 48) . '│');

        $estimatedDuration = $this->estimateDuration($parameters['model'], $parameters['count']);
        $this->info('│ Est. Duration: ' . str_pad($estimatedDuration, 51) . '│');
        $this->info('│ Memory Limit: ' . str_pad('512MB', 52) . '│');
        $this->info('│ Optimizations: ' . str_pad('Raw SQL, Batch Processing, Memory Cleanup', 50) . '│');
        $this->info('└─────────────────────────────────────────────────────────────────────┘');
        $this->newLine();
    }

    private function confirmLargeScale(int $count): bool
    {
        $this->warn('⚠️  Large-scale operation: ' . number_format($count) . ' records');
        $this->warn('This operation will use significant system resources.');

        return $this->confirm(
            'Are you sure you want to proceed?',
            false
        );
    }

    /** @param list<array<string, mixed>> $allResults */
    private function displayMultiResults(array $allResults): void
    {
        $this->newLine();
        $this->info('╔═══════════════════════════════════════════════════════════════════════╗');
        $this->info('║                   Go2Digital BV Super Seeder Results                  ║');
        $this->info('╚═══════════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $totalRecords = 0;
        $totalDuration = 0;

        foreach ($allResults as $result) {
            $duration = $result['duration'] ?? 0;
            $throughput = $result['throughput'] ?? 0;

            $this->info("Target: {$result['target']}");
            $this->info('  Created: ' . number_format($result['total_created']) . " {$result['model']} records");
            $this->info("  Duration: {$duration}s | Throughput: " . number_format($throughput) . ' records/sec');
            if (isset($result['auto_indexed'])) {
                $indexStatus = $result['auto_indexed'] ? 'Queued for indexing' : 'Indexing failed';
                $this->info("  Search Index: {$indexStatus}");
            }
            $this->newLine();

            $totalRecords += $result['total_created'];
            $totalDuration += $duration;
        }

        $overallThroughput = $totalDuration > 0 ? round($totalRecords / $totalDuration) : 0;
        $this->info('Overall Summary:');
        $this->info('  Total Records: ' . number_format($totalRecords));
        $this->info('  Targets: ' . count($allResults));
        $this->info('  Combined Throughput: ' . number_format($overallThroughput) . ' records/sec');

        $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024;
        $this->info('  Peak Memory: ' . number_format($memoryUsage, 2) . ' MB');
    }

    private function estimateDuration(string $model, int $count): string
    {
        $recordsPerSecond = match ($model) {
            'User' => 15000,
            'Campaign' => 20000,
            'Donation' => 25000,
            'All' => 20000,
            default => 20000
        };

        $estimatedSeconds = (int) ($count / $recordsPerSecond);

        if ($estimatedSeconds < 60) {
            return $estimatedSeconds . 's';
        }

        if ($estimatedSeconds < 3600) {
            return (int) ($estimatedSeconds / 60) . 'm ' . ($estimatedSeconds % 60) . 's';
        }

        $hours = (int) ($estimatedSeconds / 3600);
        $minutes = (int) (($estimatedSeconds % 3600) / 60);

        return "{$hours}h {$minutes}m";
    }

    public function displayUsage(): void
    {
        $this->info('Usage Examples:');
        $this->info('');
        $this->info('# Interactive mode (recommended)');
        $this->info('php artisan go2digital:super-seed --interactive');
        $this->info('');
        $this->info('# Generate 20,000 users for enterprise testing');
        $this->info('php artisan go2digital:super-seed --model=User --count=20000');
        $this->info('');
        $this->info('# Generate 1 million donation transactions');
        $this->info('php artisan go2digital:super-seed --model=Donation --count=1000000');
        $this->info('');
        $this->info('# Generate comprehensive dataset with custom batch sizes');
        $this->info('php artisan go2digital:super-seed --model=All --count=500000 --batch-size=5000');
        $this->info('');
        $this->info('# Dry run to see configuration');
        $this->info('php artisan go2digital:super-seed --model=User --count=10000 --dry-run');
        $this->info('');
        $this->info('# Seed specific organization with auto-indexing');
        $this->info('php artisan go2digital:super-seed --model=Campaign --count=5000 --organization=3 --auto-index');
    }
}
