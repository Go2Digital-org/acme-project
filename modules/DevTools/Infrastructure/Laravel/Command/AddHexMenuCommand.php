<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;

use function Laravel\Prompts\select;

class AddHexMenuCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'hex:menu';

    /**
     * @var string
     */
    protected $description = 'Interactive menu for hexagonal architecture generators';

    public function handle(): int
    {
        $this->displayHeader();

        while (true) {
            $selectedCommand = $this->showMenu();

            if ($selectedCommand === 'exit') {
                $this->info('👋 Goodbye!');

                return 0;
            }

            $this->executeCommand($selectedCommand);

            $this->line('');
            $this->info(' Command completed! Returning to menu...');
            $this->line('');
        }
    }

    private function displayHeader(): void
    {
        $this->line('');
        $this->line('╔══════════════════════════════════════════════════════════╗');
        $this->line('║                Hexagonal Architecture Generator          ║');
        $this->line('║                     Go²Digital DevTools                   ║');
        $this->line('╚══════════════════════════════════════════════════════════╝');
        $this->line('');
    }

    private function showMenu(): string
    {
        $menuOptions = [
            // Smart Analysis & Retrofitting
            '━━━ 🧠 SMART ANALYSIS ━━━' => null,
            'hex:analyze-domains' => '  Analyze Domain Completeness',
            'hex:retrofit-domain' => '  Retrofit Missing Components',
            'hex:complete-partial-domains' => '  Bulk Complete Domains',

            // Domain Creation
            '━━━   DOMAIN CREATION ━━━' => null,
            'hex:create-domain' => '  Create Complete Domain (27 files)',
            'hex:add:structure' => '  Create Empty Directory Structure',

            // CQRS Components
            '━━━  CQRS COMPONENTS ━━━' => null,
            'add:hex:command' => '   Add Commands & Handlers',
            'add:hex:event' => '  Add Events & Handlers',
            'add:hex:find-query' => '  Add Queries & Handlers',

            // Data Layer
            '━━━ 💾 DATA LAYER ━━━' => null,
            'hex:add:model' => '   Add Domain Model',
            'hex:add:repository' => '  Add Repository Interface',
            'hex:add:repository-eloquent' => '🗄️   Add Repository Implementation',
            'hex:add:migration' => '  Add Database Migration',

            // API Layer
            '━━━  API LAYER ━━━' => null,
            'hex:add:resource' => '🔗  Add API Resource',
            'hex:add:processor' => '  Add API Processors',
            'hex:add:provider' => '📤  Add API Providers',

            // Infrastructure
            '━━━  INFRASTRUCTURE ━━━' => null,
            'hex:add:form-request' => '  Add Form Requests',
            'hex:add:service-provider' => '   Add Service Provider',
            'hex:add:factory' => '🏭  Add Model Factory',
            'hex:add:seeder' => '🌱  Add Database Seeder',

            // Exit
            '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' => null,
            'exit' => '  Exit Menu',
        ];

        // Filter out separator lines for the actual selection
        $selectableOptions = array_filter($menuOptions, fn ($value, $key): bool => $value !== null, ARRAY_FILTER_USE_BOTH);

        $result = select(
            label: ' Select a command to execute:',
            options: $selectableOptions,
            scroll: 15,
        );

        return (string) $result;
    }

    private function executeCommand(string $command): void
    {
        $this->line('');

        switch ($command) {
            case 'hex:analyze-domains':
                $this->info(' Analyzing domain completeness...');
                $this->call($command);
                break;

            case 'hex:retrofit-domain':
                $this->info(' Retrofitting missing components...');
                $domain = $this->ask(' Enter the domain name to retrofit');

                if ($domain) {
                    $this->call($command, ['domain' => $domain]);
                }
                break;

            case 'hex:complete-partial-domains':
                $this->info(' Bulk completing partial domains...');
                $this->call($command);
                break;

            case 'hex:create-domain':
                $this->info(' Creating complete domain structure...');
                $domain = $this->ask(' Enter the domain name');

                if ($domain) {
                    $this->call($command, ['domain' => $domain]);
                }
                break;

            case 'hex:add:structure':
                $this->info(' Creating empty directory structure...');
                $domain = $this->ask(' Enter the domain name');

                if ($domain) {
                    $this->call($command, ['domain' => $domain]);
                }
                break;

            case 'add:hex:command':
                $this->info(' Adding CQRS commands and handlers...');
                $this->call($command);
                break;

            case 'add:hex:event':
                $this->info(' Adding domain events and handlers...');
                $this->call($command);
                break;

            case 'add:hex:find-query':
                $this->info(' Adding queries and handlers...');
                $this->call($command);
                break;

            case 'hex:add:model':
                $this->info(' Adding domain model...');
                $this->call($command);
                break;

            case 'hex:add:repository':
                $this->info(' Adding repository interface...');
                $this->call($command);
                break;

            case 'hex:add:repository-eloquent':
                $this->info('🗄️ Adding repository implementation...');
                $this->call($command);
                break;

            case 'hex:add:migration':
                $this->info(' Adding database migration...');
                $this->call($command);
                break;

            case 'hex:add:resource':
                $this->info('🔗 Adding API resource...');
                $this->call($command);
                break;

            case 'hex:add:processor':
                $this->info(' Adding API processors...');
                $this->call($command);
                break;

            case 'hex:add:provider':
                $this->info('📤 Adding API providers...');
                $this->call($command);
                break;

            case 'hex:add:form-request':
                $this->info(' Adding form requests...');
                $this->call($command);
                break;

            case 'hex:add:service-provider':
                $this->info(' Adding service provider...');
                $this->call($command);
                break;

            case 'hex:add:factory':
                $this->info('🏭 Adding model factory...');
                $this->call($command);
                break;

            case 'hex:add:seeder':
                $this->info('🌱 Adding database seeder...');
                $this->call($command);
                break;

            default:
                $this->error("Unknown command: {$command}");
                break;
        }
    }
}
