<?php

namespace App\Actions\Admin\Key;

use App\Models\AnahtarYonetimi;

class UpdateKeyAction
{
    public function handle(AnahtarYonetimi $anahtar, array $data): AnahtarYonetimi
    {
        $data['updated_by'] = auth()->id();

        $anahtar->update($data);

        return $anahtar->fresh();
    }
}
