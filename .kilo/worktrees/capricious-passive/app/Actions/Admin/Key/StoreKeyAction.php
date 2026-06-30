<?php

namespace App\Actions\Admin\Key;

use App\Models\AnahtarYonetimi;

class StoreKeyAction
{
    public function handle(array $data): AnahtarYonetimi
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return AnahtarYonetimi::create($data);
    }
}
