
{{-- ACME Corp Language Selector Component --}}
<div 
    x-data="{ 
        open: false, 
        currentLocale: '{{ $currentLocale }}',
        languages: @js($activeLanguages),
        languageUrls: @js($languageUrls)
    }"
    @click.away="open = false"
    class="relative"
>
    {{-- Desktop Language Selector --}}
    <div class="hidden lg:block">
        <button
            @click="open = !open"
            class="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all duration-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/50 ring-1 ring-transparent hover:ring-gray-200 dark:hover:ring-gray-700"
            :aria-expanded="open"
            aria-haspopup="true"
            :class="{ 'ring-indigo-200 dark:ring-indigo-800 bg-gray-50 dark:bg-gray-800/50 text-indigo-600 dark:text-indigo-400': open }"
        >
            <span x-text="languages[currentLocale].flag" class="text-lg"></span>
            <span x-text="languages[currentLocale].name" class="hidden xl:inline font-medium"></span>
            <svg 
                class="w-4 h-4 transition-all duration-200 text-gray-400" 
                :class="{ 'rotate-180 text-indigo-500': open }" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        {{-- Desktop Dropdown --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="transform opacity-0 scale-95 translate-y-1"
            x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="transform opacity-0 scale-95 translate-y-1"
            class="absolute right-0 mt-2 w-52 bg-white dark:bg-gray-900 rounded-xl shadow-lg ring-1 ring-black/5 dark:ring-white/10 border border-gray-100 dark:border-gray-800 z-50 overflow-hidden"
            style="display: none;"
        >
            <div class="p-1">
                <div class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800 mb-1">
                    Choose Language
                </div>
                <template x-for="(lang, code) in languages" :key="code">
                    <a
                        :href="languageUrls[code]"
                        @click="currentLocale = code; open = false"
                        class="flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:text-indigo-700 dark:hover:text-indigo-300 transition-all duration-200 rounded-lg group"
                        :class="{ 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 font-medium': currentLocale === code }"
                    >
                        <span x-text="lang.flag" class="text-lg group-hover:scale-110 transition-transform duration-200"></span>
                        <span x-text="lang.name" class="font-medium"></span>
                        <svg x-show="currentLocale === code" class="w-4 h-4 ml-auto text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </a>
                </template>
            </div>
        </div>
    </div>

    {{-- Mobile Language Selector (for mobile menu) --}}
    <div class="lg:hidden">
        <div class="flex items-center justify-between py-3 px-1">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/50">
                    <span x-text="languages[currentLocale].flag" class="text-lg"></span>
                </div>
                <div class="flex flex-col">
                    <span class="text-base font-medium text-gray-900 dark:text-white">Language</span>
                    <span x-text="languages[currentLocale].name" class="text-sm text-gray-500 dark:text-gray-400"></span>
                </div>
            </div>
            <button
                @click="open = !open"
                class="flex items-center justify-center w-9 h-9 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 ring-1 ring-transparent hover:ring-gray-200 dark:hover:ring-gray-700"
                :aria-expanded="open"
                :class="{ 'bg-gray-100 dark:bg-gray-800 ring-gray-200 dark:ring-gray-700': open }"
            >
                <svg 
                    class="w-5 h-5 transition-all duration-200 text-gray-400" 
                    :class="{ 'rotate-180 text-indigo-500': open }" 
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
        </div>

        {{-- Mobile Language Options --}}
        <div 
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform opacity-0 -translate-y-2"
            x-transition:enter-end="transform opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="transform opacity-100 translate-y-0"
            x-transition:leave-end="transform opacity-0 -translate-y-2"
            class="ml-6 pb-3 space-y-1"
            style="display: none;"
        >
            <template x-for="(lang, code) in languages" :key="code">
                <a
                    :href="languageUrls[code]"
                    @click="currentLocale = code; open = false"
                    class="flex items-center gap-3 py-2.5 px-3 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:text-indigo-700 dark:hover:text-indigo-300 transition-all duration-200"
                    :class="{ 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 font-medium': currentLocale === code }"
                >
                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800">
                        <span x-text="lang.flag" class="text-base"></span>
                    </div>
                    <span x-text="lang.name" class="text-base font-medium"></span>
                    <svg x-show="currentLocale === code" class="w-4 h-4 ml-auto text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </a>
            </template>
        </div>
    </div>
</div>