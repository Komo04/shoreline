<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate([
            'email' => 'admin@gmail.com',
        ], [
            'name' => 'admin',
            'no_telp' => '081234567890',
            'password' => Hash::make('admin12345'),
            'user_role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
