<?php

declare(strict_types=1);

namespace Modules\Localization\Domain\Exception;

use DomainException;

final class InvalidTranslationKeyException extends DomainException
{
    public static function emptyKey(): self
    {
        return new self('Translation key cannot be empty.');
    }

    public static function invalidFormat(string $key): self
    {
        return new self(sprintf('Translation key "%s" has an invalid format.', $key));
    }
}
