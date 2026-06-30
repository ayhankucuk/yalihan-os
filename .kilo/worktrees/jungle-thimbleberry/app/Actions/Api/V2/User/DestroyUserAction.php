<?php

namespace App\Actions\Api\V2\User;

use App\Models\User;

class DestroyUserAction
{
    public function handle(User $user): bool
    {
        return $user->delete();
    }
}
