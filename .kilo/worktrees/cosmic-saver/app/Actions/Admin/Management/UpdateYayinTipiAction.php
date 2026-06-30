<?php

namespace App\Actions\Admin\Management;

use App\Models\YayinTipiSablonu;

class UpdateYayinTipiAction
{
    public function handle(YayinTipiSablonu $yayinTipi, array $data): YayinTipiSablonu
    {
        $yayinTipi->update($data);
        return $yayinTipi->fresh();
    }
}
