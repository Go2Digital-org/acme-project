<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Modules\DevTools\Infrastructure\Laravel\Command\Traits\HexGeneratorTrait;

class AddHexEloquentRepositoryCommand extends Command
{
    use HexGeneratorTrait;

    /**
     * @var string
     */
    protected $signature = 'hex:add:repository-eloquent {domain?} {name?} {--force : Force overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Add an Eloquent Repository to a specific domain';

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

        $name = $this->getRepositoryName($domain);

        if ($name === '' || $name === '0') {
            $this->error('Repository name not provided.');

            return 1;
        }

        if (! $this->validateClassName($name)) {
            $this->error('Invalid class name format. Use PascalCase.');

            return 1;
        }

        $success = $this->generateFile(
            $domain,
            $name,
            'RepositoryEloquent',
            'EloquentRepository.stub',
            [],
            $this->shouldForceOverwrite(),
        );

        if ($success) {
            $this->displaySummary($domain, $name, 'Eloquent Repository');
            $this->info('');
            $this->info('Don\'t forget to:');
            $this->line('1. Bind the repository interface to this implementation in your service provider');
            $this->line('2. Import and use the repository interface in your handlers');
        }

        return $success ? 0 : 1;
    }

    /**
     * Get or prompt for repository name.
     */
    protected function getRepositoryName(string $domain): string
    {
        $nameArgument = $this->argument('name');

        if ($nameArgument !== null && $nameArgument !== '') {
            return $nameArgument;
        }

        return $this->ask('Enter the repository name (without "Repository" suffix)', $domain) ?? '';
    }
}
