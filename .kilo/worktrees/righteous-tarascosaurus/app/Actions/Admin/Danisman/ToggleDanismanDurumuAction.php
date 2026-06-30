<?php

namespace App\Actions\Admin\Danisman;

use App\Models\User;

class ToggleDanismanDurumuAction
{
    public function handle(User $danisman): bool
    {
        $danisman->aktiflik_durumu = ! $danisman->aktiflik_durumu;

        return $danisman->save();
    }
}