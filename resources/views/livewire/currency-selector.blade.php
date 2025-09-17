<div class="relative" x-data="{ open: @entangle('showDropdown') }">
    {{-- Desktop Currency Selector --}}
    <div class="hidden md:block">
        <button
            @click="open = !open"
            @click.away="open = false"
            type="button"
            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700"
            aria-expanded="false"
            aria-haspopup="true"
        >
            <span class="text-lg">{{ $availableCurrencies[$currentCurrency]['flag'] ?? '💱' }}</span>
            <span>{{ $currentCurrency }}</span>
            <svg class="w-4 h-4 ml-1 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>
        </button>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute right-0 z-50 w-56 mt-2 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800"
            role="menu"
            aria-orientation="vertical"
            aria-labelledby="currency-menu"
            style="display: none;"
        >
            <div class="py-1" role="none">
                @foreach($availableCurrencies as $code => $currency)
                    <button
                        wire:click="setCurrency('{{ $code }}')"
                        class="flex items-center w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-700 {{ $currentCurrency === $code ? 'bg-gray-50 dark:bg-gray-700' : '' }}"
                        role="menuitem"
                    >
                        <span class="mr-3 text-lg">{{ $currency['flag'] }}</span>
                        <span class="flex-1">{{ $currency['name'] }}</span>
                        <span class="ml-auto text-gray-500 dark:text-gray-400">{{ $currency['symbol'] }}</span>
                        @if($currentCurrency === $code)
                            <svg class="w-4 h-4 ml-2 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Mobile Currency Selector --}}
    <div class="md:hidden">
        <button
            @click="open = !open"
            type="button"
            class="flex items-center justify-center w-10 h-10 text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700"
            aria-expanded="false"
            aria-haspopup="true"
        >
            <span class="text-lg">{{ $availableCurrencies[$currentCurrency]['flag'] ?? '💱' }}</span>
        </button>

        {{-- Mobile Dropdown Menu --}}
        <div
            x-show="open"
            @click.away="open = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute right-0 z-50 w-64 mt-2 origin-top-right bg-white rounded-lg shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800"
            role="menu"
            aria-orientation="vertical"
            aria-labelledby="currency-menu-mobile"
            style="display: none;"
        >
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('components.currency_selector.select_currency') }}</p>
            </div>
            <div class="py-2" role="none">
                @foreach($availableCurrencies as $code => $currency)
                    <button
                        wire:click="setCurrency('{{ $code }}')"
                        class="flex items-center w-full px-4 py-3 text-sm text-left text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-700 {{ $currentCurrency === $code ? 'bg-gray-50 dark:bg-gray-700' : '' }}"
                        role="menuitem"
                    >
                        <span class="mr-3 text-xl">{{ $currency['flag'] }}</span>
                        <div class="flex-1">
                            <div class="font-medium">{{ $code }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $currency['name'] }}</div>
                        </div>
                        @if($currentCurrency === $code)
                            <svg class="w-5 h-5 ml-2 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>