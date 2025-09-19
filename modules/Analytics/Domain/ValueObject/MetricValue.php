<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\ValueObject;

use InvalidArgumentException;

class MetricValue
{
    public function __construct(
        public float $value,
        public string $label,
        public ?string $unit = '',
        public ?float $previousValue = null,
        public bool $isPercentage = false,
        public int $precision = 2,
    ) {
        if ($this->precision < 0) {
            throw new InvalidArgumentException('Precision cannot be negative');
        }
    }

    public static function currency(mixed $value, string $label, string $currency = 'EUR', mixed $previousValue = null): self
    {
        return new self(
            value: (float) $value,
            label: $label,
            unit: $currency,
            previousValue: $previousValue !== null ? (float) $previousValue : null,
            precision: 2,
        );
    }

    public static function count(int $value, string $label, ?int $previousValue = null): self
    {
        return new self(
            value: (float) $value,
            label: $label,
            unit: null,
            previousValue: $previousValue !== null ? (float) $previousValue : null,
            precision: 0,
        );
    }

    public static function percentage(float $value, string $label, ?float $previousValue = null): self
    {
        return new self(
            value: $value,
            label: $label,
            unit: '%',
            previousValue: $previousValue,
            isPercentage: true,
            precision: 1,
        );
    }

    public static function rate(float $value, string $label, string $unit = '', ?float $previousValue = null): self
    {
        return new self(
            value: $value,
            label: $label,
            unit: $unit,
            previousValue: $previousValue,
            precision: 2,
        );
    }

    public static function bytes(int $value, string $label, ?int $previousValue = null): self
    {
        return new self(
            value: (float) $value,
            label: $label,
            unit: 'bytes',
            previousValue: $previousValue !== null ? (float) $previousValue : null,
            precision: 0,
        );
    }

    public static function duration(int $seconds, string $label, ?int $previousValue = null): self
    {
        return new self(
            value: (float) $seconds,
            label: $label,
            unit: 'seconds',
            previousValue: $previousValue !== null ? (float) $previousValue : null,
            precision: 0,
        );
    }

    public function getFormattedValue(): string
    {
        if ($this->isPercentage) {
            return number_format($this->value, $this->precision) . '%';
        }

        if ($this->unit === 'EUR' || $this->unit === 'USD' || $this->unit === 'GBP') {
            return $this->formatCurrency($this->value, $this->unit);
        }

        if ($this->unit === 'bytes') {
            return $this->formatBytes($this->value);
        }

        if ($this->unit === 'seconds') {
            return $this->formatDuration($this->value);
        }

        // For count metrics (unit is null), apply count formatting
        if ($this->unit === null && $this->precision === 0) {
            return $this->formatCount($this->value);
        }

        $formattedValue = number_format($this->value, $this->precision);

        return $formattedValue . ($this->unit ? ' ' . $this->unit : '');
    }

    public function getChangePercentage(): ?float
    {
        if ($this->previousValue === null || $this->previousValue === 0.0) {
            return null;
        }

        return (($this->value - $this->previousValue) / $this->previousValue) * 100;
    }

    public function getChangeDirection(): string
    {
        $changePercentage = $this->getChangePercentage();

        if ($changePercentage === null) {
            return 'neutral';
        }

        return match (true) {
            $changePercentage > 0 => 'up',
            $changePercentage < 0 => 'down',
            default => 'neutral',
        };
    }

    public function getChangeColor(): string
    {
        return match ($this->getChangeDirection()) {
            'up' => 'success',
            'down' => 'danger',
            default => 'gray',
        };
    }

    public function getChangeIcon(): string
    {
        return match ($this->getChangeDirection()) {
            'up' => 'heroicon-m-arrow-trending-up',
            'down' => 'heroicon-m-arrow-trending-down',
            default => 'heroicon-m-minus',
        };
    }

    public function hasSignificantChange(float $threshold = 5.0): bool
    {
        $changePercentage = $this->getChangePercentage();

        if ($changePercentage === null) {
            return false;
        }

        return abs($changePercentage) >= $threshold;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'value' => $this->precision === 0 && $this->unit === null ? (int) $this->value : $this->value,
            'formatted_value' => $this->getFormattedValue(),
            'label' => $this->label,
            'unit' => $this->unit,
            'previous_value' => $this->previousValue !== null && $this->precision === 0 && $this->unit === null ? (int) $this->previousValue : $this->previousValue,
            'change_percentage' => $this->getChangePercentage(),
            'change_direction' => $this->getChangeDirection(),
            'change_color' => $this->getChangeColor(),
            'change_icon' => $this->getChangeIcon(),
            'has_significant_change' => $this->hasSignificantChange(),
            'is_percentage' => $this->isPercentage,
            'precision' => $this->precision,
        ];
    }

    private function formatCurrency(float $value, string $currency): string
    {
        $symbol = match ($currency) {
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            default => $currency,
        };

        if ($value >= 1_000_000_000) {
            return $symbol . number_format($value / 1_000_000_000, 1) . 'B';
        }

        if ($value >= 1_000_000) {
            return $symbol . number_format($value / 1_000_000, 1) . 'M';
        }

        if ($value >= 1_000) {
            return $symbol . number_format($value / 1_000, 1) . 'K';
        }

        return $symbol . number_format($value, 2);
    }

    private function formatBytes(float $bytes): string
    {
        if ($bytes === 0.0) {
            return '0B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor(log($bytes, 1024));

        return sprintf('%.2f%s', $bytes / 1024 ** $factor, $units[$factor] ?? 'B');
    }

    private function formatDuration(float $seconds): string
    {
        if ($seconds < 60) {
            return number_format($seconds) . 's';
        }

        if ($seconds < 3600) {
            return number_format($seconds / 60, 1) . 'm';
        }

        if ($seconds < 86400) {
            return number_format($seconds / 3600, 1) . 'h';
        }

        return number_format($seconds / 86400, 1) . 'd';
    }

    private function formatCount(float $value): string
    {
        // Use comma formatting for values under 100,000
        if ($value < 100_000) {
            return number_format($value, 0);
        }

        if ($value >= 1_000_000) {
            return number_format($value / 1_000_000, 1) . 'M';
        }

        if ($value >= 1_000) {
            return number_format($value / 1_000, 1) . 'K';
        }

        return number_format($value, 0);
    }
}
