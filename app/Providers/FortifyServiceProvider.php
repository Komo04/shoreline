<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\LoginResponse as LoginResponseHandler;
use App\Http\Responses\RegisterResponse as RegisterResponseHandler;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::loginView(fn() => view('auth.login'));
        Fortify::registerView(fn() => view('auth.register'));
        Fortify::requestPasswordResetLinkView(fn() => view('auth.forgotpassword'));

        Fortify::resetPasswordView(function (Request $request) {
            return view('auth.resetpass', [
                'email' => $request->email,
                'token' => $request->route('token'),
            ]);
        });

        Fortify::verifyEmailView(fn() => view('auth.verifyemail'));

        Fortify::authenticateUsing(function (Request $request) {
            $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ]);

            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return null;
            }

            if (! (bool) ($user->is_active ?? true)) {
                throw ValidationException::withMessages([
                    'email' => 'Akun kamu nonaktif. Hubungi admin.',
                ]);
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::lower($request->input(Fortify::username())) . '|' . $request->ip();
            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('verification', function (Request $request) {
            return Limit::perMinute(6)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('profile-update', function (Request $request) {
            return Limit::perMinute(10)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('password-update', function (Request $request) {
            return Limit::perMinute(5)->by(optional($request->user())->id ?: $request->ip());
        });

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::redirectUserForTwoFactorAuthenticationUsing(
            RedirectIfTwoFactorAuthenticatable::class
        );

        // Use dedicated handlers to keep auth response behavior consistent.
        $this->app->singleton(LoginResponse::class, LoginResponseHandler::class);
        $this->app->singleton(RegisterResponse::class, RegisterResponseHandler::class);

        $this->app->singleton(LogoutResponse::class, function () {
            return new class implements LogoutResponse {
                public function toResponse($request)
                {
                    return redirect()->route('login')
                        ->withCookie(cookie()->forget('had_login'))
                        ->with('flash', [
                            'type' => 'success',
                            'action' => 'logout',
                            'entity' => 'Akun',
                        ]);
                }
            };
        });

        $this->app->singleton(VerifyEmailResponse::class, function () {
            return new class implements VerifyEmailResponse {
                public function toResponse($request)
                {
                    $request->session()->put('had_login', true);
                    $user = $request->user();

                    if ($user && $user->user_role === 'admin') {
                        return redirect()
                            ->route('admin.dashboard')
                            ->with('verified', true)
                            ->withCookie(cookie()->forever('had_login', '1'));
                    }

                    return redirect('/')
                        ->with('verified', true)
                        ->withCookie(cookie()->forever('had_login', '1'));
                }
            };
        });
    }
}
