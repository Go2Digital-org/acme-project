@php
    $currentCurrency = session('currency', 'EUR');
    $currencies = \Modules\Currency\Domain\Model\Currency::where('is_active', true)
        ->orderBy('is_default', 'desc')
        ->orderBy('name')
        ->get();
@endphp

<form method="POST" action="{{ route('currency.change') }}" class="inline-flex items-center">
    @csrf
    <select 
        name="currency" 
        id="currency-select"
        onchange="this.form.submit()"
        class="text-sm bg-transparent border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer"
    >
        @foreach($currencies as $currency)
            <option 
                value="{{ $currency->code }}" 
                @if($currency->code === $currentCurrency) selected @endif
            >
                {{ $currency->flag }} {{ $currency->code }}
            </option>
        @endforeach
    </select>
</form>