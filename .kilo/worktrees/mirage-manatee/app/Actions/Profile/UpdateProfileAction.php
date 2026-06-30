<?php

namespace App\Actions\Profile;

use App\Models\User;

class UpdateProfileAction
{
    public function handle(User $user, array $validated): void
    {
        $user->fill($validated);
        $user->save();
    }
}
