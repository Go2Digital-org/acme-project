@props([
    'name',
    'label',
    'placeholder' => '',
    'required' => false,
    'value' => [],
    'error' => null,
    'hint' => null,
    'rows' => 4,
    'disabled' => false,
])

@php
    $languages = [
        'en' => ['name' => 'English', 'flag' => '🇬🇧', 'required' => true],
        'nl' => ['name' => 'Dutch', 'flag' => '🇳🇱', 'required' => false],
        'fr' => ['name' => 'French', 'flag' => '🇫🇷', 'required' => false],
    ];
    
    // Ensure value is an array
    if (!is_array($value)) {
        $value = [];
    }
    
    // Parse old input or existing values
    $oldValues = [];
    foreach ($languages as $locale => $lang) {
        $oldValues[$locale] = old("{$name}.{$locale}", $value[$locale] ?? '');
    }
    
    // Check for validation errors
    $hasErrors = false;
    $localeErrors = [];
    foreach ($languages as $locale => $lang) {
        $errorKey = "{$name}.{$locale}";
        if ($errors->has($errorKey)) {
            $hasErrors = true;
            $localeErrors[$locale] = $errors->first($errorKey);
        }
    }
@endphp

<div 
    x-data="{ 
        activeTab: 'en',
        values: @js($oldValues),
        hasContent: {
            en: {{ !empty($oldValues['en']) ? 'true' : 'false' }},
            nl: {{ !empty($oldValues['nl']) ? 'true' : 'false' }},
            fr: {{ !empty($oldValues['fr']) ? 'true' : 'false' }}
        },
        updateContent(locale, value) {
            this.hasContent[locale] = value.trim() !== '';
        }
    }"
    class="translation-textarea-component"
>
    {{-- Label --}}
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        {{ $label }}
        @if($required)
            <span class="text-red-500 ml-1">*</span>
        @endif
    </label>

    {{-- Language Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700 mb-3">
        <nav class="flex space-x-1" aria-label="Language tabs">
            @foreach($languages as $locale => $lang)
                <button
                    type="button"
                    @click="activeTab = '{{ $locale }}'"
                    :class="{
                        'bg-primary text-white': activeTab === '{{ $locale }}',
                        'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300': activeTab !== '{{ $locale }}',
                        'ring-2 ring-red-500': {{ isset($localeErrors[$locale]) ? 'true' : 'false' }}
                    }"
                    class="relative px-3 py-2 text-sm font-medium rounded-t-lg transition-all duration-200"
                >
                    <span class="flex items-center gap-2">
                        <span>{{ $lang['flag'] }}</span>
                        <span>{{ $lang['name'] }}</span>
                        @if($lang['required'])
                            <span class="text-xs">*</span>
                        @endif
                        {{-- Content indicator --}}
                        <span 
                            x-show="hasContent.{{ $locale }}" 
                            class="inline-block w-2 h-2 bg-green-500 rounded-full"
                            title="Has content"
                        ></span>
                        {{-- Error indicator --}}
                        @if(isset($localeErrors[$locale]))
                            <span 
                                class="inline-block w-2 h-2 bg-red-500 rounded-full"
                                title="Has error"
                            ></span>
                        @endif
                    </span>
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Tab Content --}}
    <div class="relative">
        @foreach($languages as $locale => $lang)
            <div 
                x-show="activeTab === '{{ $locale }}'"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform translate-y-1"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 transform translate-y-0"
                x-transition:leave-end="opacity-0 transform translate-y-1"
            >
                <div class="relative">
                    <textarea
                        name="{{ $name }}[{{ $locale }}]"
                        id="{{ $name }}_{{ $locale }}"
                        rows="{{ $rows }}"
                        @input="updateContent('{{ $locale }}', $event.target.value)"
                        placeholder="{{ $placeholder }} ({{ $lang['name'] }})"
                        @if($disabled) disabled @endif
                        @if($required && $lang['required']) required @endif
                        class="
                            w-full rounded-lg border 
                            {{ isset($localeErrors[$locale]) 
                                ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' 
                                : 'border-gray-300 dark:border-gray-600 focus:ring-primary focus:border-primary' 
                            }}
                            dark:bg-gray-800 dark:text-white
                            px-4 py-2.5
                            text-sm
                            transition-colors duration-200
                            disabled:bg-gray-50 disabled:text-gray-500 dark:disabled:bg-gray-900 dark:disabled:text-gray-500
                            resize-y
                        "
                    >{{ $oldValues[$locale] }}</textarea>

                    {{-- Character count --}}
                    <div class="absolute bottom-2 right-2 text-xs text-gray-400 dark:text-gray-500">
                        <span x-text="values.{{ $locale }}.length"></span> characters
                    </div>
                </div>

                {{-- Error message for this locale --}}
                @if(isset($localeErrors[$locale]))
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">
                        {{ $localeErrors[$locale] }}
                    </p>
                @endif

                {{-- Hint for this locale (only show on English tab) --}}
                @if($hint && $locale === 'en')
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $hint }}
                    </p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- General error message --}}
    @if($error && !$hasErrors)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">
            {{ $error }}
        </p>
    @endif

    {{-- Translation completeness indicator --}}
    <div class="mt-2 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
        <span>Translation status:</span>
        <div class="flex items-center gap-2">
            @foreach($languages as $locale => $lang)
                <span 
                    class="flex items-center gap-1"
                    :class="{ 'text-green-600 dark:text-green-400': hasContent.{{ $locale }}, 'text-gray-400 dark:text-gray-600': !hasContent.{{ $locale }} }"
                >
                    <span x-show="hasContent.{{ $locale }}">✓</span>
                    <span x-show="!hasContent.{{ $locale }}">○</span>
                    {{ $lang['flag'] }}
                </span>
            @endforeach
        </div>
    </div>
</div>