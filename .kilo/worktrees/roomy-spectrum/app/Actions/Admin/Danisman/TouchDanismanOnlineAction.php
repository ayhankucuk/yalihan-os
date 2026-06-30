<?php

namespace App\Actions\Admin\Danisman;

use App\Models\User;

class TouchDanismanOnlineAction
{
    public function handle(User $danisman): bool
    {
        $danisman->last_activity_at = now();

        return $danisman->save();
    }
}