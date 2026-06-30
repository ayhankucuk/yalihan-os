<?php

namespace App\Actions\Admin\Ups;

use App\Models\CategoryFeatureWhitelist;
use App\Services\Admin\AdminSettingsCacheService;

class UpdateFeatureWhitelistAction
{
    public function handle(CategoryFeatureWhitelist $whitelist, array $data): CategoryFeatureWhitelist
    {
        $whitelist->update($data);
        app(AdminSettingsCacheService::class)->invalidateUpsFeatures();
        return $whitelist;
    }
}
