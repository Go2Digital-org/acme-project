<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Command;

use InvalidArgumentException;
use Modules\Currency\Application\Service\CurrencyPreferenceService;
use Modules\Currency\Domain\ValueObject\Currency;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

class SetUserCurrencyCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CurrencyPreferenceService $currencyService,
    ) {}

    public function handle(CommandInterface $command): mixed
    {
        if (! $command instanceof SetUserCurrencyCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        $currency = Currency::fromString($command->currencyCode);
        $this->currencyService->setUserCurrency($command->userId, $currency);

        return null;
    }
}
