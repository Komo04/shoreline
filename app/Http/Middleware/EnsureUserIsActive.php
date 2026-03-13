<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

           return redirect()->route('login')->with('flash', [
  'type' => 'error',
  'action' => 'forbidden',
  'title' => 'Akun Nonaktif',
  'mode' => 'modal',
  'message' => 'Akun kamu nonaktif. Hubungi admin.',
]);
        }

        return $next($request);
    }
}
