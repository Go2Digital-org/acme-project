<?php

declare(strict_types=1);

namespace Modules\Localization\Domain\Service;

use Exception;
use Modules\Localization\Domain\Model\Translation;
use Modules\Localization\Domain\Repository\TranslationRepositoryInterface;
use Modules\Localization\Domain\ValueObject\Locale;
use Modules\Localization\Domain\ValueObject\TranslationKey;

final readonly class TranslationService
{
    public function __construct(
        private TranslationRepositoryInterface $translationRepository
    ) {}

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function translate(string $key, Locale $locale, array $parameters = []): string
    {
        try {
            $translationKey = new TranslationKey($key);
            $translation = $this->translationRepository->findByKeyAndLocale($translationKey, $locale);

            if (! $translation instanceof Translation) {
                // Fallback to default locale
                $defaultLocale = Locale::default();
                if (! $locale->equals($defaultLocale)) {
                    $translation = $this->translationRepository->findByKeyAndLocale($translationKey, $defaultLocale);
                }
            }

            if (! $translation instanceof Translation) {
                return $key; // Return the key if no translation found
            }

            $value = $translation->getValue();

            // Replace parameters
            foreach ($parameters as $param => $replacement) {
                $value = str_replace(':' . $param, (string) $replacement, $value);
            }

            return $value;
        } catch (Exception) {
            return $key;
        }
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function addTranslation(
        string $key,
        Locale $locale,
        string $value,
        ?string $group = null,
        ?array $metadata = null
    ): Translation {
        $translationKey = new TranslationKey($key);

        $translation = new Translation(
            id: null,
            key: $translationKey,
            locale: $locale,
            value: $value,
            group: $group,
            metadata: $metadata
        );

        $this->translationRepository->save($translation);

        return $translation;
    }

    public function updateTranslation(string $key, Locale $locale, string $newValue): bool
    {
        $translationKey = new TranslationKey($key);
        $translation = $this->translationRepository->findByKeyAndLocale($translationKey, $locale);

        if (! $translation instanceof Translation) {
            return false;
        }

        $translation->updateValue($newValue);
        $this->translationRepository->save($translation);

        return true;
    }

    /**
     * @return array<string>
     */
    public function getSupportedLocales(): array
    {
        return Locale::availableLocales();
    }

    public function isLocaleSupported(string $localeCode): bool
    {
        return in_array($localeCode, $this->getSupportedLocales(), true);
    }
}
