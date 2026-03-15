<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        $input['no_telp'] = preg_replace('/\D+/', '', $input['no_telp'] ?? '');

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'no_telp' => ['required', 'regex:/^[0-9]+$/', 'digits_between:8,15'],
            'password' => $this->passwordRules(),
        ], [
            'no_telp.required' => 'Nomor telepon wajib diisi.',
            'no_telp.regex' => 'Nomor telepon hanya boleh berisi angka.',
            'no_telp.digits_between' => 'Nomor telepon harus 8 sampai 15 digit.',
        ])->validate();

        return User::create([
            'name'       => $input['name'],
            'email'      => $input['email'],
            'no_telp'    => $input['no_telp'],
            'password'   => Hash::make($input['password']),
            'user_role'  => 'customer',
        ]);
    }
}
