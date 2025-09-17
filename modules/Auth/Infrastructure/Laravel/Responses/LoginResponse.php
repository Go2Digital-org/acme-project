<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): Response
    {
        // For JSON/API requests
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false], 200);
        }

        // For web requests - use Laravel's intended() method which properly handles redirects
        // This ensures a GET redirect, not a POST pass-through
        $locale = LaravelLocalization::getCurrentLocale();
        $defaultUrl = LaravelLocalization::getLocalizedURL($locale, '/dashboard');

        // Use intended() which automatically pulls from session and handles redirect properly
        // This prevents the POST data from being resubmitted
        return redirect()->intended($defaultUrl);
    }
}
