<?php

namespace App\Http\Controllers\Web;

use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\Controller;
use App\Models\Alamat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        $alamats = Alamat::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return view('web.Profile.edit', compact('user', 'alamats'));
    }

    public function updateAccount(Request $request, UpdateUserProfileInformation $updater)
    {
        $user = Auth::user();

        // Fortify-native (validasi + email verification saat ganti email)
        $updater->update($user, $request->only('name', 'email', 'no_telp'));

        return redirect()->route('user.profile.edit', ['tab' => 'akun'])
  ->with('flash', ['type'=>'success','action'=>'update','entity'=>'Profil']);
    }

    public function updatePassword(Request $request, UpdateUserPassword $updater)
    {
        $user = Auth::user();

        // Fortify-native (PasswordValidationRules + current_password:web)
        $updater->update($user, $request->only(
            'current_password',
            'password',
            'password_confirmation'
        ));

        return redirect()->route('user.profile.edit', ['tab' => 'password'])
            ->with('flash', [
                'type' => 'success',
                'action' => 'update',
                'title' => 'Password Berhasil Diubah',
                'message' => 'Password akun kamu berhasil diperbarui.',
                'entity' => 'Password',
                'mode' => 'modal',
            ]);
    }
}
