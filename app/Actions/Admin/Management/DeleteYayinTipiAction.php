<?php

namespace App\Actions\Admin\Management;

use App\Models\YayinTipiSablonu;

class DeleteYayinTipiAction
{
    public function handle(YayinTipiSablonu $yayinTipi): bool
    {
        return $yayinTipi->delete();
    }
}
