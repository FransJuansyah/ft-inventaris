<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\LoginResponse;
/* @chisel-passkeys */
use App\Http\Responses\PasskeyLoginResponse;
/* @end-chisel-passkeys */
/* @chisel-2fa */
use App\Http\Responses\TwoFactorLoginResponse;
/* @end-chisel-2fa */
/* @chisel-email-verification */
use App\Http\Responses\VerifyEmailResponse;
/* @end-chisel-email-verification */
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
/* @chisel-2fa */
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
/* @end-chisel-2fa */
/* @chisel-email-verification */
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
/* @end-chisel-email-verification */
use Laravel\Fortify\Fortify;
/* @chisel-passkeys */
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;

/* @end-chisel-passkeys */

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        /* @chisel-passkeys */
        $this->app->singleton(PasskeyLoginResponseContract::class, PasskeyLoginResponse::class);
        /* @end-chisel-passkeys */
        /* @chisel-2fa */
        $this->app->singleton(TwoFactorLoginResponseContract::class, TwoFactorLoginResponse::class);
        /* @end-chisel-2fa */
        /* @chisel-email-verification */
        $this->app->singleton(VerifyEmailResponseContract::class, VerifyEmailResponse::class);
        /* @end-chisel-email-verification */
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Akun nonaktif tidak dapat login (FR-AUTH-003) — pesan tetap generik
        // seperti kredensial salah, tidak membocorkan status akun (E2 UC-AUTH-001).
        Fortify::authenticateUsing(function (Request $request) {
            $user = \App\Models\User::where('email', $request->email)->first();

            if (! $user || ! \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
                return null;
            }

            return $user->is_active ? $user : null;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        /* @chisel-email-verification */
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        /* @end-chisel-email-verification */
        /* @chisel-2fa */
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        /* @end-chisel-2fa */
        /* @chisel-password-confirmation */
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
        /* @end-chisel-password-confirmation */
        Fortify::resetPasswordView(fn () => view('pages::auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages::auth.forgot-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        /* @chisel-2fa */
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
        /* @end-chisel-2fa */

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        /* @chisel-passkeys */
        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
        /* @end-chisel-passkeys */
    }
}
