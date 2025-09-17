<?php

declare(strict_types=1);

namespace Modules\Localization\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Modules\Localization\Application\Service\LocalizationService;
use Modules\Localization\Infrastructure\Laravel\Requests\Web\LocaleSwitchRequest;
use Modules\Shared\Infrastructure\Laravel\Controllers\BaseController;

final class LocaleSwitchController extends BaseController
{
    public function __construct(
        private readonly LocalizationService $localizationService,
    ) {}

    public function __invoke(LocaleSwitchRequest $request): RedirectResponse
    {
        $this->localizationService->switchLocale(
            locale: $request->locale(),
            user: $request->user(),
        );

        return redirect()->back();
    }
}
