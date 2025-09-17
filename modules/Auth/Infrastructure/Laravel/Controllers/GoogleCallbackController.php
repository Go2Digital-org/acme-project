<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Infrastructure\Laravel\Actions\HandleGoogleCallbackAction;

class GoogleCallbackController
{
    public function __invoke(HandleGoogleCallbackAction $handleGoogleCallbackAction): RedirectResponse
    {
        try {
            $user = $handleGoogleCallbackAction->execute();

            Auth::login($user, true);

            return redirect()->intended(route('dashboard'));
        } catch (Exception) {
            return redirect()
                ->route('login')
                ->with('error', __('Unable to sign in with Google. Please try again.'));
        }
    }
}
