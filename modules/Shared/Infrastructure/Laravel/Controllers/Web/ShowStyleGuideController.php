<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

final class ShowStyleGuideController
{
    public function __invoke(): View|Factory
    {
        return view('style-guide');
    }
}
