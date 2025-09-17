<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

final readonly class ExchangeRate implements JsonSerializable, Stringable
{
    public function __construct(
        private string $fromCurrency,
        private string $toCurrency,
        private float $rate,
        private DateTimeImmutable $timestamp,
        private ?string $provider = null,
    ) {
        if ($rate <= 0) {
            throw new InvalidArgumentException('Exchange rate must be positive');
        }
    }

    public function getFromCurrency(): string
    {
        return $this->fromCurrency;
    }

    public function getToCurrency(): string
    {
        return $this->toCurrency;
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function convert(float $amount): float
    {
        return $amount * $this->rate;
    }

    public function getInverseRate(): self
    {
        return new self(
            $this->toCurrency,
            $this->fromCurrency,
            1 / $this->rate,
            $this->timestamp,
            $this->provider,
        );
    }

    public function isStale(int $maxAgeInSeconds = 3600): bool
    {
        $now = new DateTimeImmutable;
        $age = $now->getTimestamp() - $this->timestamp->getTimestamp();

        return $age > $maxAgeInSeconds;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'from' => $this->fromCurrency,
            'to' => $this->toCurrency,
            'rate' => $this->rate,
            'timestamp' => $this->timestamp->format('c'),
            'provider' => $this->provider,
        ];
    }

    public function __toString(): string
    {
        return sprintf(
            '%s/%s: %.4f @ %s',
            $this->fromCurrency,
            $this->toCurrency,
            $this->rate,
            $this->timestamp->format('Y-m-d H:i:s'),
        );
    }
}
