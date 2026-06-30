<?php

namespace App\Actions\Admin\User;

use App\Models\User;

class DeleteUserAction
{
    public function handle(User $user): bool
    {
        return (bool) $user->delete();
    }
}
