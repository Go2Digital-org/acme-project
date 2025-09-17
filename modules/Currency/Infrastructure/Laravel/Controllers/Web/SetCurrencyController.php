<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Modules\Currency\Application\Service\CurrencyPreferenceService;
use Modules\Currency\Domain\ValueObject\Currency;

class SetCurrencyController extends Controller
{
    public function __construct(
        private readonly CurrencyPreferenceService $currencyService,
    ) {}

    /**
     * Set the currency and redirect back.
     */
    public function __invoke(Request $request, string $currency): RedirectResponse
    {
        try {
            $currencyObject = Currency::fromString($currency);
            $this->currencyService->setCurrentCurrency($currencyObject);

            return redirect()->back()->with('success', 'Currency changed to ' . $currencyObject->getName());
        } catch (InvalidArgumentException) {
            return redirect()->back()->with('error', 'Invalid currency selected');
        }
    }
}
