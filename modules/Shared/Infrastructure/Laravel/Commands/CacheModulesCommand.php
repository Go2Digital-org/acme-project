<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Commands;

use Illuminate\Console\Command;
use Modules\Shared\Application\Command\CacheModuleManifestCommand;
use Modules\Shared\Application\Command\CacheModuleManifestCommandHandler;

final class CacheModulesCommand extends Command
{
    protected $signature = 'app:cache-modules {--force : Force regeneration of cache}';

    protected $description = 'Cache the module discovery manifest for improved performance';

    public function __construct(
        private readonly CacheModuleManifestCommandHandler $handler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Caching module manifest...');

        $command = new CacheModuleManifestCommand(
            force: $this->option('force'),
        );

        $this->handler->handle($command);

        $this->info('Module manifest cached successfully!');

        return self::SUCCESS;
    }
}
