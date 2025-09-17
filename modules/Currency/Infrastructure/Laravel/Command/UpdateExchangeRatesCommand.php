<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Laravel\Command;

use Exception;
use Illuminate\Console\Command;
use Modules\Currency\Application\Command\UpdateExchangeRatesCommand as DomainCommand;
use Modules\Currency\Application\Command\UpdateExchangeRatesCommandHandler;
use Modules\Currency\Domain\Exception\ExchangeRateProviderException;

class UpdateExchangeRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:update-rates 
                            {--provider= : Preferred provider to use (ecb, config)}
                            {--force : Force update even if rates are recent}
                            {--base=EUR : Base currency for rates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update currency exchange rates from external providers';

    public function __construct(
        private readonly UpdateExchangeRatesCommandHandler $handler,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting exchange rate update...');

        $baseCurrency = $this->option('base') ?: 'EUR';
        $provider = $this->option('provider');
        $force = (bool) $this->option('force');

        try {
            $command = new DomainCommand(
                baseCurrency: $baseCurrency,
                preferredProvider: $provider,
                forceUpdate: $force,
            );

            $this->handler->handle($command);

            $this->info('✓ Exchange rates updated successfully');

            return Command::SUCCESS;
        } catch (ExchangeRateProviderException $e) {
            $this->error('Failed to update exchange rates: ' . $e->getMessage());

            return Command::FAILURE;
        } catch (Exception $e) {
            $this->error('Unexpected error: ' . $e->getMessage());
            $this->line('Run with -v for more details');

            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
