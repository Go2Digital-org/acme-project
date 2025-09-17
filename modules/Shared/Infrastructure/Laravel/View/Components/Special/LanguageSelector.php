<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\View\Components\Special;

use Illuminate\View\Component;
use Illuminate\View\View;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

final class LanguageSelector extends Component
{
    /** @var array<string, array<string, string>> */
    public array $supportedLocales;

    public string $currentLocale;

    /** @var array<string, array<string, string>> */
    public array $localeData;

    /** @var array<string, string> */
    public array $languageUrls;

    /** @var array<string, array<string, string>> */
    public array $activeLanguages;

    public function __construct()
    {
        $this->prepareLanguageData();
    }

    public function render(): View
    {
        return view('components.language-selector');
    }

    private function prepareLanguageData(): void
    {
        $this->supportedLocales = LaravelLocalization::getSupportedLocales();
        $this->currentLocale = LaravelLocalization::getCurrentLocale() ?? 'en';

        // Map locale codes to flag emojis and native names
        $this->localeData = [
            'en' => ['flag' => '🇬🇧', 'name' => 'English', 'native_name' => 'English', 'code' => 'en'],
            'fr' => ['flag' => '🇫🇷', 'name' => 'French', 'native_name' => 'Français', 'code' => 'fr'],
            'nl' => ['flag' => '🇳🇱', 'name' => 'Dutch', 'native_name' => 'Nederlands', 'code' => 'nl'],
        ];

        // Generate URLs for each language
        $this->languageUrls = [];
        $activeLanguages = collect();

        foreach (array_keys($this->supportedLocales) as $locale) {
            if (isset($this->localeData[$locale])) {
                $url = LaravelLocalization::getLocalizedURL($locale, null, [], true);
                $this->languageUrls[$locale] = is_string($url) ? $url : '';
                $activeLanguages->put($locale, (object) $this->localeData[$locale]);
            }
        }

        $this->activeLanguages = $activeLanguages->keyBy('code')->map(fn ($lang): array => [
            'name' => $lang->native_name,
            'flag' => $lang->flag,
            'code' => $lang->code,
        ])->toArray();
    }
}
