<?php

namespace App\Actions\Admin\Ups;

use App\Models\CategoryFeatureWhitelist;
use App\Services\Admin\AdminSettingsCacheService;

class DeleteFeatureWhitelistAction
{
    public function handle(CategoryFeatureWhitelist $whitelist): void
    {
        $whitelist->delete();
        app(AdminSettingsCacheService::class)->invalidateUpsFeatures();
    }
}
