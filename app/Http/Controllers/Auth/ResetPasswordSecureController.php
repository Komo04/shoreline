<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetSuccessMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ResetPasswordSecureController extends Controller
{
    public function update(Request $request)
    {
        // Email DISIMPAN hidden di form, user tidak bisa edit di UI
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::default()],
        ]);

        $status = Password::reset(
            [
                'email' => $request->email,
                'token' => $request->token,
                'password' => $request->password,
                'password_confirmation' => $request->password_confirmation,
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors([
                'password' => __($status),
            ]);
        }

        // ✅ Kirim email notifikasi sukses reset ke user
        try {
            Mail::to($request->email)->queue(new PasswordResetSuccessMail($request->email));
        } catch (\Throwable $e) {
            // kalau email gagal, reset password tetap sukses
            // optional: bisa log error
            // \Log::error('Mail reset password gagal: '.$e->getMessage());
        }

      return redirect()->route('login')
  ->with('flash', [
    'type' => 'success',
    'action' => 'update',
    'entity' => 'Password',
    'detail' => 'Silakan login.',
    'mode' => 'modal',
    'title' => 'Password Diubah',
    'confirmText' => 'Login',
  ]);
    }
}

