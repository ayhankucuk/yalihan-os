<?php

namespace App\Actions\Admin\Key;

use App\Models\AnahtarYonetimi;

class DeleteKeyAction
{
    public function handle(AnahtarYonetimi $anahtar): bool
    {
        return $anahtar->delete();
    }
}
