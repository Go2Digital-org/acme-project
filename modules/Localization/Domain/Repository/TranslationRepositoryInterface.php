<?php

declare(strict_types=1);

namespace Modules\Localization\Domain\Repository;

use Modules\Localization\Domain\Model\Translation;
use Modules\Localization\Domain\ValueObject\Locale;
use Modules\Localization\Domain\ValueObject\TranslationKey;

interface TranslationRepositoryInterface
{
    public function findByKeyAndLocale(TranslationKey $key, Locale $locale): ?Translation;

    /**
     * @return array<Translation>
     */
    public function findByLocale(Locale $locale): array;

    /**
     * @return array<Translation>
     */
    public function findByGroup(string $group, Locale $locale): array;

    public function save(Translation $translation): void;

    public function delete(Translation $translation): void;

    /**
     * @return array<string, string>
     */
    public function getAllTranslationsForLocale(Locale $locale): array;

    /**
     * @return array<string>
     */
    public function getAvailableGroups(): array;

    public function clearCache(?Locale $locale = null): void;
}
