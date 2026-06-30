<?php

namespace App\Services\Mobile;

use App\Models\User;
use App\Models\V2\Ilan;

class FavoriteService
{
    public function toggle(User $user, int $ilanId): bool
    {
        // Check if ilan exists
        $ilan = Ilan::findOrFail($ilanId);

        $exists = $user->favoriIlanlar()->where('ilan_id', $ilanId)->exists();

        if ($exists) {
            $user->favoriIlanlar()->detach($ilanId);
            return false;
        } else {
            $user->favoriIlanlar()->attach($ilanId);
            return true;
        }
    }

    public function getFavorites(User $user)
    {
        return $user->favoriIlanlar()->with(['il', 'ilce', 'mahalle', 'fotograflar'])->latest('pivot_created_at');
    }
}
