<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shared\Domain\Model\Language;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $languageMap = [
            'en' => ['English', 'English'],
            'es' => ['Spanish', 'Español'],
            'fr' => ['French', 'Français'],
            'de' => ['German', 'Deutsch'],
            'it' => ['Italian', 'Italiano'],
            'pt' => ['Portuguese', 'Português'],
            'nl' => ['Dutch', 'Nederlands'],
            'ru' => ['Russian', 'Русский'],
            'zh' => ['Chinese', '中文'],
            'ja' => ['Japanese', '日本語'],
        ];

        $code = $this->faker->randomElement(array_keys($languageMap));
        [$name, $nativeName] = $languageMap[$code];

        return [
            'code' => $code,
            'name' => $name,
            'native_name' => $nativeName,
            'flag' => strtolower((string) $code) . '.png',
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'is_default' => false, // Usually false, specific tests can override
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Indicate that the language is active.
     */
    public function active(): self
    {
        return $this->state(fn (): array => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the language is inactive.
     */
    public function inactive(): self
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the language is the default.
     */
    public function default(): self
    {
        return $this->state(fn (): array => [
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Create an English language.
     */
    public function english(): self
    {
        return $this->state(fn (): array => [
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'flag' => 'en.png',
        ]);
    }

    /**
     * Create a Spanish language.
     */
    public function spanish(): self
    {
        return $this->state(fn (): array => [
            'code' => 'es',
            'name' => 'Spanish',
            'native_name' => 'Español',
            'flag' => 'es.png',
        ]);
    }

    /**
     * Create a French language.
     */
    public function french(): self
    {
        return $this->state(fn (): array => [
            'code' => 'fr',
            'name' => 'French',
            'native_name' => 'Français',
            'flag' => 'fr.png',
        ]);
    }
}
