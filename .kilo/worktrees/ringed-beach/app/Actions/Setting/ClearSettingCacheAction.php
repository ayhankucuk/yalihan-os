<?php

namespace App\Actions\Setting;

use App\Models\Setting;

class ClearSettingCacheAction
{
    public function handle(): void
    {
        Setting::clearCache();
    }
}
