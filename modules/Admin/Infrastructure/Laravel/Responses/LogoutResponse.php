<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Laravel\Responses;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        // Always redirect to home page after admin logout
        return redirect('/');
    }
}
