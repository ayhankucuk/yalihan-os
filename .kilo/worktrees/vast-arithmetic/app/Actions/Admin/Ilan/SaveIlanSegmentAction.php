<?php

namespace App\Actions\Admin\Ilan;

use App\Models\Ilan;

class SaveIlanSegmentAction
{
    public function handle(Ilan $ilan): bool
    {
        return $ilan->save();
    }
}
