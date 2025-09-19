<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Laravel\Livewire;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Modules\Currency\Application\Service\CurrencyPreferenceService;
use Modules\Currency\Domain\ValueObject\Currency;

class CurrencySelector extends Component
{
    public string $currentCurrency;

    /** @var array<string, mixed> */
    public array $availableCurrencies = [];

    public bool $showDropdown = false;

    public function mount(CurrencyPreferenceService $currencyService): void
    {
        $this->currentCurrency = $currencyService->getCurrentCurrency()->getCode();
        $this->availableCurrencies = $currencyService->getAvailableCurrenciesData();
    }

    public function setCurrency(string $currencyCode): void
    {
        $currencyService = app(CurrencyPreferenceService::class);
        $currency = Currency::fromString($currencyCode);
        $currencyService->setCurrentCurrency($currency);

        $this->currentCurrency = $currencyCode;
        $this->showDropdown = false;

        // Emit event for other components to react
        $this->dispatch('currency-changed', currency: $currencyCode);

        // Refresh the page to apply new currency
        $this->redirect(request()->header('Referer'));
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = ! $this->showDropdown;
    }

    public function render(): View|Factory
    {
        return view('livewire.currency-selector');
    }
}
