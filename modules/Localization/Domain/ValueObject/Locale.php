<?php

declare(strict_types=1);

namespace Modules\Localization\Domain\ValueObject;

use Modules\Localization\Domain\Exception\InvalidLocaleException;
use Stringable;

final readonly class Locale implements Stringable
{
    private const VALID_LOCALES = [
        'en' => 'English',
        'fr' => 'Français',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'nl' => 'Nederlands',
        'pt' => 'Português',
        'ar' => 'العربية',
        'zh' => '中文',
        'ja' => '日本語',
    ];

    private string $code;

    public function __construct(string $code)
    {
        if (! $this->isValid($code)) {
            throw InvalidLocaleException::unsupportedLocale($code);
        }

        $this->code = $code;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return self::VALID_LOCALES[$this->code];
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }

    public static function default(): self
    {
        return new self('en');
    }

    public static function fromString(string $code): self
    {
        return new self($code);
    }

    /**
     * @return array<int, string>
     */
    public static function availableLocales(): array
    {
        return array_keys(self::VALID_LOCALES);
    }

    private function isValid(string $code): bool
    {
        return isset(self::VALID_LOCALES[$code]);
    }
}
