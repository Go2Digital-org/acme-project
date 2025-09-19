<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Modules\Currency\Application\Service\CurrencyPreferenceService;
use Modules\Currency\Domain\ValueObject\Currency;
use Symfony\Component\HttpFoundation\Response;

class SetCurrency
{
    public function __construct(
        private readonly CurrencyPreferenceService $currencyService,
    ) {}

    /**
     * Handle currency setting for the application.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if currency is specified in the request
        if ($request->has('currency')) {
            try {
                $currency = Currency::fromString($request->get('currency'));
                $this->currencyService->setCurrentCurrency($currency);
            } catch (InvalidArgumentException) {
                // Invalid currency code, ignore
            }
        }

        // Set the current currency in the app context
        $currentCurrency = $this->currencyService->getCurrentCurrency();
        app()->instance('current.currency', $currentCurrency);

        // Share currency data with all views
        view()->share('currentCurrency', $currentCurrency);
        view()->share('availableCurrencies', $this->currencyService->getAvailableCurrenciesData());

        return $next($request);
    }
}
