<?php

declare(strict_types=1);

namespace Modules\Localization\Domain\Model;

use DateTimeImmutable;
use DateTimeInterface;
use Modules\Localization\Domain\ValueObject\Locale;
use Modules\Localization\Domain\ValueObject\TranslationKey;

final class Translation
{
    private readonly ?string $group;

    private readonly DateTimeInterface $createdAt;

    private DateTimeInterface $updatedAt;

    public function __construct(
        private readonly ?string $id,
        private readonly TranslationKey $key,
        private readonly Locale $locale,
        private string $value,
        ?string $group = null,
        /**
         * @var array<string, mixed>|null
         */
        private ?array $metadata = null
    ) {
        $this->group = $group ?? $this->key->getGroup();
        $this->createdAt = new DateTimeImmutable;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getKey(): TranslationKey
    {
        return $this->key;
    }

    public function getLocale(): Locale
    {
        return $this->locale;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function updateValue(string $value): void
    {
        $this->value = $value;
        $this->updatedAt = new DateTimeImmutable;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function updateMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
        $this->updatedAt = new DateTimeImmutable;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }
}
