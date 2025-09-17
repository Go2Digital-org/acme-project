<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Modules\DevTools\Infrastructure\Laravel\Command\Traits\HexGeneratorTrait;

class AddHexServiceProviderCommand extends Command
{
    use HexGeneratorTrait;

    /**
     * @var string
     */
    protected $signature = 'hex:add:service-provider {domain?} {name?} {--force : Force overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Add a Service Provider to a specific domain';

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

        $success = $this->generateFile(
            $domain,
            $domain,
            'ServiceProvider',
            'ServiceProvider.stub',
            [],
            $this->shouldForceOverwrite(),
        );

        if ($success) {
            $this->displaySummary($domain, "{$domain}ServiceProvider", 'Service Provider');
            $this->displayServiceProviderInstructions($domain);
        }

        return $success ? 0 : 1;
    }

    /**
     * Display instructions for using the service provider.
     */
    protected function displayServiceProviderInstructions(string $domain): void
    {
        $this->info('');
        $this->info('Service Provider created successfully!');
        $this->info('');
        $this->info('Next steps:');
        $this->line('1. Register the service provider in bootstrap/providers.php:');
        $this->line("   Modules\\{$domain}\\Infrastructure\\Laravel\\Provider\\{$domain}ServiceProvider::class,");
        $this->info('');
        $this->line('2. Add repository bindings in the register() method:');
        $this->line('   $this->app->bind(');
        $this->line("       {$domain}RepositoryInterface::class,");
        $this->line("       {$domain}EloquentRepository::class");
        $this->line('   );');
        $this->info('');
        $this->line('3. Load routes, migrations, and views in the boot() method as needed');
        $this->info('');
        $this->line('4. Consider registering console commands if your domain has any');
    }
}
