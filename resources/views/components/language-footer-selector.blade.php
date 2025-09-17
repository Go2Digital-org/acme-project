@php
    $currentLocale = app()->getLocale();
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

<select 
    id="language-select"
    onchange="window.location.href = '{{ url('/locale') }}/' + this.value"
    class="text-sm bg-transparent border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer"
>
    @foreach($availableLocales as $locale => $language)
        <option 
            value="{{ $locale }}" 
            @if($locale === $currentLocale) selected @endif
        >
            {{ $localeFlags[$locale] ?? '' }} {{ $language }}
        </option>
    @endforeach
</select>