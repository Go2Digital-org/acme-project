<?php

declare(strict_types=1);

namespace Modules\Localization\Domain\ValueObject;

use Modules\Localization\Domain\Exception\InvalidTranslationKeyException;
use Stringable;

final readonly class TranslationKey implements Stringable
{
    private string $key;

    public function __construct(string $key)
    {
        if ($key === '' || $key === '0') {
            throw InvalidTranslationKeyException::emptyKey();
        }

        if (! $this->isValidFormat($key)) {
            throw InvalidTranslationKeyException::invalidFormat($key);
        }

        $this->key = $key;
    }

    public function getValue(): string
    {
        return $this->key;
    }

    public function getNamespace(): ?string
    {
        if (str_contains($this->key, '::')) {
            return explode('::', $this->key)[0];
        }

        return null;
    }

    public function getGroup(): ?string
    {
        $parts = $this->getParts();

        if (count($parts) > 1) {
            array_pop($parts); // Remove the last part (the actual key)

            return implode('.', $parts);
        }

        return null;
    }

    public function equals(self $other): bool
    {
        return $this->key === $other->key;
    }

    public function __toString(): string
    {
        return $this->key;
    }

    private function isValidFormat(string $key): bool
    {
        // Format: namespace::group.key or group.key
        return preg_match('/^([a-z_]+::)?[a-z0-9_]+(\.[a-z0-9_]+)*$/i', $key) === 1;
    }

    /**
     * @return array<int, string>
     */
    private function getParts(): array
    {
        $key = $this->key;

        // Remove namespace if present
        if (str_contains($key, '::')) {
            $key = explode('::', $key)[1];
        }

        return explode('.', $key);
    }
}
