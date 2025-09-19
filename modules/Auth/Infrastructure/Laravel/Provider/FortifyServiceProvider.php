<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Provider;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Modules\Auth\Infrastructure\Laravel\Actions\Fortify\CreateNewUser;
use Modules\Auth\Infrastructure\Laravel\Actions\Fortify\ResetUserPassword;
use Modules\Auth\Infrastructure\Laravel\Actions\Fortify\UpdateUserPassword;
use Modules\Auth\Infrastructure\Laravel\Actions\Fortify\UpdateUserProfileInformation;
use Modules\Auth\Infrastructure\Laravel\Responses\LoginResponse;

final class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Disable default Fortify routes in register method
        Fortify::ignoreRoutes();

        // Bind custom LoginResponse for localized redirects
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));

        // API rate limiter
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()->id ?? $request->ip()));

        // Configure view rendering
        Fortify::loginView(fn (): View|Factory => view('auth.login'));

        Fortify::registerView(fn (): View|Factory => view('auth.register'));

        Fortify::requestPasswordResetLinkView(fn (): View|Factory => view('auth.forgot-password'));

        Fortify::resetPasswordView(fn ($request): View|Factory => view('auth.reset-password', ['request' => $request]));

        Fortify::verifyEmailView(fn (): View|Factory => view('auth.verify-email'));

        Fortify::confirmPasswordView(fn (): View|Factory => view('auth.confirm-password'));

        Fortify::twoFactorChallengeView(fn (): View|Factory => view('auth.two-factor-challenge'));
    }
}
