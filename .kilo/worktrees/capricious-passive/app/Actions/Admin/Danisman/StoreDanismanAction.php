<?php

namespace App\Actions\Admin\Danisman;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class StoreDanismanAction
{
    /**
     * Handle the storage of a new Danisman (User).
     *
     * @param array $userData
     * @return User
     */
    public function handle(array $userData): User
    {
        // 1. Create User
        $user = User::create($userData);

        // 2. Assign Danisman Role
        $user->assignRole('danisman');

        // 3. Log
        Log::channel('module_changes')->info('Danışman oluşturuldu (Action)', [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);

        return $user;
    }
}
