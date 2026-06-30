<?php

namespace App\Actions\Admin\Config;

use App\Helpers\ConfigOptionHelper;
use App\Models\ConfigOption;

class UpdateConfigOptionAction
{
    public function handle(ConfigOption $configOption, array $validated): bool
    {
        $oldKey = $configOption->option_key;
        $oldKategoriId = $configOption->kategori_id;
        $oldYayinTipiId = $configOption->yayin_tipi_id;

        $result = $configOption->update($validated);

        ConfigOptionHelper::clearCache($oldKey, $oldKategoriId, $oldYayinTipiId);
        ConfigOptionHelper::clearCache(
            $configOption->option_key,
            $configOption->kategori_id,
            $configOption->yayin_tipi_id
        );

        return $result;
    }
}
