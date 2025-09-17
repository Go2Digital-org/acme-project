@php
    // Use Laravel Localization's current locale to ensure consistency
    $currentLocale = \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocale() ?? app()->getLocale();
    $availableLocales = config('app.available_locales', ['en' => 'English', 'fr' => 'Français', 'nl' => 'Nederlands']);
    $localeFlags = [
        'en' => '🇬🇧',
        'fr' => '🇫🇷', 
        'nl' => '🇳🇱',
        'de' => '🇩🇪',
        'es' => '🇪🇸',
        'it' => '🇮🇹',
        'pt' => '🇵🇹',
        'ar' => '🇸🇦'
    ];
@endphp

<div class="relative" 
     x-data="{ 
         languageOpen: false,
         toggle() {
             if (!this.languageOpen) {
                 this.$dispatch('dropdown-opened', 'language');
                 this.languageOpen = true;
             } else {
                 this.languageOpen = false;
             }
         }
     }" 
     @click.away="languageOpen = false" 
     @click.stop
     @close-all-dropdowns.window="if($event.detail !== 'language') languageOpen = false">
    <button
        @click.stop="toggle()"
        class="flex items-center justify-center w-10 h-10 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        title="Change Language"
    >
        <span class="text-xl">{{ $localeFlags[$currentLocale] ?? '' }}</span>
    </button>
    
    <div
        x-show="languageOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-1 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-40"
        @click.stop
    >
        <div class="py-1">
            @foreach($availableLocales as $locale => $language)
                <a
                    href="{{ \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($locale) }}"
                    @click="languageOpen = false"
                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 @if($locale === $currentLocale) bg-gray-50 dark:bg-gray-700/50 @endif"
                >
                    <span class="mr-3 text-lg">{{ $localeFlags[$locale] ?? '' }}</span>
                    <span class="flex-1 text-left">{{ $language }}</span>
                    @if($locale === $currentLocale)
                        <svg class="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</div>