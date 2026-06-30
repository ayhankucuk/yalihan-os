<?php

namespace App\Actions\Admin\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StoreUserAction
{
    public function handle(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'telefon' => $data['telefon'] ?? null,
            'password' => Hash::make($data['password']),
            'aktiflik_durumu' => $data['aktiflik_durumu'] ?? true,
            'email_verified_at' => !empty($data['email_verified']) ? now() : null,
        ]);

        if (!empty($data['role'])) {
            $user->assignRole($data['role']);
        }

        return $user;
    }
}
