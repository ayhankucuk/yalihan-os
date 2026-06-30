<?php

namespace App\Actions\Admin\Ups;

use App\Models\CategoryFeatureWhitelist;
use App\Services\Admin\AdminSettingsCacheService;

class StoreFeatureWhitelistAction
{
    public function handle(array $data): CategoryFeatureWhitelist
    {
        $whitelist = CategoryFeatureWhitelist::create($data);
        app(AdminSettingsCacheService::class)->invalidateUpsFeatures();
        return $whitelist;
    }
}
