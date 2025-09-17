<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Command;

final readonly class UpdateExchangeRatesCommand
{
    public function __construct(
        public string $baseCurrency = 'EUR',
        public ?string $preferredProvider = null,
        public bool $forceUpdate = false,
    ) {}
}
