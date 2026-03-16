<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response|RedirectResponse
    {
        // Simpan di session + cookie agar status "pernah login" tetap bisa dibaca
        // walaupun session utama sudah expired.
        $request->session()->put('had_login', true);

        $user = Auth::user();
        $isAdmin = $user
            ? (method_exists($user, 'isAdmin')
                ? (bool) $user->isAdmin()
                : (($user->user_role ?? null) === 'admin'))
            : false;

        if ($isAdmin) {
            return redirect()
                ->route('admin.dashboard')
                ->withCookie(cookie()->forever('had_login', '1'));
        }

        return redirect()
            ->route('home')
            ->withCookie(cookie()->forever('had_login', '1'));
    }
}
