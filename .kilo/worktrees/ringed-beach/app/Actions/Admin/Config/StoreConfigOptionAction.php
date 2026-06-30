<?php

namespace App\Actions\Admin\Config;

use App\Models\ConfigOption;
use App\Helpers\ConfigOptionHelper;

class StoreConfigOptionAction
{
    /**
     * Store a new config option and clear cache.
     *
     * @param array $data
     * @return ConfigOption
     */
    public function handle(array $data): ConfigOption
    {
        $configOption = ConfigOption::create($data);

        // Cache'i temizle
        ConfigOptionHelper::clearCache(
            $configOption->option_key,
            $configOption->kategori_id,
            $configOption->yayin_tipi_id
        );

        return $configOption;
    }
}
