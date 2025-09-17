<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Commands;

use Illuminate\Console\Command;
use Modules\Shared\Application\Command\ModuleManifestCacheInterface;

final class ClearModulesCommand extends Command
{
    protected $signature = 'app:clear-modules';

    protected $description = 'Clear the cached module discovery manifest';

    public function __construct(
        private readonly ModuleManifestCacheInterface $cache,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Clearing module manifest cache...');

        $this->cache->clear();

        $this->info('Module manifest cache cleared successfully!');

        return self::SUCCESS;
    }
}
