<?php

declare(strict_types=1);

namespace Modules\Localization\Domain\Exception;

use DomainException;

final class InvalidLocaleException extends DomainException
{
    public static function unsupportedLocale(string $locale): self
    {
        return new self(sprintf('The locale "%s" is not supported.', $locale));
    }
}
