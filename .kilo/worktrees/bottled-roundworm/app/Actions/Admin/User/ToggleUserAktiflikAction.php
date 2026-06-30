<?php

namespace App\Actions\Admin\User;

use App\Models\User;

class ToggleUserAktiflikAction
{
    public function handle(User $user): bool
    {
        return $user->update([
            'aktiflik_durumu' => ! $user->aktiflik_durumu,
        ]);
    }
}
