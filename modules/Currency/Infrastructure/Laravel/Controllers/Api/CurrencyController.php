<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Laravel\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Modules\Currency\Application\Service\CurrencyPreferenceService;
use Modules\Currency\Domain\ValueObject\Currency;

class CurrencyController extends Controller
{
    public function __construct(
        private readonly CurrencyPreferenceService $currencyService,
    ) {}

    /**
     * Get available currencies.
     */
    public function index(): JsonResponse
    {
        $currencies = $this->currencyService->getAvailableCurrenciesData();

        return response()->json([
            'data' => $currencies,
            'default' => $this->currencyService->getDefaultCurrency()->getCode(),
            'current' => $this->currencyService->getCurrentCurrency()->getCode(),
        ]);
    }

    /**
     * Get current user's currency preference.
     */
    public function show(Request $request): JsonResponse
    {
        if (! $request->user()) {
            return response()->json([
                'data' => [
                    'currency' => $this->currencyService->getCurrentCurrency()->toArray(),
                ],
            ]);
        }

        $currency = $this->currencyService->getUserCurrency($request->user()->id);

        if (! $currency instanceof Currency) {
            $currency = $this->currencyService->getDefaultCurrency();
        }

        return response()->json([
            'data' => [
                'currency' => $currency->toArray(),
            ],
        ]);
    }

    /**
     * Set user's currency preference.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'currency' => 'required|string|size:3',
        ]);

        try {
            $currency = Currency::fromString($request->input('currency'));
        } catch (InvalidArgumentException) {
            return response()->json([
                'message' => 'Invalid currency code',
                'errors' => [
                    'currency' => ['The selected currency is invalid.'],
                ],
            ], 422);
        }

        // Set in session for all users
        $this->currencyService->setCurrentCurrency($currency);

        // Set in database for authenticated users
        if ($request->user()) {
            $this->currencyService->setUserCurrency($request->user()->id, $currency);
        }

        return response()->json([
            'message' => 'Currency preference updated successfully',
            'data' => [
                'currency' => $currency->toArray(),
            ],
        ]);
    }

    /**
     * Format an amount in the current currency.
     */
    public function format(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric',
            'currency' => 'nullable|string|size:3',
        ]);

        $amount = (float) $request->input('amount');
        $currencyCode = $request->input('currency');

        if ($currencyCode) {
            try {
                $currency = Currency::fromString($currencyCode);
            } catch (InvalidArgumentException) {
                return response()->json([
                    'message' => 'Invalid currency code',
                ], 422);
            }
        } else {
            $currency = null;
        }

        $formatted = $this->currencyService->formatAmount($amount, $currency);

        return response()->json([
            'data' => [
                'amount' => $amount,
                'formatted' => $formatted,
                'currency' => ($currency ?? $this->currencyService->getCurrentCurrency())->toArray(),
            ],
        ]);
    }
}
