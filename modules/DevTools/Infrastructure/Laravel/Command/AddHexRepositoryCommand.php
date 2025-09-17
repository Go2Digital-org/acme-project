<?php

declare(strict_types=1);

namespace Modules\DevTools\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Modules\DevTools\Infrastructure\Laravel\Command\Traits\HexGeneratorTrait;

class AddHexRepositoryCommand extends Command
{
    use HexGeneratorTrait;

    /**
     * @var string
     */
    protected $signature = 'hex:add:repository {domain?} {name?} {--force : Force overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Add a Repository Interface to a specific domain';

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
            'Repository',
            'RepositoryInterface.stub',
            [],
            $this->shouldForceOverwrite(),
        );

        if ($success) {
            $this->displaySummary($domain, $name, 'Repository Interface');
            $this->promptForRelatedFiles($domain, $name, 'Repository');
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
