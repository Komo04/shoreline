<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('Admin.Profile.edit', compact('user'));
    }

    public function updateAccount(Request $request, UpdateUserProfileInformation $updater)
    {
        $user = Auth::user();
        $updater->update($user, $request->only('name', 'email', 'no_telp'));

        return redirect()->route('admin.profile.edit', ['tab' => 'akun'])
            ->with('flash', [
                'type' => 'success',
                'action' => 'update',
                'title' => 'Profil Admin Diperbarui',
                'message' => 'Profil admin berhasil diperbarui.',
                'entity' => 'Profil',
                'mode' => 'modal',
            ]);
    }

    public function updatePassword(Request $request, UpdateUserPassword $updater)
    {
        $user = Auth::user();
        $updater->update($user, $request->only(
            'current_password',
            'password',
            'password_confirmation'
        ));

        return redirect()->route('admin.profile.edit', ['tab' => 'password'])
            ->with('flash', [
                'type' => 'success',
                'action' => 'update',
                'title' => 'Password Admin Berhasil Diubah',
                'message' => 'Password admin berhasil diperbarui.',
                'entity' => 'Password',
                'mode' => 'modal',
            ]);
    }
}
