<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Modules\Currency\Application\Service\CurrencyService;
use Modules\Currency\Infrastructure\Laravel\Requests\Web\ChangeCurrencyRequest;
use Modules\Shared\Infrastructure\Laravel\Controllers\BaseController;

final class ChangeCurrencyController extends BaseController
{
    public function __construct(
        private readonly CurrencyService $currencyService,
    ) {}

    public function __invoke(ChangeCurrencyRequest $request): RedirectResponse
    {
        $this->currencyService->changeCurrency(
            currency: $request->currency(),
            user: $request->user(),
        );

        return redirect()->back();
    }
}
