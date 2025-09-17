<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

class SetUserCurrencyCommand implements CommandInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly string $currencyCode,
    ) {}
}
