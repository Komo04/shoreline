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
        // Penanda bahwa user pernah login untuk flow "sesi berakhir".
        $request->session()->put('had_login', true);

        $user = Auth::user();
        $isAdmin = $user
            ? (method_exists($user, 'isAdmin')
                ? (bool) $user->isAdmin()
                : (($user->user_role ?? null) === 'admin'))
            : false;

        if ($isAdmin) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('home');
    }
}
