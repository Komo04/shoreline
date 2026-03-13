<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            abort(401);
        }

        $user = Auth::user();
        $isAdmin = method_exists($user, 'isAdmin')
            ? (bool) $user->isAdmin()
            : (($user->user_role ?? null) === 'admin');

        if (! $isAdmin) {
            if ($request->expectsJson()) {
                abort(403, 'Akses ditolak, Anda bukan admin.');
            }

            return redirect()->route('home')->with('flash', [
                'type' => 'error',
                'action' => 'forbidden',
                'title' => 'Akses Ditolak',
                'message' => 'Akses ditolak, Anda bukan admin.',
                'entity' => 'Area Admin',
                'mode' => 'modal',
            ]);
        }

        return $next($request);
    }
}
