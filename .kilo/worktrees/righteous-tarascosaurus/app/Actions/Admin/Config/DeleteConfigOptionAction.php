<?php

namespace App\Actions\Admin\Config;

use App\Models\Deprecated\ConfigOption;
use App\Helpers\ConfigOptionHelper;

class DeleteConfigOptionAction
{
    /**
     * Delete config option and clear cache.
     *
     * @param ConfigOption $configOption
     * @return bool|null
     */
    public function handle(ConfigOption $configOption): ?bool
    {
        // Cache'i temizle
        ConfigOptionHelper::clearCache(
            $configOption->option_key,
            $configOption->kategori_id,
            $configOption->yayin_tipi_id
        );

        return $configOption->delete();
    }
}
