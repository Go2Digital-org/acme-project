{{--
  Currency dropdown component with pre-processed data from CurrencyDropdownComposer.
  No PHP logic here - all data processing happens in CQRS handlers and view composers.
--}}
<div class="relative"
     x-data="{
         currencyOpen: false,
         currentCurrency: '{{ $currentCurrency }}',
         toggle() {
             if (!this.currencyOpen) {
                 this.$dispatch('dropdown-opened', 'currency');
                 this.currencyOpen = true;
             } else {
                 this.currencyOpen = false;
             }
         }
     }"
     @click.away="currencyOpen = false"
     @click.stop
     @close-all-dropdowns.window="if($event.detail !== 'currency') currencyOpen = false">

    {{-- Current Currency Button --}}
    <button
        @click.stop="toggle()"
        class="flex items-center justify-center w-10 h-10 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors font-medium"
        title="Change Currency"
        :aria-expanded="currencyOpen.toString()"
    >
        {{-- Display current currency symbol (pre-processed by composer) --}}
        <span class="text-base">{{ $currentCurrencySymbol }}</span>
    </button>

    {{-- Dropdown Menu --}}
    <div
        x-show="currencyOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-1 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-40"
        @click.stop
        role="menu"
        aria-labelledby="currency-menu"
    >
        <div class="py-1">
            {{-- Currency Options (pre-processed by CQRS) --}}
            @forelse($currencies as $currency)
                <form method="POST" action="{{ route('currency.change') }}" class="w-full">
                    @csrf
                    <input type="hidden" name="currency" value="{{ $currency->code }}">
                    <button
                        type="submit"
                        @click="currencyOpen = false"
                        class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors
                        {{ $currency->code === $currentCurrency ? 'bg-gray-50 dark:bg-gray-700/50' : '' }}"
                        role="menuitem"
                    >
                        <span class="text-sm font-semibold mr-3 w-6 text-center">{{ $currency->symbol }}</span>
                        <span class="flex-1 text-left text-gray-700 dark:text-gray-300">{{ $currency->name }}</span>
                        @if($currency->code === $currentCurrency)
                            <svg class="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </button>
                </form>
            @empty
                {{-- Fallback when no currencies available --}}
                <div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                    No currencies available
                </div>
            @endforelse
        </div>
    </div>
</div>