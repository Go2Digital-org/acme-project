@php
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
$currentLocale = LaravelLocalization::getCurrentLocale();
$supportedLocales = LaravelLocalization::getSupportedLocales();
@endphp

<div x-data="{ open: false }" class="relative">
    {{-- Current Language Button --}}
    <button
        @click="open = !open"
        @click.away="open = false"
        class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        aria-label="{{ __('Change language') }}"
        aria-expanded="false"
        aria-haspopup="true"
    >
        <span class="text-base">
            @switch($currentLocale)
                @case('nl')
                    🇳🇱
                    @break
                @case('fr')
                    🇫🇷
                    @break
                @case('en')
                    🇬🇧
                    @break
                @default
                    
            @endswitch
        </span>
        <span>{{ strtoupper($currentLocale) }}</span>
        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    {{-- Language Dropdown --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-lg bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-gray-700 focus:outline-none"
        role="menu"
        aria-orientation="vertical"
        aria-labelledby="language-menu"
    >
        <div class="py-1" role="none">
            @foreach($supportedLocales as $localeCode => $properties)
                <a
                    href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}"
                    class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors @if($localeCode === $currentLocale) bg-gray-50 dark:bg-gray-700 @endif"
                    role="menuitem"
                    @if($localeCode === $currentLocale) aria-current="true" @endif
                >
                    <span class="text-lg">
                        @switch($localeCode)
                            @case('nl')
                                🇳🇱
                                @break
                            @case('fr')
                                🇫🇷
                                @break
                            @case('en')
                                🇬🇧
                                @break
                            @default
                                
                        @endswitch
                    </span>
                    <div class="flex-1">
                        <div class="font-medium">{{ $properties['native'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $properties['name'] }}</div>
                    </div>
                    @if($localeCode === $currentLocale)
                        <svg class="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</div>