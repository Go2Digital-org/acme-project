<?php

declare(strict_types=1);

namespace Modules\Shared\Application\ViewPresenter;

use DateTime;
use DateTimeInterface;
use Illuminate\Support\Collection;
use JsonSerializable;
use RuntimeException;

/**
 * Base abstract class for all view presenters
 * Provides common methods for data transformation and array/object conversion.
 */
abstract class AbstractViewPresenter implements JsonSerializable
{
    public function __construct(protected mixed $data) {}

    /**
     * Transform data for presentation.
     */
    /** @return array<array-key, mixed> */
    abstract public function present(): array;

    /**
     * Get the underlying data.
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Convert presenter to array.
     */
    /** @return array<array-key, mixed> */
    public function toArray(): array
    {
        return $this->present();
    }

    /**
     * JSON serialization.
     */
    /** @return array<array-key, mixed> */
    public function jsonSerialize(): array
    {
        return $this->present();
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(int $options = 0): string
    {
        $json = json_encode($this->present(), $options);

        if ($json === false) {
            throw new RuntimeException('Failed to encode data as JSON');
        }

        return $json;
    }

    /**
     * Create a new presenter instance with different data.
     */
    public function with(mixed $data): static
    {
        $className = static::class;

        return new $className($data);
    }

    /**
     * Check if data exists.
     */
    protected function hasData(): bool
    {
        return $this->data !== null;
    }

    /**
     * Safely get a value from array/object.
     */
    protected function getValue(string $key, mixed $default = null): mixed
    {
        if (! $this->hasData()) {
            return $default;
        }

        if (is_array($this->data)) {
            return $this->data[$key] ?? $default;
        }

        if (is_object($this->data)) {
            return $this->data->{$key} ?? $default;
        }

        return $default;
    }

    /**
     * Format money amount with currency symbol.
     */
    protected function formatMoney(float|string $amount, string $currency = 'USD'): string
    {
        // Convert string to float if needed (handles decimal cast from Eloquent)
        $amount = is_string($amount) ? (float) $amount : $amount;

        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        $symbol = $symbols[$currency] ?? $currency;

        return $symbol . number_format($amount, 2);
    }

    /**
     * Format date for display.
     */
    protected function formatDate(mixed $date, string $format = 'M j, Y'): ?string
    {
        if (! $date) {
            return null;
        }

        if (is_string($date)) {
            $date = new DateTime($date);
        }

        if (! $date instanceof DateTimeInterface) {
            return null;
        }

        return $date->format($format);
    }

    /**
     * Format percentage with precision.
     */
    protected function formatPercentage(float $percentage, int $precision = 1): string
    {
        return number_format($percentage, $precision) . '%';
    }

    /**
     * Truncate text with ellipsis.
     */
    protected function truncateText(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length - strlen($suffix)) . $suffix;
    }

    /**
     * Generate CSS classes from array.
     */
    /**
     * @param  array<int|string, string>  $classes
     */
    protected function generateClasses(array $classes): string
    {
        $filtered = array_filter($classes, fn (string $class): bool => $class !== '' && $class !== '0');

        return implode(' ', $filtered);
    }

    /**
     * Apply conditional CSS class.
     */
    protected function conditionalClass(bool $condition, string $class, string $alternative = ''): string
    {
        return $condition ? $class : $alternative;
    }

    /**
     * Transform collection data.
     *
     * @param  Collection<int|string, mixed>  $collection
     * @return array<array-key, mixed>
     */
    protected function transformCollection(Collection $collection, callable $transformer): array
    {
        return $collection->map($transformer)->toArray();
    }
}
