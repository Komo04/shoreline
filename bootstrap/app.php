<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Sentry\Laravel\Integration;

$routeCachePath = 'bootstrap/cache/routes-shoreline.php';
$argv = $_SERVER['argv'] ?? [];
$isRouteCacheCommand = in_array('route:cache', $argv, true) || in_array('route:clear', $argv, true);

if ($isRouteCacheCommand) {
    $routeCachePath = 'bootstrap/cache/routes-shoreline-building.php';
}

putenv("APP_ROUTES_CACHE={$routeCachePath}");
$_ENV['APP_ROUTES_CACHE'] = $routeCachePath;
$_SERVER['APP_ROUTES_CACHE'] = $routeCachePath;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            'admin'      => \App\Http\Middleware\AdminMiddleware::class,
            'purchased' => \App\Http\Middleware\PurchasedMiddleware::class,
            'active'     => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'midtrans/webhook',
            'midtrans/notification',
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);

        /*
        |--------------------------------------------------------------------------
        | 419 - CSRF Token Mismatch (SESSION HABIS SAAT USER SUDAH LOGIN)
        |--------------------------------------------------------------------------
        */
        $exceptions->render(function (TokenMismatchException $e, $request) {

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Session expired.'], 419);
            }

            return redirect()
                ->route('login')
                ->with('flash', [
                    'type' => 'warning',
                    'action' => 'expired',
                    'title' => 'Sesi Berakhir',
                    'mode' => 'modal',
                    'message' => 'Sesi Anda sudah berakhir. Silakan login kembali.',
                    'confirmText' => 'Login',
                ]);
        });

        /*
        |--------------------------------------------------------------------------
        | 401 - Unauthenticated (USER BELUM LOGIN, AKSES HALAMAN WAJIB LOGIN)
        |--------------------------------------------------------------------------
        */
        $exceptions->render(function (AuthenticationException $e, $request) {

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            $hadLogin = (bool) $request->cookie('had_login', false);

            return redirect()
                ->route('login')
                ->with('flash', $hadLogin ? [
                    'type' => 'warning',
                    'action' => 'expired',
                    'title' => 'Sesi Berakhir',
                    'mode' => 'modal',
                    'message' => 'Sesi Anda sudah berakhir. Silakan login kembali.',
                    'confirmText' => 'Login',
                ] : [
                    'type' => 'warning',
                    'action' => 'login_required',
                    'title' => 'Harus Login',
                    'mode' => 'modal',
                    'message' => 'Anda harus login dahulu untuk mengakses halaman ini.',
                    'confirmText' => 'Login',
                ]);
        });
    })

    ->create();
