<?php

namespace App\Actions\Admin\Danisman;

use App\Models\User;

class UpdateDanismanAction
{
    public function handle(User $danisman, array $userData): bool
    {
        return $danisman->update($userData);
    }
}