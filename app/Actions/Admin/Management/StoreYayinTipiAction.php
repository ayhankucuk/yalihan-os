<?php

namespace App\Actions\Admin\Management;

use App\Models\YayinTipiSablonu;

class StoreYayinTipiAction
{
    public function handle(array $data): YayinTipiSablonu
    {
        return YayinTipiSablonu::create($data);
    }
}
