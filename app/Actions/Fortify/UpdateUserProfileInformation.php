<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * @param  array<string, string>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'no_telp' => ['required', 'regex:/^[0-9]+$/', 'digits_between:8,15'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ])->validateWithBag('updateProfileInformation');

        if ($input['email'] !== $user->email && $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'no_telp' => $input['no_telp'],
                'email' => $input['email'],
            ])->save();
        }
    }

    /**
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'no_telp' => $input['no_telp'],
            'email' => $input['email'],
            'email_verified_at' => null, // <- reset verifikasi
        ])->save();

        $user->sendEmailVerificationNotification(); // <- kirim verifikasi email baru
    }
}
