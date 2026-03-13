<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): Response|RedirectResponse
    {
        // Samakan perilaku session dengan login sukses.
        $request->session()->put('had_login', true);

        return redirect()->route('home');
    }
}
