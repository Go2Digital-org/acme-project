<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Traits;

use Illuminate\Support\Facades\App;
use InvalidArgumentException;

trait HasTranslations
{
    /**
     * Default locale.
     */
    protected string $defaultLocale = 'en';

    /**
     * Available locales.
     *
     * @var array<int, string>
     */
    protected array $availableLocales = ['en', 'nl', 'fr'];

    /**
     * Get translatable fields.
     *
     * @return array<int, string>
     */
    public function getTranslatableFields(): array
    {
        return $this->translatable;
    }

    /**
     * Get available locales.
     *
     * @return array<int, string>
     */
    public function getAvailableLocales(): array
    {
        return $this->availableLocales;
    }

    /**
     * Get translation for a field in specific locale.
     */
    public function getTranslation(string $field, ?string $locale = null): ?string
    {
        if (! in_array($field, $this->getTranslatableFields(), true)) {
            $value = $this->getDirectAttribute($field);

            return is_string($value) ? $value : null;
        }

        $locale ??= App::getLocale() ?? $this->defaultLocale;

        // Get the field value which should be an array of translations
        $translations = $this->getDirectAttribute($field);

        if (! is_array($translations)) {
            return is_string($translations) ? $translations : null;
        }

        // Return translation for specific locale, fallback to default locale
        return $translations[$locale] ?? $translations[$this->defaultLocale] ?? null;
    }

    /**
     * Set translation for a field in specific locale.
     */
    public function setTranslation(string $field, string $locale, ?string $value): self
    {
        if (! in_array($field, $this->getTranslatableFields(), true)) {
            throw new InvalidArgumentException("Field {$field} is not translatable");
        }

        if (! in_array($locale, $this->getAvailableLocales(), true)) {
            throw new InvalidArgumentException("Locale {$locale} is not supported");
        }

        /** @var array<string, string|null> $translations */
        $translations = $this->getDirectAttribute($field) ?? [];
        if (! is_array($translations)) {
            $translations = [];
        }
        $translations[$locale] = $value;

        $this->setDirectAttribute($field, $translations);

        return $this;
    }

    /**
     * Set translations for multiple locales at once.
     *
     * @param  array<string, string|null>  $translations
     */
    public function setTranslations(string $field, array $translations): self
    {
        foreach ($translations as $locale => $value) {
            $this->setTranslation($field, $locale, $value);
        }

        return $this;
    }

    /**
     * Get all translations for a field.
     *
     * @return array<string, string|null>
     */
    public function getTranslations(string $field): array
    {
        if (! in_array($field, $this->getTranslatableFields(), true)) {
            $value = $this->getDirectAttribute($field);

            return [$this->defaultLocale => is_string($value) ? $value : null];
        }

        /** @var array<string, string|null> $translations */
        $translations = $this->getDirectAttribute($field) ?? [];

        if (! is_array($translations)) {
            return [$this->defaultLocale => is_string($translations) ? $translations : null];
        }

        return $translations;
    }

    /**
     * Check if translation exists for a field in specific locale.
     */
    public function hasTranslation(string $field, string $locale): bool
    {
        $translations = $this->getTranslations($field);

        return isset($translations[$locale]) && $translations[$locale] !== '';
    }

    /**
     * Get all missing translations for the model.
     *
     * @return array<string, array<int, string>>
     */
    public function getMissingTranslations(): array
    {
        /** @var array<string, array<int, string>> $missing */
        $missing = [];

        foreach ($this->getTranslatableFields() as $field) {
            foreach ($this->getAvailableLocales() as $locale) {
                if (! $this->hasTranslation($field, $locale)) {
                    $missing[$field] ??= [];
                    $missing[$field][] = $locale;
                }
            }
        }

        return $missing;
    }

    /**
     * Check if model has complete translations for all fields and locales.
     */
    public function hasCompleteTranslations(): bool
    {
        return empty($this->getMissingTranslations());
    }

    /**
     * Get translation status.
     *
     * @return array<string, array<string, bool>>
     */
    public function getTranslationStatus(): array
    {
        /** @var array<string, array<string, bool>> $status */
        $status = [];

        foreach ($this->getTranslatableFields() as $field) {
            foreach ($this->getAvailableLocales() as $locale) {
                $status[$field][$locale] = $this->hasTranslation($field, $locale);
            }
        }

        return $status;
    }

    /**
     * Magic method to get translated field in current locale.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        // Don't interfere with magic getter - let parent handle everything
        return parent::__get($key);
    }

    /**
     * Magic method to set translated field in current locale.
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function __set($key, $value)
    {
        // For now, let parent handle all magic setting to avoid conflicts
        parent::__set($key, $value);
    }

    /**
     * Override getAttribute to handle translatable fields and dot notation for Filament.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        // Handle Spatie format (e.g., 'title.nl') - what the solution-forest plugin expects
        if (str_contains($key, '.') && ! str_contains($key, '_translations')) {
            [$field, $locale] = explode('.', $key, 2);
            if (in_array($field, $this->translatable, true)) {
                $translations = $this->getDirectAttribute($field);
                if (is_array($translations)) {
                    return $translations[$locale] ?? null;
                }

                return null;
            }
        }

        // Avoid recursion for internal properties
        if ($key === 'translatable' || $key === 'availableLocales' || $key === 'defaultLocale') {
            return parent::getAttribute($key);
        }

        // For translatable fields, return the full array (not just current locale)
        // This is needed for Filament to work with the unified JSON structure
        if (in_array($key, $this->translatable, true)) {
            $value = parent::getAttribute($key);
            // If it's already an array, return it as-is for Filament
            if (is_array($value)) {
                return $value;
            }
            // Otherwise try to decode if it's JSON string
            if (is_string($value)) {
                $decoded = json_decode($value, true);

                return is_array($decoded) ? $decoded : $value;
            }

            return $value;
        }

        return parent::getAttribute($key);
    }

    /**
     * Override setAttribute to handle translatable fields and dot notation for Filament.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        // Handle Spatie format (e.g., 'title.nl') - what the solution-forest plugin expects
        if (str_contains($key, '.') && ! str_contains($key, '_translations')) {
            [$field, $locale] = explode('.', $key, 2);
            if (in_array($field, $this->translatable, true)) {
                $translations = $this->getDirectAttribute($field) ?? [];
                if (! is_array($translations)) {
                    $translations = [];
                }
                $translations[$locale] = $value;

                return $this->setDirectAttribute($field, $translations);
            }
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Boot the trait.
     */
    public static function bootHasTranslations(): void
    {
        static::saving(function ($model): void {
            // Ensure translation fields are properly formatted
            foreach ($model->getTranslatableFields() as $field) {
                if ($model->isDirty($field)) {
                    /** @var mixed $translations */
                    $translations = $model->getAttribute($field);

                    if (is_array($translations)) {
                        // Remove null and empty values
                        /** @var array<string, string|null> $filteredTranslations */
                        $filteredTranslations = array_filter($translations, static fn ($value): bool => $value !== null && $value !== '');
                        $model->setAttribute($field, $filteredTranslations);
                    }
                }
            }
        });
    }

    /**
     * Get direct attribute value without triggering magic methods.
     * This prevents recursion when accessing translation fields.
     *
     * @return mixed
     */
    protected function getDirectAttribute(string $key)
    {
        // For translatable fields, handle JSON decoding
        if (in_array($key, $this->translatable, true)) {
            $value = $this->attributes[$key] ?? null;

            if (is_string($value)) {
                $decoded = json_decode($value, true);

                return is_array($decoded) ? $decoded : $value;
            }

            return $value;
        }

        // For normal attributes, directly access from attributes array
        // This avoids calling getAttribute which could cause recursion
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set direct attribute value without triggering magic methods.
     * This prevents recursion when setting translation fields.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function setDirectAttribute(string $key, $value)
    {
        return parent::setAttribute($key, $value);
    }
}
