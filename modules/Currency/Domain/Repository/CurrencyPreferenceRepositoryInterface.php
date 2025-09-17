<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\Repository;

use Modules\Currency\Domain\ValueObject\Currency;

interface CurrencyPreferenceRepositoryInterface
{
    public function getUserCurrency(int $userId): ?Currency;

    public function setUserCurrency(int $userId, Currency $currency): void;

    public function removeUserCurrency(int $userId): void;

    public function getDefaultCurrency(): Currency;
}
