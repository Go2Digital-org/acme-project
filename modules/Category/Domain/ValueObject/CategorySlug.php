<?php

declare(strict_types=1);

namespace Modules\Category\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class CategorySlug implements Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        if (in_array(trim($value), ['', '0'], true)) {
            throw new InvalidArgumentException('Category slug cannot be empty');
        }

        if (! preg_match('/^[a-z0-9_-]+$/', $value)) {
            throw new InvalidArgumentException('Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function fromString(string $name): self
    {
        $slug = strtolower(trim($name));
        $slug = (string) preg_replace('/[^a-z0-9\s_-]/', '', $slug);
        $slug = (string) preg_replace('/[\s_-]+/', '_', $slug);
        $slug = trim($slug, '_-');

        return new self($slug);
    }

    public static function fromText(string $text): self
    {
        return self::fromString($text);
    }
}
