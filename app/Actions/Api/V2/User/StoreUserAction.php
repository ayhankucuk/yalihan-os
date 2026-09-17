<?php

namespace App\Actions\Api\V2\User;

use App\Models\User;

class StoreUserAction
{
    public function handle(array $data): User
    {
        return User::create([
            'tenant_id'        => $data['tenant_id'] ?? null,
            'name'             => $data['ad_soyad'],
            'email'            => $data['email'],
            'telefon'          => $data['telefon'],
            'password'         => bcrypt($data['sifre_hash']),
            'aktiflik_durumu'  => true,
        ]);
    }
}
